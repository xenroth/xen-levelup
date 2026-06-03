<?php
/**
 * Task system – user-created personal tasks (max 10 per day).
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Tasks
 */
class Xen_Tasks extends Xen_Database {

	/** XP reward for completing a personal task */
	const TASK_XP    = 100;
	/** Coin reward for completing a personal task */
	const TASK_COINS = 20;

	public function __construct() {
		parent::__construct();
	}

	// ─── Create ───────────────────────────────────────────────────────────

	/**
	 * Create a new task for a user.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $data    Task data.
	 * @return int|WP_Error Task ID or error.
	 */
	public function create( $user_id, array $data ) {
		$user_id = (int) $user_id;

		// Validate daily limit
		if ( $this->get_today_count( $user_id ) >= XEN_MAX_DAILY_TASKS ) {
			return new WP_Error( 'limit_reached',
				sprintf(
					/* translators: %d = max tasks */
					__( 'Daily task limit of %d reached. Try again tomorrow.', 'xen-levelup' ),
					XEN_MAX_DAILY_TASKS
				)
			);
		}

		$title = sanitize_text_field( $data['title'] ?? '' );
		if ( ! $title ) {
			return new WP_Error( 'empty_title', __( 'Task title is required.', 'xen-levelup' ) );
		}

		$due = '';
		if ( ! empty( $data['due_date'] ) ) {
			$due = sanitize_text_field( $data['due_date'] );
			// Validate date format
			$d = \DateTime::createFromFormat( 'Y-m-d', $due );
			if ( ! $d ) {
				$due = '';
			}
		}

		$valid_priorities = array( 'low', 'medium', 'high', 'critical' );
		$priority = in_array( $data['priority'] ?? '', $valid_priorities, true ) ? $data['priority'] : 'medium';

		$id = $this->insert(
			'user_tasks',
			array(
				'user_id'     => $user_id,
				'title'       => $title,
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'category'    => sanitize_key( $data['category'] ?? 'general' ),
				'priority'    => $priority,
				'due_date'    => $due ?: null,
				'notes'       => sanitize_textarea_field( $data['notes'] ?? '' ),
				'xp_reward'   => self::TASK_XP,
				'coin_reward' => self::TASK_COINS,
				'status'      => 'pending',
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $id ?: new WP_Error( 'db_error', __( 'Failed to create task.', 'xen-levelup' ) );
	}

	// ─── Complete ─────────────────────────────────────────────────────────

	/**
	 * Mark a task as complete and award rewards.
	 *
	 * @param int $task_id Task ID.
	 * @param int $user_id WP user ID.
	 * @return array|WP_Error
	 */
	public function complete( $task_id, $user_id ) {
		$task = $this->get_row( 'user_tasks', array( 'id' => (int) $task_id, 'user_id' => (int) $user_id ) );

		if ( ! $task ) {
			return new WP_Error( 'not_found', __( 'Task not found.', 'xen-levelup' ) );
		}
		if ( 'pending' !== $task->status ) {
			return new WP_Error( 'already_done', __( 'Task has already been completed.', 'xen-levelup' ) );
		}

		$this->update(
			'user_tasks',
			array( 'status' => 'completed', 'completed_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $task_id, 'user_id' => (int) $user_id )
		);

		// Award XP
		$xp_result = xen_levelup()->leveling->add_xp(
			$user_id,
			(int) $task->xp_reward,
			'task',
			$task_id,
			sprintf( __( 'Task: %s', 'xen-levelup' ), $task->title )
		);

		// Award coins
		xen_levelup()->currency->add(
			$user_id,
			(int) $task->coin_reward,
			'task',
			sprintf( __( 'Task reward: %s', 'xen-levelup' ), $task->title ),
			$task_id,
			'task'
		);

		// Update task counter on profile
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}xen_user_profiles SET total_tasks = total_tasks + 1 WHERE user_id = %d",
			(int) $user_id
		) );
		xen_levelup()->user->flush_profile_cache( $user_id );

		do_action( 'xen_task_completed', $user_id, array( 'task' => $task, 'xp_result' => $xp_result ) );

		return array(
			'success'    => true,
			'task'       => $task,
			'xp_earned'  => (int) $task->xp_reward,
			'coins_earned'=> (int) $task->coin_reward,
			'leveled_up' => $xp_result['leveled_up'] ?? false,
			'new_level'  => $xp_result['new_level']  ?? null,
		);
	}

	// ─── Delete ───────────────────────────────────────────────────────────

	/**
	 * Delete a task (pending tasks only).
	 *
	 * @param int $task_id Task ID.
	 * @param int $user_id WP user ID.
	 * @return bool
	 */
	public function delete( $task_id, $user_id ) {
		$task = $this->get_row( 'user_tasks', array( 'id' => (int) $task_id, 'user_id' => (int) $user_id ) );
		if ( ! $task || 'pending' !== $task->status ) {
			return false;
		}
		return (bool) $this->delete( 'user_tasks', array( 'id' => (int) $task_id ) );
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get all tasks for a user.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $status  'all', 'pending', or 'completed'.
	 * @return array
	 */
	public function get_tasks( $user_id, $status = 'all' ) {
		$t    = $this->table( 'user_tasks' );
		$sql  = "SELECT * FROM {$t} WHERE user_id = %d";
		$args = array( (int) $user_id );

		if ( 'all' !== $status ) {
			$sql   .= ' AND status = %s';
			$args[] = sanitize_key( $status );
		}
		$sql .= ' ORDER BY FIELD(priority,"critical","high","medium","low"), created_at DESC';

		return $this->query( $sql, $args );
	}

	/**
	 * How many tasks were created today by this user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_today_count( $user_id ) {
		$t   = $this->table( 'user_tasks' );
		$sql = "SELECT COUNT(*) FROM {$t} WHERE user_id = %d AND DATE(created_at) = %s";
		return (int) $this->get_var( $sql, array( (int) $user_id, current_time( 'Y-m-d' ) ) );
	}

	/**
	 * Return remaining tasks allowed today.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_remaining_today( $user_id ) {
		return max( 0, XEN_MAX_DAILY_TASKS - $this->get_today_count( $user_id ) );
	}
}
