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
			'xen_accept_quest',
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
			'xen_daily_checkin',
			'xen_dismiss_whats_new',
			'xen_update_profile',
			'xen_transfer_currency',
			'xen_get_wallet',
			'xen_get_quest_hub',
			// v1.4.1
			'xen_upload_avatar',
			'xen_convert_task_to_quest',
			'xen_post_activity',
			'xen_get_feed',
			'xen_like_activity',
			'xen_add_comment',
			'xen_get_comments',
			'xen_send_friend_request',
			'xen_accept_friend_request',
			// v1.4.2
			'xen_search_users',
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

	public function xen_accept_quest() {
		$this->require_auth();
		$user_id  = get_current_user_id();
		$quest_id = $this->post_int( 'quest_id' );

		$result = xen_levelup()->quests->accept_quest( $quest_id, $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'quest_id' => $quest_id ) );
	}

	public function xen_get_quest_hub() {
		$this->require_auth();
		$user_id = get_current_user_id();
		wp_send_json_success( array(
			'daily'     => xen_levelup()->daily_quests->get_today( $user_id ),
			'special'   => xen_levelup()->special_quests->get_active( $user_id ),
			'legendary' => xen_levelup()->legendary_quests->get_active( $user_id ),
		) );
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

		$type     = sanitize_key( $this->post_text( 'type', 'all' ) );
		$page     = max( 1, $this->post_int( 'page', 1 ) );
		$per_page = min( 48, max( 4, $this->post_int( 'per_page', 12 ) ) );

		$total = xen_levelup()->shop->count_items( $type, true );
		$items = xen_levelup()->shop->get_items_paged( $type, $page, $per_page );

		// Include user ownership info if logged in
		$owned_ids   = array();
		$equipped_ids = array();
		if ( is_user_logged_in() ) {
			$inventory = xen_levelup()->shop->get_inventory( get_current_user_id() );
			foreach ( $inventory as $inv ) {
				$owned_ids[] = (int) $inv->id;
				if ( $inv->is_equipped ) {
					$equipped_ids[] = (int) $inv->id;
				}
			}
		}

		wp_send_json_success( array(
			'items'        => $items,
			'owned_ids'    => $owned_ids,
			'equipped_ids' => $equipped_ids,
			'total'        => $total,
			'page'         => $page,
			'per_page'     => $per_page,
			'pages'        => max( 1, (int) ceil( $total / $per_page ) ),
		) );
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

	// ─── Daily Check-In Handler ────────────────────────────────────────────

	public function xen_daily_checkin() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$result  = xen_levelup()->daily_checkin->checkin( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Include updated balance in the response
		$result['balance'] = xen_levelup()->currency->get_balance( $user_id );
		wp_send_json_success( $result );
	}

	// ─── What’s New Dismiss Handler ────────────────────────────────────────

	public function xen_dismiss_whats_new() {
		$this->require_auth();
		xen_levelup()->overview->dismiss( get_current_user_id(), XEN_LEVELUP_VERSION );
		wp_send_json_success();
	}
	// ─── Profile Update Handler ─────────────────────────────────────────────────────

	public function xen_update_profile() {
		$this->require_auth();
		$user_id = get_current_user_id();

		$display_name = $this->post_text( 'display_name' );
		$bio          = $this->post_textarea( 'bio' );
		$title        = $this->post_text( 'title' );

		// Update WP display name
		if ( $display_name !== '' ) {
			$result = wp_update_user( array(
				'ID'           => $user_id,
				'display_name' => $display_name,
			) );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
		}

		// Update bio in user meta
		update_user_meta( $user_id, 'xen_bio', $bio );

		// Update current title in profile (validate it's non-empty)
		if ( $title !== '' ) {
			xen_levelup()->user->update_profile( $user_id, array( 'current_title' => $title ) );
		}

		xen_levelup()->user->flush_profile_cache( $user_id );

		wp_send_json_success( array(
			'display_name' => get_userdata( $user_id )->display_name,
			'bio'          => get_user_meta( $user_id, 'xen_bio', true ),
			'title'        => $title,
		) );
	}

	// ─── Currency Transfer Handlers ────────────────────────────────────────────────

	public function xen_transfer_currency() {
		$this->require_auth();
		$sender_id   = get_current_user_id();
		$receiver_id = $this->post_int( 'to_user_id' );
		$amount      = $this->post_int( 'amount' );
		$note        = $this->post_text( 'note' );

		if ( $receiver_id <= 0 || $amount <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid transfer details.', 'xen-levelup' ) ) );
		}
		if ( $receiver_id === $sender_id ) {
			wp_send_json_error( array( 'message' => __( 'You cannot transfer coins to yourself.', 'xen-levelup' ) ) );
		}
		if ( ! get_userdata( $receiver_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Recipient not found.', 'xen-levelup' ) ) );
		}

		$result = xen_levelup()->currency->transfer( $sender_id, $receiver_id, $amount, $note );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	public function xen_get_wallet() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$page    = max( 1, $this->post_int( 'page', 1 ) );
		wp_send_json_success( array(
			'balance'      => xen_levelup()->currency->get_balance( $user_id ),
			'transactions' => xen_levelup()->currency->get_transactions( $user_id, 20 ),
			'transfers'    => xen_levelup()->currency->get_transfer_history( $user_id, 20 ),
		) );
	}

	// ─── Avatar Upload ────────────────────────────────────────────────────

	public function xen_upload_avatar() {
		$this->require_auth();
		$user_id = get_current_user_id();

		if ( empty( $_FILES['avatar'] ) || UPLOAD_ERR_OK !== $_FILES['avatar']['error'] ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded or upload error.', 'xen-levelup' ) ) );
		}

		// Validate mime type
		$allowed = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$finfo   = new \finfo( FILEINFO_MIME_TYPE );
		$mime    = $finfo->file( $_FILES['avatar']['tmp_name'] ); // phpcs:ignore
		if ( ! in_array( $mime, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Only JPEG, PNG, GIF, and WebP images are allowed.', 'xen-levelup' ) ) );
		}

		// Max 2 MB
		if ( $_FILES['avatar']['size'] > 2 * 1024 * 1024 ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => __( 'Image must be 2 MB or smaller.', 'xen-levelup' ) ) );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$overrides = array( 'test_form' => false );
		$uploaded  = wp_handle_upload( $_FILES['avatar'], $overrides ); // phpcs:ignore

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error( array( 'message' => $uploaded['error'] ) );
		}

		$url = esc_url_raw( $uploaded['url'] );
		update_user_meta( $user_id, 'xen_avatar_url', $url );

		wp_send_json_success( array( 'url' => $url ) );
	}

	// ─── Task → Side Quest ────────────────────────────────────────────────

	public function xen_convert_task_to_quest() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$task_id = $this->post_int( 'task_id' );

		$task = xen_levelup()->tasks->get_tasks( $user_id, 'pending' );
		$task = array_values( array_filter( $task, function ( $t ) use ( $task_id ) {
			return (int) $t->id === $task_id;
		} ) );

		if ( empty( $task ) ) {
			wp_send_json_error( array( 'message' => __( 'Task not found or already completed.', 'xen-levelup' ) ) );
		}
		$task = $task[0];

		// Create side quest from task
		$quest_id = xen_levelup()->quests->assign_quest( $user_id, array(
			'title'       => $task->title,
			'description' => $task->description ?: '',
			'category'    => $task->category ?: 'general',
			'difficulty'  => 'medium',
			'quest_type'  => 'special',
			'xp_reward'   => 150,
			'coin_reward' => 30,
			'quest_date'  => current_time( 'Y-m-d' ),
			'expires_at'  => null,
		) );

		if ( is_wp_error( $quest_id ) ) {
			wp_send_json_error( array( 'message' => $quest_id->get_error_message() ) );
		}

		// Mark the original task as converted (completed)
		xen_levelup()->tasks->complete( $task_id, $user_id );

		wp_send_json_success( array(
			'quest_id' => $quest_id,
			'message'  => __( 'Task converted to a Side Quest!', 'xen-levelup' ),
		) );
	}

	// ─── Social: Activity Feed ────────────────────────────────────────────

	public function xen_post_activity() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$content = $this->post_textarea( 'content' );
		if ( ! $content ) {
			wp_send_json_error( array( 'message' => __( 'Post content cannot be empty.', 'xen-levelup' ) ) );
		}
		$id = xen_levelup()->social->post( $user_id, 'custom', $content );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Could not post activity.', 'xen-levelup' ) ) );
		}

		// Fetch the newly-created enriched item to return full data to JS.
		$feed  = xen_levelup()->social->get_global_feed( 1, 0 );
		$item  = ! empty( $feed ) ? $feed[0] : null;

		wp_send_json_success( array( 'activity_id' => $id, 'item' => $item ) );
	}

	public function xen_get_feed() {
		$this->require_auth();
		$user_id = get_current_user_id();
		$mode    = $this->post_text( 'mode', 'friends' );
		$offset  = max( 0, $this->post_int( 'offset', 0 ) );
		$limit   = min( 20, max( 1, $this->post_int( 'limit', 20 ) ) );

		$feed = 'global' === $mode
			? xen_levelup()->social->get_global_feed( $limit, $offset )
			: xen_levelup()->social->get_feed( $user_id, $limit, $offset );

		wp_send_json_success( array(
			'items'    => $feed,
			'has_more' => count( $feed ) >= $limit,
		) );
	}

	public function xen_like_activity() {
		$this->require_auth();
		$user_id     = get_current_user_id();
		$activity_id = $this->post_int( 'activity_id' );
		$result      = xen_levelup()->social->toggle_like( $activity_id, $user_id );
		wp_send_json_success( $result );
	}

	public function xen_add_comment() {
		$this->require_auth();
		$user_id     = get_current_user_id();
		$activity_id = $this->post_int( 'activity_id' );
		$content     = $this->post_textarea( 'content' );
		$result      = xen_levelup()->social->add_comment( $activity_id, $user_id, $content );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'comment_id' => $result ) );
	}

	public function xen_get_comments() {
		$this->require_auth();
		$activity_id = $this->post_int( 'activity_id' );
		$comments    = xen_levelup()->social->get_comments( $activity_id );
		wp_send_json_success( array( 'comments' => $comments ) );
	}

	public function xen_send_friend_request() {
		$this->require_auth();
		$from   = get_current_user_id();
		$to     = $this->post_int( 'user_id' );
		$result = xen_levelup()->social->send_friend_request( $from, $to );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	public function xen_accept_friend_request() {
		$this->require_auth();
		$acceptor    = get_current_user_id();
		$requester   = $this->post_int( 'user_id' );
		$result      = xen_levelup()->social->accept_friend_request( $requester, $acceptor );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success();
	}

	// ── v1.4.2 ────────────────────────────────────────────────────────────

	/**
	 * Search users by display name, user_login, or numeric ID.
	 * Returns lightweight list for the wallet recipient autocomplete.
	 */
	public function xen_search_users() {
		$this->require_auth();
		$current_user_id = get_current_user_id();
		$term            = sanitize_text_field( $this->post_text( 'term' ) );

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		// Numeric ID lookup
		if ( is_numeric( $term ) ) {
			$uid  = (int) $term;
			$user = $uid !== $current_user_id ? get_userdata( $uid ) : false;
			if ( $user ) {
				wp_send_json_success( array( array(
					'id'       => $user->ID,
					'name'     => $user->display_name,
					'username' => $user->user_login,
				) ) );
			}
			wp_send_json_success( array() );
		}

		// Text search by display_name or user_login
		$users = get_users( array(
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'display_name', 'user_login' ),
			'exclude'        => array( $current_user_id ),
			'number'         => 10,
			'fields'         => array( 'ID', 'display_name', 'user_login' ),
		) );

		$results = array();
		foreach ( $users as $u ) {
			$results[] = array(
				'id'       => $u->ID,
				'name'     => $u->display_name,
				'username' => $u->user_login,
			);
		}
		wp_send_json_success( $results );
	}
}
