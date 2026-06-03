<?php
/**
 * "What's New" card + game-wide Overview Stats.
 *
 * What's New:
 *   - Parses CHANGELOG.md (plugin root) to extract the "### What's New" bullet
 *     items for the current plugin version.
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
	 * Returns "What's New" items for a given version by parsing CHANGELOG.md.
	 *
	 * Expected CHANGELOG.md format under each `## [x.x.x]` section:
	 *   ### What's New
	 *   - ICON **Title** — Description
	 *
	 * Results are cached in a static variable for the duration of the request.
	 *
	 * @param  string $version  Semantic version string, e.g. '1.1.0'.
	 * @return array  Each item: [ 'icon' => string, 'title' => string, 'desc' => string ]
	 */
	public static function whats_new( $version = XEN_LEVELUP_VERSION ) {
		static $cache = array();

		if ( isset( $cache[ $version ] ) ) {
			return $cache[ $version ];
		}

		$file = XEN_LEVELUP_PLUGIN_DIR . 'CHANGELOG.md';
		if ( ! file_exists( $file ) ) {
			$cache[ $version ] = array();
			return array();
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content ) {
			$cache[ $version ] = array();
			return array();
		}

		// Isolate the block for this version: ## [x.x.x] ... next ## heading or end of file.
		$ver_esc = preg_quote( $version, '/' );
		if ( ! preg_match( '/^## \[' . $ver_esc . '\][^\n]*\n(.*?)(?=^## \[|\z)/ms', $content, $section_match ) ) {
			$cache[ $version ] = array();
			return array();
		}

		// Within that block, isolate the "### What's New" sub-section.
		$section = $section_match[1];
		if ( ! preg_match( "/^### What's New\n(.*?)(?=^###|\z)/ms", $section, $subsec_match ) ) {
			$cache[ $version ] = array();
			return array();
		}

		// Parse bullet lines: - ICON **Title** — Description
		$items = array();
		foreach ( explode( "\n", $subsec_match[1] ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || '-' !== $line[0] ) {
				continue;
			}
			// Captures: icon (first non-space token), bold title, text after — or - separator.
			if ( preg_match( '/^-\s+(\S+)\s+\*\*(.+?)\*\*\s*[—–\-]+\s*(.+)$/u', $line, $m ) ) {
				$items[] = array(
					'icon'  => $m[1],
					'title' => $m[2],
					'desc'  => $m[3],
				);
			}
		}

		$cache[ $version ] = $items;
		return $items;
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
