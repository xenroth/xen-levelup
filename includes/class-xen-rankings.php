<?php
/**
 * Rankings system – global, weekly, and monthly leaderboards.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Rankings
 */
class Xen_Rankings extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Update ───────────────────────────────────────────────────────────

	/**
	 * Schedule a ranking update (called from the xen_xp_added hook).
	 *
	 * We debounce by using a transient so we don't recalculate on every XP gain.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $data    XP result data.
	 */
	public function schedule_update( $user_id, $data ) {
		if ( ! get_transient( 'xen_rankings_pending_' . $user_id ) ) {
			set_transient( 'xen_rankings_pending_' . $user_id, 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Recalculate and cache all rankings.
	 *
	 * Called by the twice-daily cron job.
	 */
	public function recalculate_all() {
		$this->recalculate( 'global', 'all' );
		$this->recalculate( 'weekly',  gmdate( 'Y-W' ) );
		$this->recalculate( 'monthly', gmdate( 'Y-m' ) );
	}

	/**
	 * Calculate rankings for a specific period and persist them.
	 *
	 * @param string $period_type 'global', 'weekly', or 'monthly'.
	 * @param string $period_key  Period key (e.g. '2025-W01' or '2025-01').
	 */
	private function recalculate( $period_type, $period_key ) {
		global $wpdb;

		$profiles_table = $wpdb->prefix . 'xen_user_profiles';
		$xp_log_table   = $wpdb->prefix . 'xen_xp_log';

		if ( 'global' === $period_type ) {
			$rows = $wpdb->get_results( // phpcs:ignore
				"SELECT user_id, level, experience, total_quests, total_tasks
				 FROM {$profiles_table}
				 ORDER BY level DESC, experience DESC
				 LIMIT 500"
			);
		} elseif ( 'weekly' === $period_type ) {
			$start = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
			$rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT p.user_id, p.level, p.experience, p.total_quests, p.total_tasks,
				        COALESCE(SUM(xl.xp_amount), 0) AS period_score
				 FROM {$profiles_table} p
				 LEFT JOIN {$xp_log_table} xl ON xl.user_id = p.user_id AND DATE(xl.created_at) >= %s
				 GROUP BY p.user_id
				 ORDER BY period_score DESC, p.level DESC
				 LIMIT 500",
				$start
			) );
		} else {
			// Monthly
			$start = gmdate( 'Y-m-01' );
			$rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
				"SELECT p.user_id, p.level, p.experience, p.total_quests, p.total_tasks,
				        COALESCE(SUM(xl.xp_amount), 0) AS period_score
				 FROM {$profiles_table} p
				 LEFT JOIN {$xp_log_table} xl ON xl.user_id = p.user_id AND DATE(xl.created_at) >= %s
				 GROUP BY p.user_id
				 ORDER BY period_score DESC, p.level DESC
				 LIMIT 500",
				$start
			) );
		}

		$rank = 1;
		foreach ( (array) $rows as $row ) {
			$score = isset( $row->period_score ) ? (int) $row->period_score : (int) $row->experience;

			$wpdb->query( $wpdb->prepare( // phpcs:ignore
				"INSERT INTO {$wpdb->prefix}xen_rankings
				    (user_id, period_type, period_key, rank_position, score, level, quests_completed, tasks_completed)
				 VALUES (%d, %s, %s, %d, %d, %d, %d, %d)
				 ON DUPLICATE KEY UPDATE
				    rank_position = VALUES(rank_position),
				    score = VALUES(score),
				    level = VALUES(level),
				    quests_completed = VALUES(quests_completed),
				    tasks_completed = VALUES(tasks_completed),
				    updated_at = NOW()",
				$row->user_id,
				$period_type,
				$period_key,
				$rank,
				$score,
				(int) $row->level,
				(int) $row->total_quests,
				(int) $row->total_tasks
			) );
			$rank++;
		}
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get the top N users for a given period with their WP display info.
	 *
	 * @param string $period_type Period type.
	 * @param string $period_key  Period key.
	 * @param int    $limit       Max results.
	 * @return array
	 */
	public function get_leaderboard( $period_type = 'global', $period_key = 'all', $limit = 50 ) {
		global $wpdb;

		if ( 'weekly' === $period_type ) {
			$period_key = gmdate( 'Y-W' );
		} elseif ( 'monthly' === $period_type ) {
			$period_key = gmdate( 'Y-m' );
		}

		$r = $wpdb->prefix . 'xen_rankings';
		$p = $wpdb->prefix . 'xen_user_profiles';
		$u = $wpdb->prefix . 'users';

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
			"SELECT r.rank_position, r.score, p.level, r.quests_completed, r.tasks_completed,
			        u.ID as user_id, u.display_name,
			        p.rank_title, p.current_title, p.coins
			 FROM {$r} r
			 INNER JOIN {$u} u ON u.ID = r.user_id
			 INNER JOIN {$p} p ON p.user_id = r.user_id
			 WHERE r.period_type = %s AND r.period_key = %s
			 ORDER BY r.rank_position ASC
			 LIMIT %d",
			$period_type,
			$period_key,
			(int) $limit
		) );
	}

	/**
	 * Get a single user's rank for a period.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param string $period_type Period type.
	 * @param string $period_key  Period key.
	 * @return int Rank position (0 if unranked).
	 */
	public function get_user_rank( $user_id, $period_type = 'global', $period_key = 'all' ) {
		$row = $this->get_row( 'rankings', array(
			'user_id'     => (int) $user_id,
			'period_type' => $period_type,
			'period_key'  => $period_key,
		) );
		return $row ? (int) $row->rank_position : 0;
	}
}
