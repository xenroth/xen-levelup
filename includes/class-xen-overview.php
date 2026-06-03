<?php
/**
 * "What's New" card + game-wide Overview Stats.
 *
 * What's New:
 *   - Static per-version array of feature announcements.
 *   - Shown once per user per version; dismissed via AJAX (stored in user meta).
 *
 * Overview Stats:
 *   - System-wide totals shown on the public dashboard.
 *   - Cached for 15 minutes via transient.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xen_Overview extends Xen_Database {

	const STATS_TRANSIENT = 'xen_overview_stats';
	const STATS_TTL       = 900; // 15 minutes

	public function __construct() {
		parent::__construct();
	}

	// ─── What's New ───────────────────────────────────────────────────────

	/**
	 * Returns an array of "What's New" items for a given version.
	 * Each item: [ icon, title, description ]
	 *
	 * @param  string $version  Semantic version string, e.g. '1.1.0'.
	 * @return array
	 */
	public static function whats_new( $version = XEN_LEVELUP_VERSION ) {
		$all = array(
			'1.1.0' => array(
				array(
					'icon'  => '📅',
					'title' => __( 'Daily Check-In Rewards', 'xen-levelup' ),
					'desc'  => __( 'Check in every day to earn XP and coins. Build streaks to unlock bigger rewards every 7 days.', 'xen-levelup' ),
				),
				array(
					'icon'  => '📊',
					'title' => __( 'Dashboard Overview Stats', 'xen-levelup' ),
					'desc'  => __( 'See system-wide stats — total hunters, quests completed, XP earned, and more — right on your dashboard.', 'xen-levelup' ),
				),
				array(
					'icon'  => '💎',
					'title' => __( 'Custom Currency Name & Symbol', 'xen-levelup' ),
					'desc'  => __( 'Administrators can now rename the in-game currency and choose any symbol (emoji or text).', 'xen-levelup' ),
				),
			),
		);

		return $all[ $version ] ?? array();
	}

	/**
	 * Check whether the current user has dismissed the What's New card for this version.
	 *
	 * @param  int    $user_id
	 * @param  string $version
	 * @return bool
	 */
	public function is_dismissed( $user_id, $version = XEN_LEVELUP_VERSION ) {
		return get_user_meta( (int) $user_id, 'xen_levelup_whats_new_dismissed', true ) === $version;
	}

	/**
	 * Mark the What's New card as dismissed for this user + version.
	 *
	 * @param  int    $user_id
	 * @param  string $version
	 */
	public function dismiss( $user_id, $version = XEN_LEVELUP_VERSION ) {
		update_user_meta( (int) $user_id, 'xen_levelup_whats_new_dismissed', sanitize_text_field( $version ) );
	}

	// ─── Overview Stats ───────────────────────────────────────────────────

	/**
	 * Returns cached system-wide overview stats.
	 *
	 * @return array {
	 *   total_hunters, total_xp, total_quests, total_tasks,
	 *   total_habits, highest_level, top_hunter_name, active_today
	 * }
	 */
	public function get_stats() {
		$cached = get_transient( self::STATS_TRANSIENT );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$p = $wpdb->prefix . 'xen_';

		$stats = array(
			'total_hunters'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}user_profiles" ),
			'total_xp'        => (int) $wpdb->get_var( "SELECT COALESCE(SUM(xp_amount),0) FROM {$p}xp_log" ),
			'total_quests'    => (int) $wpdb->get_var( "SELECT COALESCE(SUM(total_quests),0) FROM {$p}user_profiles" ),
			'total_tasks'     => (int) $wpdb->get_var( "SELECT COALESCE(SUM(total_tasks),0) FROM {$p}user_profiles" ),
			'total_habits'    => (int) $wpdb->get_var( "SELECT COALESCE(SUM(total_habits),0) FROM {$p}user_profiles" ),
			'highest_level'   => (int) $wpdb->get_var( "SELECT COALESCE(MAX(level),0) FROM {$p}user_profiles" ),
			'top_hunter_name' => '',
			'active_today'    => 0,
		);

		// Top hunter by XP
		$top = $wpdb->get_row(
			"SELECT p.user_id, p.experience FROM {$p}user_profiles p ORDER BY p.experience DESC LIMIT 1"
		);
		if ( $top ) {
			$user = get_userdata( (int) $top->user_id );
			$stats['top_hunter_name'] = $user ? esc_html( $user->display_name ) : '';
		}

		// Hunters active today (logged XP today)
		$today = date( 'Y-m-d' );
		$stats['active_today'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$p}xp_log WHERE DATE(created_at) = %s",
				$today
			)
		);

		set_transient( self::STATS_TRANSIENT, $stats, self::STATS_TTL );

		return $stats;
	}

	/**
	 * Purge the overview stats transient (e.g. after a significant action).
	 */
	public function flush_stats_cache() {
		delete_transient( self::STATS_TRANSIENT );
	}
}
