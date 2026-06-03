<?php
/**
 * Habit tracking system – streaks, logs, and XP rewards.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Habits
 */
class Xen_Habits extends Xen_Database {

	/** XP for logging a habit */
	const HABIT_XP    = 30;
	/** Coins for logging a habit */
	const HABIT_COINS = 5;
	/** Bonus multiplier every 7 days (streak milestone) */
	const STREAK_BONUS_DAYS = 7;

	/** @var array Valid habit categories */
	const CATEGORIES = array(
		'physical', 'mental', 'reading', 'business',
		'productivity', 'spiritual', 'relationship', 'custom',
	);

	public function __construct() {
		parent::__construct();
	}

	// ─── Create ───────────────────────────────────────────────────────────

	/**
	 * Create a new habit for a user.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $data    Habit data.
	 * @return int|WP_Error Habit ID or error.
	 */
	public function create( $user_id, array $data ) {
		$user_id = (int) $user_id;
		$title   = sanitize_text_field( $data['title'] ?? '' );

		if ( ! $title ) {
			return new WP_Error( 'empty_title', __( 'Habit title is required.', 'xen-levelup' ) );
		}

		$category = in_array( $data['category'] ?? '', self::CATEGORIES, true ) ? $data['category'] : 'custom';

		$id = $this->insert(
			'habits',
			array(
				'user_id'     => $user_id,
				'title'       => $title,
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'category'    => $category,
				'icon'        => sanitize_text_field( $data['icon'] ?? '⭐' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return $id ?: new WP_Error( 'db_error', __( 'Failed to create habit.', 'xen-levelup' ) );
	}

	// ─── Log Completion ───────────────────────────────────────────────────

	/**
	 * Log today's completion of a habit and update the streak.
	 *
	 * @param int    $habit_id Habit ID.
	 * @param int    $user_id  WP user ID.
	 * @param string $notes    Optional notes.
	 * @return array|WP_Error
	 */
	public function log( $habit_id, $user_id, $notes = '' ) {
		$habit_id = (int) $habit_id;
		$user_id  = (int) $user_id;

		$habit = $this->get_row( 'habits', array( 'id' => $habit_id, 'user_id' => $user_id ) );
		if ( ! $habit ) {
			return new WP_Error( 'not_found', __( 'Habit not found.', 'xen-levelup' ) );
		}
		if ( ! $habit->is_active ) {
			return new WP_Error( 'inactive', __( 'Habit is inactive.', 'xen-levelup' ) );
		}

		$today = current_time( 'Y-m-d' );

		// Check duplicate for today
		if ( $this->row_exists( 'habit_logs', array( 'habit_id' => $habit_id, 'log_date' => $today ) ) ) {
			return new WP_Error( 'already_logged', __( 'Habit already logged today.', 'xen-levelup' ) );
		}

		// Insert log entry
		$this->insert(
			'habit_logs',
			array(
				'habit_id' => $habit_id,
				'user_id'  => $user_id,
				'log_date' => $today,
				'notes'    => sanitize_textarea_field( $notes ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		// Update streak
		$streak = $this->calculate_streak( $habit_id, $today, $habit->last_logged );
		$longest = max( (int) $habit->longest_streak, $streak );

		$this->update(
			'habits',
			array(
				'current_streak'    => $streak,
				'longest_streak'    => $longest,
				'total_completions' => (int) $habit->total_completions + 1,
				'last_logged'       => $today,
			),
			array( 'id' => $habit_id )
		);

		// XP + bonus for streak milestones
		$xp = self::HABIT_XP;
		if ( $streak > 0 && 0 === $streak % self::STREAK_BONUS_DAYS ) {
			$xp = (int) ( $xp * ( 1 + ( $streak / self::STREAK_BONUS_DAYS ) * 0.5 ) );
		}

		$xp_result = xen_levelup()->leveling->add_xp(
			$user_id,
			$xp,
			'habit',
			$habit_id,
			sprintf( __( 'Habit: %s', 'xen-levelup' ), $habit->title )
		);

		xen_levelup()->currency->add(
			$user_id,
			self::HABIT_COINS,
			'habit',
			sprintf( __( 'Habit: %s', 'xen-levelup' ), $habit->title ),
			$habit_id,
			'habit'
		);

		// Update profile total
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}xen_user_profiles SET total_habits = total_habits + 1 WHERE user_id = %d",
			$user_id
		) );
		xen_levelup()->user->flush_profile_cache( $user_id );

		do_action( 'xen_habit_logged', $user_id, array(
			'habit'  => $habit,
			'streak' => $streak,
		) );

		return array(
			'success'    => true,
			'streak'     => $streak,
			'xp_earned'  => $xp,
			'leveled_up' => $xp_result['leveled_up'] ?? false,
		);
	}

	// ─── Streak Calculation ───────────────────────────────────────────────

	/**
	 * Calculate the new streak for a habit.
	 *
	 * @param int    $habit_id    Habit ID.
	 * @param string $today       Today's date Y-m-d.
	 * @param string $last_logged Previous log date Y-m-d or null.
	 * @return int
	 */
	private function calculate_streak( $habit_id, $today, $last_logged ) {
		if ( ! $last_logged ) {
			return 1;
		}
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $today ) ) );
		if ( $last_logged === $yesterday ) {
			// Continuing streak – fetch current streak from db
			$habit = $this->get_row( 'habits', array( 'id' => (int) $habit_id ) );
			return (int) $habit->current_streak + 1;
		}
		// Streak broken
		return 1;
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get all habits for a user.
	 *
	 * @param int  $user_id   WP user ID.
	 * @param bool $active_only Return only active habits.
	 * @return array
	 */
	public function get_habits( $user_id, $active_only = true ) {
		$where = array( 'user_id' => (int) $user_id );
		if ( $active_only ) {
			$where['is_active'] = 1;
		}
		return $this->get_rows( 'habits', $where, 'category ASC, created_at DESC' );
	}

	/**
	 * Get all habit logs for a user within a date range.
	 *
	 * @param int    $user_id    WP user ID.
	 * @param string $date_from  Y-m-d start.
	 * @param string $date_to    Y-m-d end.
	 * @return array
	 */
	public function get_logs( $user_id, $date_from, $date_to ) {
		$t    = $this->table( 'habit_logs' );
		return $this->query(
			"SELECT hl.*, h.title, h.category, h.icon FROM {$t} hl
			 JOIN {$this->table('habits')} h ON h.id = hl.habit_id
			 WHERE hl.user_id = %d AND hl.log_date BETWEEN %s AND %s
			 ORDER BY hl.log_date DESC",
			array( (int) $user_id, $date_from, $date_to )
		);
	}

	/**
	 * Check if a habit was logged today.
	 *
	 * @param int $habit_id Habit ID.
	 * @return bool
	 */
	public function logged_today( $habit_id ) {
		return $this->row_exists( 'habit_logs', array(
			'habit_id' => (int) $habit_id,
			'log_date' => current_time( 'Y-m-d' ),
		) );
	}

	/**
	 * Deactivate a habit.
	 *
	 * @param int $habit_id Habit ID.
	 * @param int $user_id  WP user ID.
	 * @return bool
	 */
	public function deactivate( $habit_id, $user_id ) {
		return (bool) $this->update(
			'habits',
			array( 'is_active' => 0 ),
			array( 'id' => (int) $habit_id, 'user_id' => (int) $user_id )
		);
	}

	/**
	 * Get the longest streak across all habits for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_longest_streak( $user_id ) {
		$t   = $this->table( 'habits' );
		$val = $this->get_var(
			"SELECT MAX(longest_streak) FROM {$t} WHERE user_id = %d",
			array( (int) $user_id )
		);
		return (int) $val;
	}
}
