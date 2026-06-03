<?php
/**
 * Rank Definitions — CRUD for configurable rank tiers tied to the Rebirth system.
 *
 * Ranks are stored in the `xen_rank_definitions` table.
 * The rank assigned to a user is the highest-tier rank whose `rebirth_required`
 * value is ≤ the user's current `rebirth_count`.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Ranks
 */
class Xen_Ranks extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Lookup ───────────────────────────────────────────────────────────

	/**
	 * Return the rank title a user qualifies for based on their rebirth count.
	 * Picks the highest tier whose rebirth_required <= $rebirth_count.
	 *
	 * @param int $rebirth_count Number of rebirths the user has done.
	 * @return string
	 */
	public function title_for_rebirth( $rebirth_count ) {
		$row = $this->db->get_row(
			$this->db->prepare(
				"SELECT title FROM {$this->p}rank_definitions
				 WHERE is_active = 1 AND rebirth_required <= %d
				 ORDER BY rebirth_required DESC
				 LIMIT 1",
				(int) $rebirth_count
			)
		);
		return $row ? $row->title : 'Unranked';
	}

	/**
	 * Return the full rank row a user qualifies for (includes icon, color, etc.).
	 *
	 * @param int $rebirth_count
	 * @return object|null
	 */
	public function rank_for_rebirth( $rebirth_count ) {
		return $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->p}rank_definitions
				 WHERE is_active = 1 AND rebirth_required <= %d
				 ORDER BY rebirth_required DESC
				 LIMIT 1",
				(int) $rebirth_count
			)
		);
	}

	// ─── List ─────────────────────────────────────────────────────────────

	/**
	 * Get all rank definitions, ordered by rebirth_required then sort_order.
	 *
	 * @return array
	 */
	public function get_all() {
		return $this->db->get_results(
			"SELECT * FROM {$this->p}rank_definitions ORDER BY rebirth_required ASC, sort_order ASC"
		);
	}

	/**
	 * Get a single rank by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public function get_rank( $id ) {
		return $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->p}rank_definitions WHERE id = %d",
				(int) $id
			)
		);
	}

	// ─── CRUD ─────────────────────────────────────────────────────────────

	/**
	 * Create a new rank definition.
	 *
	 * @param array $data
	 * @return int|WP_Error Inserted ID or error.
	 */
	public function create_rank( array $data ) {
		$clean = $this->sanitize_rank( $data );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		$id = $this->insert( 'rank_definitions', $clean['fields'], $clean['formats'] );
		return $id ? $id : new \WP_Error( 'db_error', __( 'Could not create rank.', 'xen-levelup' ) );
	}

	/**
	 * Update an existing rank definition.
	 *
	 * @param int   $id
	 * @param array $data
	 * @return true|WP_Error
	 */
	public function update_rank( $id, array $data ) {
		$clean = $this->sanitize_rank( $data );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		$result = $this->update( 'rank_definitions', $clean['fields'], array( 'id' => (int) $id ) );
		return false !== $result ? true : new \WP_Error( 'db_error', __( 'Could not update rank.', 'xen-levelup' ) );
	}

	/**
	 * Delete a rank definition.
	 *
	 * @param int $id
	 * @return true|WP_Error
	 */
	public function delete_rank( $id ) {
		$result = $this->db->delete(
			$this->p . 'rank_definitions',
			array( 'id' => (int) $id ),
			array( '%d' )
		);
		return $result ? true : new \WP_Error( 'db_error', __( 'Could not delete rank.', 'xen-levelup' ) );
	}

	/**
	 * Toggle is_active for a rank.
	 *
	 * @param int $id
	 */
	public function toggle_active( $id ) {
		$this->db->query(
			$this->db->prepare(
				"UPDATE {$this->p}rank_definitions SET is_active = IF(is_active = 1, 0, 1) WHERE id = %d",
				(int) $id
			)
		);
	}

	// ─── Internal ─────────────────────────────────────────────────────────

	/**
	 * Sanitize and validate rank form data.
	 *
	 * @param array $data Raw POST data.
	 * @return array|WP_Error {fields, formats} or WP_Error.
	 */
	private function sanitize_rank( array $data ) {
		$title = sanitize_text_field( wp_unslash( $data['title'] ?? '' ) );
		if ( ! $title ) {
			return new \WP_Error( 'empty_title', __( 'Rank title is required.', 'xen-levelup' ) );
		}

		$color = sanitize_hex_color( $data['color'] ?? '' );

		return array(
			'fields'  => array(
				'title'            => $title,
				'icon'             => sanitize_text_field( wp_unslash( $data['icon'] ?? '' ) ),
				'color'            => $color ?: '',
				'rebirth_required' => max( 0, absint( $data['rebirth_required'] ?? 0 ) ),
				'description'      => sanitize_textarea_field( wp_unslash( $data['description'] ?? '' ) ),
				'sort_order'       => absint( $data['sort_order'] ?? 0 ),
				'is_active'        => isset( $data['is_active'] ) ? 1 : 0,
			),
			'formats' => array( '%s', '%s', '%s', '%d', '%s', '%d', '%d' ),
		);
	}
}
