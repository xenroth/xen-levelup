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

	// ─── Pagination & Count ──────────────────────────────────────────────

	/**
	 * Count shop items with optional filters.
	 *
	 * @param string    $type        Item type or 'all'.
	 * @param bool|null $active_only true = active only, false = inactive only, null = all.
	 * @param string    $search      Partial title search.
	 * @return int
	 */
	public function count_items( $type = 'all', $active_only = true, $search = '' ) {
		$table = $this->table( 'shop_items' );
		$sql   = "SELECT COUNT(*) FROM {$table} WHERE 1=1";
		$args  = array();

		if ( null !== $active_only ) {
			$sql .= $active_only ? ' AND is_active = 1' : ' AND is_active = 0';
		}
		if ( $type && 'all' !== $type ) {
			$sql   .= ' AND item_type = %s';
			$args[] = sanitize_key( $type );
		}
		if ( $search ) {
			$sql   .= ' AND title LIKE %s';
			$args[] = '%' . $this->db->esc_like( $search ) . '%';
		}

		return (int) $this->get_var( $sql, $args );
	}

	/**
	 * Get a paginated list of shop items.
	 *
	 * @param string    $type        Item type or 'all'.
	 * @param int       $page        1-based page number.
	 * @param int       $per_page    Items per page.
	 * @param string    $search      Partial title search.
	 * @param bool|null $active_only true = active only, false = inactive only, null = all.
	 * @return array
	 */
	public function get_items_paged( $type = 'all', $page = 1, $per_page = 12, $search = '', $active_only = true ) {
		$table  = $this->table( 'shop_items' );
		$offset = ( max( 1, (int) $page ) - 1 ) * (int) $per_page;
		$sql    = "SELECT * FROM {$table} WHERE 1=1";
		$args   = array();

		if ( null !== $active_only ) {
			$sql .= $active_only ? ' AND is_active = 1' : ' AND is_active = 0';
		}
		if ( $type && 'all' !== $type ) {
			$sql   .= ' AND item_type = %s';
			$args[] = sanitize_key( $type );
		}
		if ( $search ) {
			$sql   .= ' AND title LIKE %s';
			$args[] = '%' . $this->db->esc_like( $search ) . '%';
		}
		$sql   .= ' ORDER BY sort_order ASC, price ASC LIMIT %d OFFSET %d';
		$args[] = (int) $per_page;
		$args[] = $offset;

		return $this->query( $sql, $args );
	}

	/**
	 * Get a single item by ID regardless of active status (admin use).
	 *
	 * @param int $item_id Item ID.
	 * @return object|null
	 */
	public function get_item_any( $item_id ) {
		$table   = $this->table( 'shop_items' );
		$results = $this->query(
			"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
			array( (int) $item_id )
		);
		return $results ? $results[0] : null;
	}

	// ─── Admin CRUD ──────────────────────────────────────────────────────

	/**
	 * Create a new shop item.
	 *
	 * @param array $data Raw item data (unsanitized).
	 * @return int|WP_Error New item ID or error.
	 */
	public function create_item( array $data ) {
		$sanitized = $this->sanitize_item_data( $data );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}
		$id = $this->insert( 'shop_items', $sanitized );
		if ( ! $id ) {
			return new WP_Error( 'db_error', __( 'Failed to create item.', 'xen-levelup' ) );
		}
		return $id;
	}

	/**
	 * Update an existing shop item.
	 *
	 * @param int   $item_id Item ID.
	 * @param array $data    Raw item data (unsanitized).
	 * @return true|WP_Error
	 */
	public function update_item( $item_id, array $data ) {
		$sanitized = $this->sanitize_item_data( $data );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}
		$result = $this->update( 'shop_items', $sanitized, array( 'id' => (int) $item_id ) );
		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to update item.', 'xen-levelup' ) );
		}
		return true;
	}

	/**
	 * Hard-delete a shop item and its inventory records.
	 *
	 * @param int $item_id Item ID.
	 * @return true|WP_Error
	 */
	public function delete_item( $item_id ) {
		$result = $this->delete( 'shop_items', array( 'id' => (int) $item_id ) );
		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to delete item.', 'xen-levelup' ) );
		}
		$this->delete( 'user_inventory', array( 'item_id' => (int) $item_id ) );
		return true;
	}

	/**
	 * Toggle the is_active flag for a shop item.
	 *
	 * @param int $item_id Item ID.
	 * @return true|WP_Error
	 */
	public function toggle_active( $item_id ) {
		$item = $this->get_item_any( $item_id );
		if ( ! $item ) {
			return new WP_Error( 'not_found', __( 'Item not found.', 'xen-levelup' ) );
		}
		$this->update( 'shop_items', array( 'is_active' => $item->is_active ? 0 : 1 ), array( 'id' => (int) $item_id ) );
		return true;
	}

	/**
	 * Sanitize and validate item data for insert/update.
	 *
	 * @param array $data Raw input.
	 * @return array|WP_Error
	 */
	private function sanitize_item_data( array $data ) {
		$title = sanitize_text_field( wp_unslash( $data['title'] ?? '' ) );
		if ( ! $title ) {
			return new WP_Error( 'missing_title', __( 'Title is required.', 'xen-levelup' ) );
		}

		$item_type = sanitize_key( $data['item_type'] ?? '' );
		if ( ! in_array( $item_type, self::ITEM_TYPES, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid item type.', 'xen-levelup' ) );
		}

		$item_data_raw = wp_unslash( $data['item_data'] ?? '' );
		if ( $item_data_raw ) {
			$decoded = json_decode( $item_data_raw, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_Error( 'invalid_json', __( 'Item Data must be valid JSON.', 'xen-levelup' ) );
			}
			$item_data = wp_json_encode( $decoded );
		} else {
			$item_data = null;
		}

		return array(
			'title'       => $title,
			'item_type'   => $item_type,
			'description' => sanitize_textarea_field( wp_unslash( $data['description'] ?? '' ) ),
			'price'       => max( 0, (int) ( $data['price'] ?? 0 ) ),
			'image_url'   => esc_url_raw( wp_unslash( $data['image_url'] ?? '' ) ),
			'item_data'   => $item_data,
			'sort_order'  => (int) ( $data['sort_order'] ?? 0 ),
			'is_premium'  => empty( $data['is_premium'] ) ? 0 : 1,
			'is_active'   => empty( $data['is_active'] ) ? 0 : 1,
		);
	}
}
