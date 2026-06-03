<?php
/**
 * Shop system – item catalog, purchasing, and inventory management.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Shop
 */
class Xen_Shop extends Xen_Database {

	/** @var string[] Valid item types */
	const ITEM_TYPES = array( 'frame', 'border', 'name_color', 'title', 'theme', 'badge' );

	public function __construct() {
		parent::__construct();
	}

	// ─── Catalog ──────────────────────────────────────────────────────────

	/**
	 * Get all active shop items, optionally filtered by type.
	 *
	 * @param string $type Item type filter or 'all'.
	 * @return array
	 */
	public function get_items( $type = 'all' ) {
		$where = array( 'is_active' => 1 );
		if ( $type && 'all' !== $type && in_array( $type, self::ITEM_TYPES, true ) ) {
			$where['item_type'] = $type;
		}
		return $this->get_rows( 'shop_items', $where, 'sort_order ASC, price ASC' );
	}

	/**
	 * Get a single shop item by ID.
	 *
	 * @param int $item_id Shop item ID.
	 * @return object|null
	 */
	public function get_item( $item_id ) {
		return $this->get_row( 'shop_items', array( 'id' => (int) $item_id, 'is_active' => 1 ) );
	}

	// ─── Purchase ─────────────────────────────────────────────────────────

	/**
	 * Purchase an item for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $item_id Shop item ID.
	 * @return array|WP_Error
	 */
	public function purchase( $user_id, $item_id ) {
		$user_id = (int) $user_id;
		$item_id = (int) $item_id;

		$item = $this->get_item( $item_id );
		if ( ! $item ) {
			return new WP_Error( 'not_found', __( 'Item not found.', 'xen-levelup' ) );
		}

		// Already owned?
		if ( $this->row_exists( 'user_inventory', array( 'user_id' => $user_id, 'item_id' => $item_id ) ) ) {
			return new WP_Error( 'already_owned', __( 'You already own this item.', 'xen-levelup' ) );
		}

		// Spend coins
		$spend = xen_levelup()->currency->spend(
			$user_id,
			(int) $item->price,
			'purchase',
			sprintf( __( 'Purchased: %s', 'xen-levelup' ), $item->title ),
			$item_id,
			'shop_item'
		);

		if ( ! $spend['success'] ) {
			return new WP_Error( 'insufficient_coins', $spend['message'] );
		}

		// Add to inventory
		$this->insert(
			'user_inventory',
			array(
				'user_id' => $user_id,
				'item_id' => $item_id,
			),
			array( '%d', '%d' )
		);

		// Notification
		xen_levelup()->notifications->add(
			$user_id,
			'purchase',
			sprintf(
				/* translators: %s = item title */
				__( '🛒 Purchased: %s', 'xen-levelup' ),
				$item->title
			),
			sprintf(
				/* translators: %s = item title */
				__( 'You have successfully purchased "%s". Equip it from your profile!', 'xen-levelup' ),
				$item->title
			),
			array( 'item_id' => $item_id )
		);

		return array(
			'success' => true,
			'item'    => $item,
			'balance' => $spend['balance'],
		);
	}

	// ─── Inventory ────────────────────────────────────────────────────────

	/**
	 * Get all items owned by a user.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $type    Item type filter or 'all'.
	 * @return array
	 */
	public function get_inventory( $user_id, $type = 'all' ) {
		$inv   = $this->table( 'user_inventory' );
		$items = $this->table( 'shop_items' );
		$sql   = "SELECT i.*, inv.is_equipped, inv.purchased_at
		          FROM {$items} i
		          INNER JOIN {$inv} inv ON inv.item_id = i.id
		          WHERE inv.user_id = %d";
		$args  = array( (int) $user_id );

		if ( $type && 'all' !== $type ) {
			$sql   .= ' AND i.item_type = %s';
			$args[] = sanitize_key( $type );
		}
		$sql .= ' ORDER BY inv.purchased_at DESC';

		return $this->query( $sql, $args );
	}

	/**
	 * Equip or un-equip an item.
	 *
	 * Equipping a new item of the same type will un-equip the previous one.
	 *
	 * @param int  $item_id   Shop item ID.
	 * @param int  $user_id   WP user ID.
	 * @param bool $equip     True to equip, false to un-equip.
	 * @return array|WP_Error
	 */
	public function equip( $item_id, $user_id, $equip = true ) {
		$user_id = (int) $user_id;
		$item_id = (int) $item_id;

		$inv_row = $this->get_row( 'user_inventory', array( 'user_id' => $user_id, 'item_id' => $item_id ) );
		if ( ! $inv_row ) {
			return new WP_Error( 'not_owned', __( 'You do not own this item.', 'xen-levelup' ) );
		}

		$item = $this->get_item( $item_id );
		if ( ! $item ) {
			return new WP_Error( 'not_found', __( 'Item not found.', 'xen-levelup' ) );
		}

		if ( $equip ) {
			// Un-equip any other item of the same type
			$this->unequip_type( $user_id, $item->item_type );

			$this->update( 'user_inventory', array( 'is_equipped' => 1 ), array( 'user_id' => $user_id, 'item_id' => $item_id ) );

			// Update profile field based on type
			$this->apply_equipped_item( $user_id, $item );
		} else {
			$this->update( 'user_inventory', array( 'is_equipped' => 0 ), array( 'user_id' => $user_id, 'item_id' => $item_id ) );
			$this->clear_profile_item( $user_id, $item );
		}

		return array( 'success' => true, 'equipped' => $equip );
	}

	// ─── Private ─────────────────────────────────────────────────────────

	/**
	 * Un-equip all items of a given type for a user.
	 *
	 * @param int    $user_id   WP user ID.
	 * @param string $item_type Item type.
	 */
	private function unequip_type( $user_id, $item_type ) {
		global $wpdb;
		$inv   = $wpdb->prefix . 'xen_user_inventory';
		$items = $wpdb->prefix . 'xen_shop_items';
		$wpdb->query( $wpdb->prepare( // phpcs:ignore
			"UPDATE {$inv} inv
			 INNER JOIN {$items} i ON i.id = inv.item_id
			 SET inv.is_equipped = 0
			 WHERE inv.user_id = %d AND i.item_type = %s",
			$user_id,
			$item_type
		) );
	}

	/**
	 * Apply equipped item data to the user's profile.
	 *
	 * @param int    $user_id WP user ID.
	 * @param object $item    Shop item row.
	 */
	private function apply_equipped_item( $user_id, $item ) {
		$data = $item->item_data ? json_decode( $item->item_data, true ) : array();
		switch ( $item->item_type ) {
			case 'frame':
				xen_levelup()->user->update_profile( $user_id, array( 'profile_frame' => $data['css_class'] ?? '' ) );
				break;
			case 'name_color':
				xen_levelup()->user->update_profile( $user_id, array( 'name_color' => $data['color'] ?? '' ) );
				break;
			case 'title':
				xen_levelup()->user->update_profile( $user_id, array( 'current_title' => $data['text'] ?? '' ) );
				break;
		}
	}

	/**
	 * Clear item data from user profile when un-equipped.
	 *
	 * @param int    $user_id WP user ID.
	 * @param object $item    Shop item row.
	 */
	private function clear_profile_item( $user_id, $item ) {
		switch ( $item->item_type ) {
			case 'frame':
				xen_levelup()->user->update_profile( $user_id, array( 'profile_frame' => '' ) );
				break;
			case 'name_color':
				xen_levelup()->user->update_profile( $user_id, array( 'name_color' => '' ) );
				break;
			case 'title':
				xen_levelup()->user->update_profile( $user_id, array( 'current_title' => '' ) );
				break;
		}
	}
}
