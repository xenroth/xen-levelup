<?php
/**
 * Notifications – create, retrieve, mark-read, dismiss.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Notifications
 */
class Xen_Notifications extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Create ───────────────────────────────────────────────────────────

	/**
	 * Add a notification for a user.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $type    Notification type (level_up, quest, achievement, …).
	 * @param string $title   Short title.
	 * @param string $message Longer message.
	 * @param array  $data    Optional extra data (stored as JSON).
	 * @return int|false Notification ID or false.
	 */
	public function add( $user_id, $type, $title, $message = '', array $data = array() ) {
		if ( ! get_option( 'xen_levelup_enable_notifications', 1 ) ) {
			return false;
		}

		return $this->insert(
			'notifications',
			array(
				'user_id' => (int) $user_id,
				'type'    => sanitize_key( $type ),
				'title'   => sanitize_text_field( $title ),
				'message' => sanitize_textarea_field( $message ),
				'data'    => ! empty( $data ) ? wp_json_encode( $data ) : null,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get notifications for a user.
	 *
	 * @param int  $user_id    WP user ID.
	 * @param bool $unread_only Return only unread.
	 * @param int  $limit      Max rows.
	 * @return array
	 */
	public function get( $user_id, $unread_only = false, $limit = 20 ) {
		$t    = $this->table( 'notifications' );
		$sql  = "SELECT * FROM {$t} WHERE user_id = %d";
		$args = array( (int) $user_id );

		if ( $unread_only ) {
			$sql .= ' AND is_read = 0';
		}
		$sql .= ' ORDER BY created_at DESC LIMIT %d';
		$args[] = (int) $limit;

		return $this->query( $sql, $args );
	}

	/**
	 * Count unread notifications for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function unread_count( $user_id ) {
		$t = $this->table( 'notifications' );
		return (int) $this->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE user_id = %d AND is_read = 0",
			array( (int) $user_id )
		);
	}

	// ─── Mark Read ────────────────────────────────────────────────────────

	/**
	 * Mark a single notification as read.
	 *
	 * @param int $notif_id Notification ID.
	 * @param int $user_id  WP user ID (ownership check).
	 * @return bool
	 */
	public function mark_read( $notif_id, $user_id ) {
		return (bool) $this->update(
			'notifications',
			array( 'is_read' => 1 ),
			array( 'id' => (int) $notif_id, 'user_id' => (int) $user_id )
		);
	}

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id WP user ID.
	 */
	public function mark_all_read( $user_id ) {
		global $wpdb;
		$t = $wpdb->prefix . 'xen_notifications';
		$wpdb->update( $t, array( 'is_read' => 1 ), array( 'user_id' => (int) $user_id ) ); // phpcs:ignore
	}

	// ─── Cleanup ─────────────────────────────────────────────────────────

	/**
	 * Remove notifications older than 30 days (cron cleanup).
	 */
	public function prune_old() {
		global $wpdb;
		$t      = $wpdb->prefix . 'xen_notifications';
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$t} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore
	}
}
