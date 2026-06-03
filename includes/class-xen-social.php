<?php
/**
 * Social system — activity feed, reactions, comments, friends.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Social
 */
class Xen_Social extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Feed: Post ───────────────────────────────────────────────────────

	/**
	 * Post an activity entry to the global feed.
	 *
	 * @param int    $user_id
	 * @param string $type     e.g. 'checkin', 'quest', 'task', 'joined', 'custom'
	 * @param string $content  Human-readable text.
	 * @param array  $meta     Optional extra data (JSON-encoded).
	 * @return int|false  Inserted row ID or false on failure.
	 */
	public function post( $user_id, $type, $content, array $meta = array() ) {
		return $this->insert(
			'activity_feed',
			array(
				'user_id'   => (int) $user_id,
				'type'      => sanitize_key( $type ),
				'content'   => sanitize_textarea_field( $content ),
				'meta_data' => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	// ─── Feed: Retrieve ───────────────────────────────────────────────────

	/**
	 * Get the global activity feed (everyone, newest first).
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function get_global_feed( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table = $this->table( 'activity_feed' );
		$limit  = min( 50, max( 1, (int) $limit ) );
		$offset = max( 0, (int) $offset );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.*, u.display_name
				 FROM {$table} f
				 LEFT JOIN {$wpdb->users} u ON u.ID = f.user_id
				 ORDER BY f.created_at DESC
				 LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		return $this->enrich_feed( $rows );
	}

	/**
	 * Get the friend-filtered feed for a user (own posts + friends' posts).
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function get_feed( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$feed_table    = $this->table( 'activity_feed' );
		$friends_table = $this->table( 'friends' );
		$user_id = (int) $user_id;
		$limit   = min( 50, max( 1, (int) $limit ) );
		$offset  = max( 0, (int) $offset );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.*, u.display_name
				 FROM {$feed_table} f
				 LEFT JOIN {$wpdb->users} u ON u.ID = f.user_id
				 WHERE f.user_id = %d
				    OR f.user_id IN (
				         SELECT fr.friend_id
				         FROM {$friends_table} fr
				         WHERE fr.user_id = %d AND fr.status = 'accepted'
				       )
				 ORDER BY f.created_at DESC
				 LIMIT %d OFFSET %d",
				$user_id,
				$user_id,
				$limit,
				$offset
			)
		);

		return $this->enrich_feed( $rows );
	}

	/**
	 * Enrich feed rows with reaction/comment counts and current-user state.
	 *
	 * @param array $rows
	 * @return array
	 */
	private function enrich_feed( array $rows ) {
		if ( ! $rows ) {
			return array();
		}
		global $wpdb;
		$current_uid     = get_current_user_id();
		$react_table     = $this->table( 'activity_reactions' );
		$comments_table  = $this->table( 'activity_comments' );

		$ids = array_map( 'intval', wp_list_pluck( $rows, 'id' ) );
		$ids_in = implode( ',', $ids );

		// Reaction counts
		$react_counts = $wpdb->get_results(
			"SELECT activity_id, COUNT(*) AS cnt FROM {$react_table}
			 WHERE activity_id IN ({$ids_in}) GROUP BY activity_id"
		);
		$react_map = array();
		foreach ( $react_counts as $r ) {
			$react_map[ $r->activity_id ] = (int) $r->cnt;
		}

		// Comment counts
		$comment_counts = $wpdb->get_results(
			"SELECT activity_id, COUNT(*) AS cnt FROM {$comments_table}
			 WHERE activity_id IN ({$ids_in}) GROUP BY activity_id"
		);
		$comment_map = array();
		foreach ( $comment_counts as $c ) {
			$comment_map[ $c->activity_id ] = (int) $c->cnt;
		}

		// Liked by current user
		$liked_ids = array();
		if ( $current_uid ) {
			$liked = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT activity_id FROM {$react_table}
					 WHERE user_id = %d AND activity_id IN ({$ids_in})",
					$current_uid
				)
			);
			$liked_ids = array_map( 'intval', $liked );
		}

		foreach ( $rows as $row ) {
			$row->like_count    = $react_map[ $row->id ]   ?? 0;
			$row->comment_count = $comment_map[ $row->id ] ?? 0;
			$row->liked_by_me   = in_array( (int) $row->id, $liked_ids, true );
			$row->avatar_url    = get_avatar_url( (int) $row->user_id, array( 'size' => 48 ) );
			$row->meta_data     = $row->meta_data ? json_decode( $row->meta_data, true ) : array();
			$row->time_diff     = human_time_diff( strtotime( $row->created_at ), current_time( 'timestamp' ) );
		}

		return $rows;
	}

	// ─── Reactions (Likes) ────────────────────────────────────────────────

	/**
	 * Toggle a like on an activity. Returns new like count.
	 *
	 * @param int $activity_id
	 * @param int $user_id
	 * @return array {liked: bool, count: int}
	 */
	public function toggle_like( $activity_id, $user_id ) {
		global $wpdb;
		$table       = $this->table( 'activity_reactions' );
		$activity_id = (int) $activity_id;
		$user_id     = (int) $user_id;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			)
		);

		if ( $exists ) {
			$wpdb->delete( $table, array( 'activity_id' => $activity_id, 'user_id' => $user_id ), array( '%d', '%d' ) );
			$liked = false;
		} else {
			$wpdb->insert( $table, array( 'activity_id' => $activity_id, 'user_id' => $user_id, 'reaction' => 'like' ), array( '%d', '%d', '%s' ) );
			$liked = true;
		}

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE activity_id = %d", $activity_id ) );

		return array( 'liked' => $liked, 'count' => $count );
	}

	// ─── Comments ─────────────────────────────────────────────────────────

	/**
	 * Add a comment to an activity.
	 *
	 * @param int    $activity_id
	 * @param int    $user_id
	 * @param string $content
	 * @return int|WP_Error Comment ID or error.
	 */
	public function add_comment( $activity_id, $user_id, $content ) {
		$content = sanitize_textarea_field( wp_unslash( $content ) );
		if ( ! $content ) {
			return new \WP_Error( 'empty', __( 'Comment cannot be empty.', 'xen-levelup' ) );
		}
		if ( mb_strlen( $content ) > 500 ) {
			return new \WP_Error( 'too_long', __( 'Comment must be 500 characters or fewer.', 'xen-levelup' ) );
		}

		$id = $this->insert(
			'activity_comments',
			array(
				'activity_id' => (int) $activity_id,
				'user_id'     => (int) $user_id,
				'content'     => $content,
			),
			array( '%d', '%d', '%s' )
		);

		if ( ! $id ) {
			return new \WP_Error( 'db_error', __( 'Could not save comment.', 'xen-levelup' ) );
		}
		return $id;
	}

	/**
	 * Get comments for an activity.
	 *
	 * @param int $activity_id
	 * @param int $limit
	 * @return array
	 */
	public function get_comments( $activity_id, $limit = 10 ) {
		global $wpdb;
		$table = $this->table( 'activity_comments' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, u.display_name
				 FROM {$table} c
				 LEFT JOIN {$wpdb->users} u ON u.ID = c.user_id
				 WHERE c.activity_id = %d
				 ORDER BY c.created_at ASC
				 LIMIT %d",
				(int) $activity_id,
				min( 50, max( 1, (int) $limit ) )
			)
		);
		foreach ( $rows as $row ) {
			$row->avatar_url = get_avatar_url( (int) $row->user_id, array( 'size' => 40 ) );
		}
		return $rows;
	}

	// ─── Friends ──────────────────────────────────────────────────────────

	/**
	 * Send a friend request.
	 *
	 * @param int $from_user_id
	 * @param int $to_user_id
	 * @return true|WP_Error
	 */
	public function send_friend_request( $from_user_id, $to_user_id ) {
		global $wpdb;
		$from = (int) $from_user_id;
		$to   = (int) $to_user_id;

		if ( $from === $to ) {
			return new \WP_Error( 'self', __( 'You cannot add yourself as a friend.', 'xen-levelup' ) );
		}

		$table    = $this->table( 'friends' );
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
				$from, $to, $to, $from
			)
		);
		if ( $existing ) {
			return new \WP_Error( 'exists', __( 'Friend request already sent or already friends.', 'xen-levelup' ) );
		}

		$this->insert( 'friends', array( 'user_id' => $from, 'friend_id' => $to, 'status' => 'pending' ), array( '%d', '%d', '%s' ) );
		return true;
	}

	/**
	 * Accept a pending friend request.
	 *
	 * @param int $requester_id  Who sent the request.
	 * @param int $acceptor_id   Who is accepting.
	 * @return true|WP_Error
	 */
	public function accept_friend_request( $requester_id, $acceptor_id ) {
		global $wpdb;
		$table = $this->table( 'friends' );
		$rows  = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'accepted' WHERE user_id = %d AND friend_id = %d AND status = 'pending'",
				(int) $requester_id,
				(int) $acceptor_id
			)
		);
		if ( ! $rows ) {
			return new \WP_Error( 'not_found', __( 'No pending friend request found.', 'xen-levelup' ) );
		}
		// Create reverse row for symmetric lookups
		$this->insert(
			'friends',
			array( 'user_id' => (int) $acceptor_id, 'friend_id' => (int) $requester_id, 'status' => 'accepted' ),
			array( '%d', '%d', '%s' )
		);
		return true;
	}

	/**
	 * Get a user's accepted friends.
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_friends( $user_id ) {
		global $wpdb;
		$table = $this->table( 'friends' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.friend_id AS user_id, u.display_name
				 FROM {$table} f
				 LEFT JOIN {$wpdb->users} u ON u.ID = f.friend_id
				 WHERE f.user_id = %d AND f.status = 'accepted'",
				(int) $user_id
			)
		);
	}

	/**
	 * Get pending friend requests for a user (requests others sent to them).
	 *
	 * @param int $user_id
	 * @return array
	 */
	public function get_pending_requests( $user_id ) {
		global $wpdb;
		$table = $this->table( 'friends' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.user_id AS requester_id, u.display_name
				 FROM {$table} f
				 LEFT JOIN {$wpdb->users} u ON u.ID = f.user_id
				 WHERE f.friend_id = %d AND f.status = 'pending'",
				(int) $user_id
			)
		);
	}

	// ─── Shortcode ────────────────────────────────────────────────────────

	/**
	 * Render [gamified_feed] shortcode.
	 *
	 * @param array $atts
	 * @return string HTML
	 */
	public function shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="xen-login-prompt">' . esc_html__( 'Please log in to view the activity feed.', 'xen-levelup' ) . '</p>';
		}
		$atts = shortcode_atts( array(
			'mode'  => 'friends', // 'friends' or 'global'
			'limit' => 20,
		), $atts, 'gamified_feed' );

		$user_id = get_current_user_id();
		$limit   = max( 1, min( 50, (int) $atts['limit'] ) );
		$feed    = 'global' === $atts['mode']
			? $this->get_global_feed( $limit )
			: $this->get_feed( $user_id, $limit );

		$friends         = $this->get_friends( $user_id );
		$pending_requests = $this->get_pending_requests( $user_id );

		ob_start();
		include XEN_LEVELUP_PLUGIN_DIR . 'public/views/feed.php';
		return ob_get_clean();
	}

	// ─── Game Event Hooks ─────────────────────────────────────────────────

	/**
	 * Called on `xen_daily_checkin` action.
	 */
	public function on_checkin( $user_id, $streak, $xp, $coins ) {
		$user = get_userdata( (int) $user_id );
		$name = $user ? $user->display_name : __( 'A hunter', 'xen-levelup' );
		$this->post(
			$user_id,
			'checkin',
			sprintf(
				/* translators: 1: name, 2: streak days */
				__( '%1$s checked in! Day %2$d streak 🔥', 'xen-levelup' ),
				$name,
				$streak
			),
			array( 'streak' => $streak, 'xp' => $xp, 'coins' => $coins )
		);
	}

	/**
	 * Called on `xen_task_completed` action.
	 */
	public function on_task_complete( $user_id, $data ) {
		$user  = get_userdata( (int) $user_id );
		$name  = $user ? $user->display_name : __( 'A hunter', 'xen-levelup' );
		$title = $data['task']->title ?? __( 'a task', 'xen-levelup' );
		$this->post(
			$user_id,
			'task',
			sprintf(
				/* translators: 1: name, 2: task title */
				__( '%1$s completed task: %2$s ✅', 'xen-levelup' ),
				$name,
				$title
			),
			array( 'task_title' => $title )
		);
	}

	/**
	 * Called on `xen_quest_completed` action.
	 */
	public function on_quest_complete( $user_id, $data ) {
		$user  = get_userdata( (int) $user_id );
		$name  = $user ? $user->display_name : __( 'A hunter', 'xen-levelup' );
		$title = $data['title'] ?? __( 'a quest', 'xen-levelup' );
		$type  = $data['quest_type'] ?? 'quest';
		$emoji = ( 'legendary' === $type ) ? '⚡' : '⚔️';
		$this->post(
			$user_id,
			'quest',
			sprintf(
				/* translators: 1: name, 2: quest title, 3: emoji */
				__( '%1$s completed quest: %2$s %3$s', 'xen-levelup' ),
				$name,
				$title,
				$emoji
			),
			array( 'quest_title' => $title, 'quest_type' => $type )
		);
	}

	/**
	 * Called on `xen_onboarding_complete` action.
	 */
	public function on_onboarding_complete( $user_id ) {
		$user = get_userdata( (int) $user_id );
		$name = $user ? $user->display_name : __( 'A hunter', 'xen-levelup' );
		$this->post(
			$user_id,
			'joined',
			sprintf(
				/* translators: 1: name */
				__( '%s joined the system! Welcome, Hunter! 🎮', 'xen-levelup' ),
				$name
			)
		);
	}

	/**
	 * Post a rebirth event to the activity feed.
	 *
	 * @param int    $user_id
	 * @param int    $rebirth_count New rebirth count.
	 * @param string $new_rank      New rank title.
	 */
	public function on_rebirth( $user_id, $rebirth_count, $new_rank ) {
		$user = get_userdata( (int) $user_id );
		$name = $user ? $user->display_name : __( 'A hunter', 'xen-levelup' );
		$this->post(
			$user_id,
			'rebirth',
			sprintf(
				/* translators: 1: name, 2: rebirth number, 3: rank title */
				__( '🔄 %1$s has been REBORN (#%2$d)! Now a %3$s — Level reset to 1. Arise, Hunter!', 'xen-levelup' ),
				$name,
				$rebirth_count,
				$new_rank
			),
			array( 'rebirth_count' => $rebirth_count, 'new_rank' => $new_rank )
		);
	}
}
