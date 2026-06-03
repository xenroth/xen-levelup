<?php
/**
 * AJAX handlers – all authenticated endpoints using wp_ajax_ / wp_ajax_nopriv_.
 *
 * Security: every action verifies a nonce before processing.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Ajax
 */
class Xen_Ajax {

	public function __construct() {
		$logged_in_actions = array(
			'xen_complete_quest',
			'xen_complete_task',
			'xen_log_habit',
			'xen_purchase_item',
			'xen_equip_item',
			'xen_save_onboarding_step',
			'xen_complete_onboarding',
			'xen_get_notifications',
			'xen_mark_notification_read',
			'xen_mark_all_notifications_read',
			'xen_create_task',
			'xen_delete_task',
			'xen_create_habit',
			'xen_deactivate_habit',
			'xen_get_user_stats',
			'xen_get_leaderboard',
			'xen_get_shop_items',
			'xen_get_daily_quests',
		);

		// Public-safe (read-only)
		$nopriv_actions = array(
			'xen_get_leaderboard',
			'xen_get_shop_items',
		);

		foreach ( $logged_in_actions as $action ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}
		foreach ( $nopriv_actions as $action ) {
			add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
		}
	}

	// ─── Internal Helpers ─────────────────────────────────────────────────

	/** Verify nonce and require login. */
	private function require_auth() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Not authenticated.', 'xen-levelup' ) ), 401 );
		}
		if ( ! check_ajax_referer( 'xen_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'xen-levelup' ) ), 403 );
		}
	}

	/** Verify nonce only (no login check). */
	private function require_nonce() {
		if ( ! check_ajax_referer( 'xen_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'xen-levelup' ) ), 403 );
		}
	}

	/** Shorthand: get and sanitize POST integer. */
	private function post_int( $key, $default = 0 ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) ? (int) $_POST[ $key ] : $default;
	}

	/** Shorthand: get and sanitize POST text. */
	private function post_text( $key, $default = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	/** Shorthand: get and sanitize POST textarea. */
	private function post_textarea( $key, $default = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	// ─── Quest Handlers ───────────────────────────────────────────────────

	public function xen_complete_quest() {
		$this->require_auth();
		$user_id  = get_current_user_id();
		$quest_id = $this->post_int( 'quest_id' );

		$result = xen_levelup()->quests->complete_quest( $quest_id, $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	public function xen_get_daily_quests() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$quests  = xen_levelup()->daily_quests->get_today( $user_id );
		wp_send_json_success( array( 'quests' => $quests ) );
	}

	// ─── Task Handlers ────────────────────────────────────────────────────

	public function xen_create_task() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$data    = array(
			'title'    => $this->post_text( 'title' ),
			'notes'    => $this->post_textarea( 'notes' ),
			'due_date' => $this->post_text( 'due_date' ),
			'priority' => $this->post_text( 'priority' ),
			'category' => $this->post_text( 'category' ),
		);
		$result = xen_levelup()->tasks->create( $user_id, $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'task_id' => $result, 'remaining' => xen_levelup()->tasks->get_remaining_today( $user_id ) ) );
	}

	public function xen_complete_task() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$task_id = $this->post_int( 'task_id' );
		$result  = xen_levelup()->tasks->complete( $task_id, $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	public function xen_delete_task() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$task_id = $this->post_int( 'task_id' );
		$result  = xen_levelup()->tasks->delete( $task_id, $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	// ─── Habit Handlers ───────────────────────────────────────────────────

	public function xen_create_habit() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$data    = array(
			'title'    => $this->post_text( 'title' ),
			'category' => $this->post_text( 'category' ),
			'notes'    => $this->post_textarea( 'notes' ),
		);
		$result = xen_levelup()->habits->create( $user_id, $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'habit_id' => $result ) );
	}

	public function xen_log_habit() {
		$this->require_auth();
		$user_id  = get_current_user_id();
		$habit_id = $this->post_int( 'habit_id' );
		$notes    = $this->post_textarea( 'notes' );
		$result   = xen_levelup()->habits->log( $habit_id, $user_id, $notes );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	public function xen_deactivate_habit() {
		$this->require_auth();
		$user_id  = get_current_user_id();
		$habit_id = $this->post_int( 'habit_id' );
		$result   = xen_levelup()->habits->deactivate( $habit_id, $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	// ─── Shop Handlers ────────────────────────────────────────────────────

	public function xen_get_shop_items() {
		$this->require_nonce();
		$type  = sanitize_key( $this->post_text( 'type', 'all' ) );
		$items = xen_levelup()->shop->get_items( $type );
		wp_send_json_success( array( 'items' => $items ) );
	}

	public function xen_purchase_item() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$item_id = $this->post_int( 'item_id' );
		$result  = xen_levelup()->shop->purchase( $user_id, $item_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	public function xen_equip_item() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$item_id = $this->post_int( 'item_id' );
		$equip   = (bool) $this->post_int( 'equip', 1 );
		$result  = xen_levelup()->shop->equip( $item_id, $user_id, $equip );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	// ─── Onboarding Handlers ─────────────────────────────────────────────

	public function xen_save_onboarding_step() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$step    = $this->post_int( 'step' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_data = isset( $_POST['data'] ) ? $_POST['data'] : array();

		// $raw_data is an array from the AJAX serialized form – walk-sanitize
		$sanitized = array();
		if ( is_array( $raw_data ) ) {
			foreach ( $raw_data as $k => $v ) {
				$sanitized[ sanitize_key( $k ) ] = is_array( $v )
					? array_map( 'sanitize_text_field', $v )
					: sanitize_text_field( wp_unslash( $v ) );
			}
		}

		switch ( $step ) {
			case 1:
				xen_levelup()->onboarding->save_step_1( $user_id, $sanitized );
				break;
			case 2:
				xen_levelup()->onboarding->save_step_2( $user_id, $sanitized );
				break;
			case 3:
				xen_levelup()->onboarding->save_step_3( $user_id, array_values( $sanitized ) );
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid step.', 'xen-levelup' ) ) );
		}

		wp_send_json_success( array( 'step' => $step ) );
	}

	public function xen_complete_onboarding() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$result  = xen_levelup()->onboarding->complete( $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'stats' => $result ) );
	}

	// ─── Notification Handlers ────────────────────────────────────────────

	public function xen_get_notifications() {
		$this->require_auth();
		$user_id     = get_current_user_id();
		$unread_only = (bool) $this->post_int( 'unread_only', 0 );
		$limit       = max( 1, min( 50, $this->post_int( 'limit', 20 ) ) );
		wp_send_json_success( array(
			'notifications' => xen_levelup()->notifications->get( $user_id, $unread_only, $limit ),
			'unread_count'  => xen_levelup()->notifications->unread_count( $user_id ),
		) );
	}

	public function xen_mark_notification_read() {
		$this->require_auth();
		$user_id  = get_current_user_id();
		$notif_id = $this->post_int( 'notification_id' );
		xen_levelup()->notifications->mark_read( $notif_id, $user_id );
		wp_send_json_success( array( 'unread_count' => xen_levelup()->notifications->unread_count( $user_id ) ) );
	}

	public function xen_mark_all_notifications_read() {
		$this->require_auth();
		$user_id = get_current_user_id();
		xen_levelup()->notifications->mark_all_read( $user_id );
		wp_send_json_success( array( 'unread_count' => 0 ) );
	}

	// ─── Stats / Rankings Handlers ────────────────────────────────────────

	public function xen_get_user_stats() {
		$this->require_auth();
		$user_id = get_current_user_id();
		wp_send_json_success( array(
			'stats'     => xen_levelup()->stats->get_all_stats( $user_id ),
			'user_data' => xen_levelup()->user->get_full_data( $user_id ),
		) );
	}

	public function xen_get_leaderboard() {
		$this->require_nonce();
		$period = sanitize_key( $this->post_text( 'period', 'global' ) );
		$limit  = max( 1, min( 100, $this->post_int( 'limit', 50 ) ) );
		$data   = xen_levelup()->rankings->get_leaderboard( $period, 'all', $limit );
		wp_send_json_success( array( 'rankings' => $data ) );
	}
}
