<?php
/**
 * Cron jobs – scheduling and callbacks for all recurring tasks.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Cron
 */
class Xen_Cron {

	public function __construct() {
		// Register custom schedules
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );

		// Hook callbacks
		add_action( 'xen_daily_quest_generation', array( $this, 'daily_tasks'          ) );
		add_action( 'xen_random_quest_generation', array( $this, 'random_quest_tasks'  ) );
		add_action( 'xen_weekly_tasks',            array( $this, 'weekly_tasks'        ) );
		add_action( 'xen_rankings_update',         array( $this, 'rankings_update'     ) );
	}

	// ─── Custom Schedules ─────────────────────────────────────────────────

	/**
	 * Register 'weekly' cron schedule.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'xen-levelup' ),
			);
		}
		return $schedules;
	}

	// ─── Callbacks ────────────────────────────────────────────────────────

	/**
	 * Daily midnight tasks:
	 *  - Generate daily quests for all users
	 *  - Expire stale quests
	 *  - Prune old notifications
	 */
	public function daily_tasks() {
		$xen = xen_levelup();

		// Generate quests
		$xen->daily_quests->generate_for_all_users();

		// Expire any leftover stale quests
		$xen->quests->expire_stale_quests();

		// Prune notifications older than 30 days
		$xen->notifications->prune_old();
	}

	/**
	 * Hourly: random quest assignment and expiry check.
	 */
	public function random_quest_tasks() {
		$xen = xen_levelup();
		$xen->random_quests->generate_for_all_users();
		$xen->random_quests->expire_stale();
	}

	/**
	 * Weekly tasks:
	 *  - Assign special quests
	 *  - Run legendary quest selection
	 */
	public function weekly_tasks() {
		$xen = xen_levelup();
		$xen->special_quests->generate_for_all_users();
		$xen->legendary_quests->run_weekly_assignment();
	}

	/**
	 * Twice-daily: recalculate all rankings.
	 */
	public function rankings_update() {
		xen_levelup()->rankings->recalculate_all();
	}
}
