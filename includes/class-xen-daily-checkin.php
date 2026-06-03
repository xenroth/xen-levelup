<?php
/**
 * Daily Check-In system.
 *
 * Users can check in once per calendar day to earn XP and coins.
 * Rewards scale with a consecutive streak:
 *   Day 1–6:  50 XP / 10 coins
 *   Day 7+:   100 XP / 25 coins  (+25 XP / +10 coins per extra 7-day milestone)
 *
 * Streak resets if the user misses a day.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xen_Daily_Checkin extends Xen_Database {

	/* Base rewards */
	const BASE_XP    = 50;
	const BASE_COINS = 10;

	/* Bonus every 7-day milestone */
	const MILESTONE_XP    = 25;
	const MILESTONE_COINS = 10;

	public function __construct() {
		parent::__construct();
	}

	// ─── Public API ───────────────────────────────────────────────────────

	/**
	 * Check whether the user can check in today.
	 *
	 * @param  int  $user_id
	 * @return bool
	 */
	public function can_checkin( $user_id ) {
		$last = $this->last_checkin_date( $user_id );
		return $last !== current_time( 'Y-m-d' );
	}

	/**
	 * Perform the daily check-in for a user.
	 *
	 * @param  int  $user_id
	 * @return array|WP_Error  {streak, xp, coins, new_level, leveled_up, ...} or error.
	 */
	public function checkin( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $this->can_checkin( $user_id ) ) {
			return new WP_Error( 'already_checked_in', __( 'Already checked in today.', 'xen-levelup' ) );
		}

		global $wpdb;
		$table = $this->table( 'checkins' );
		$today = current_time( 'Y-m-d' );

		// Calculate new streak
		$streak = $this->calculate_new_streak( $user_id );

		// Calculate rewards
		$milestones = floor( $streak / 7 );
		$xp    = self::BASE_XP    + ( $milestones * self::MILESTONE_XP    );
		$coins = self::BASE_COINS + ( $milestones * self::MILESTONE_COINS );

		// Record the check-in
		$wpdb->insert(
			$table,
			array(
				'user_id'      => $user_id,
				'checkin_date' => $today,
				'streak'       => $streak,
				'xp_earned'    => $xp,
				'coins_earned' => $coins,
			),
			array( '%d', '%s', '%d', '%d', '%d' )
		);

		if ( ! $wpdb->insert_id ) {
			return new WP_Error( 'db_error', __( 'Check-in could not be saved.', 'xen-levelup' ) );
		}

		// Award XP and coins
		$xp_result = xen_levelup()->leveling->add_xp( $user_id, $xp, 'checkin', 0, __( 'Daily Check-In', 'xen-levelup' ) );
		xen_levelup()->currency->add( $user_id, $coins, 'checkin', __( 'Daily Check-In reward', 'xen-levelup' ) );

		// Send notification
		xen_levelup()->notifications->add(
			$user_id,
			'checkin',
			__( '📅 Daily Check-In!', 'xen-levelup' ),
			sprintf(
				/* translators: 1: streak days, 2: XP, 3: coins */
				__( 'Day %1$d streak! You earned %2$d XP and %3$d coins.', 'xen-levelup' ),
				$streak,
				$xp,
				$coins
			),
			array( 'streak' => $streak )
		);

		do_action( 'xen_daily_checkin', $user_id, $streak, $xp, $coins );

		return array(
			'streak'     => $streak,
			'xp'         => $xp,
			'coins'      => $coins,
			'leveled_up' => ! empty( $xp_result['leveled_up'] ) ? $xp_result['leveled_up'] : false,
			'new_level'  => ! empty( $xp_result['new_level']  ) ? $xp_result['new_level']  : null,
			'rank_title' => ! empty( $xp_result['rank_title'] ) ? $xp_result['rank_title'] : null,
		);
	}

	/**
	 * Get the user's current check-in streak (today counts if already checked in).
	 *
	 * @param  int $user_id
	 * @return int
	 */
	public function get_streak( $user_id ) {
		global $wpdb;
		$table = $this->table( 'checkins' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT streak FROM {$table} WHERE user_id = %d ORDER BY checkin_date DESC LIMIT 1",
				$user_id
			)
		);
	}

	/**
	 * Get the user's total lifetime check-in count.
	 *
	 * @param  int $user_id
	 * @return int
	 */
	public function get_total_checkins( $user_id ) {
		global $wpdb;
		$table = $this->table( 'checkins' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * Get recent check-in history for a user.
	 *
	 * @param  int $user_id
	 * @param  int $limit
	 * @return array
	 */
	public function get_history( $user_id, $limit = 7 ) {
		global $wpdb;
		$table = $this->table( 'checkins' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY checkin_date DESC LIMIT %d",
				$user_id,
				$limit
			)
		);
	}

	// ─── Private helpers ──────────────────────────────────────────────────

	/**
	 * Get the date of the user's last check-in, or null if never.
	 *
	 * @param  int $user_id
	 * @return string|null  'Y-m-d' or null
	 */
	private function last_checkin_date( $user_id ) {
		global $wpdb;
		$table = $this->table( 'checkins' );
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT checkin_date FROM {$table} WHERE user_id = %d ORDER BY checkin_date DESC LIMIT 1",
				$user_id
			)
		);
	}

	/**
	 * Calculate what the new streak will be after a successful check-in.
	 * Streak continues if the last check-in was yesterday; otherwise resets to 1.
	 *
	 * @param  int $user_id
	 * @return int
	 */
	private function calculate_new_streak( $user_id ) {
		$last = $this->last_checkin_date( $user_id );

		if ( ! $last ) {
			return 1; // first ever check-in
		}

		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );

		if ( $last === $yesterday ) {
			return $this->get_streak( $user_id ) + 1;
		}

		return 1; // streak broken
	}
}
