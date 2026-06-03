<?php
/**
 * Database abstraction layer – typed query helpers for all XEN tables.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Database
 */
class Xen_Database {

	/** @var wpdb */
	protected $db;

	/** @var string Table prefix including xen_ */
	protected $p;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->p  = $wpdb->prefix . 'xen_';
	}

	// ─── Generic Helpers ─────────────────────────────────────────────────

	/**
	 * Return a full table name.
	 *
	 * @param string $table Table slug, e.g. 'user_profiles'.
	 * @return string
	 */
	public function table( $table ) {
		return $this->p . $table;
	}

	/**
	 * Generic SELECT by column.
	 *
	 * @param string $table  Table slug.
	 * @param array  $where  Associative array of column => value.
	 * @return object|null
	 */
	public function get_row( $table, array $where ) {
		$t      = $this->table( $table );
		$sql    = "SELECT * FROM {$t} WHERE 1=1";
		$values = array();

		foreach ( $where as $col => $val ) {
			$sql      .= " AND `{$col}` = %s";
			$values[]  = $val;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->db->get_row( $this->db->prepare( $sql, $values ) );
	}

	/**
	 * Generic SELECT multiple rows.
	 *
	 * @param string $table   Table slug.
	 * @param array  $where   WHERE conditions.
	 * @param string $orderby Optional ORDER BY clause.
	 * @param int    $limit   Optional row limit (0 = no limit).
	 * @return array
	 */
	public function get_rows( $table, array $where = array(), $orderby = '', $limit = 0 ) {
		$t      = $this->table( $table );
		$sql    = "SELECT * FROM {$t} WHERE 1=1";
		$values = array();

		foreach ( $where as $col => $val ) {
			$sql      .= " AND `{$col}` = %s";
			$values[]  = $val;
		}

		if ( $orderby ) {
			$sql .= ' ORDER BY ' . esc_sql( $orderby );
		}
		if ( $limit > 0 ) {
			$sql .= ' LIMIT ' . (int) $limit;
		}

		if ( empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return $this->db->get_results( $sql );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->db->get_results( $this->db->prepare( $sql, $values ) );
	}

	/**
	 * Insert a row.
	 *
	 * @param string $table  Table slug.
	 * @param array  $data   Column => value.
	 * @param array  $format Optional sprintf formats.
	 * @return int|false Insert ID or false on failure.
	 */
	public function insert( $table, array $data, array $format = array() ) {
		$result = $this->db->insert( $this->table( $table ), $data, $format ?: null );
		return $result ? $this->db->insert_id : false;
	}

	/**
	 * Update a row.
	 *
	 * @param string $table  Table slug.
	 * @param array  $data   Column => value to update.
	 * @param array  $where  WHERE column => value.
	 * @return int|false Number of rows updated or false.
	 */
	public function update( $table, array $data, array $where ) {
		return $this->db->update( $this->table( $table ), $data, $where );
	}

	/**
	 * Delete rows.
	 *
	 * @param string $table Table slug.
	 * @param array  $where WHERE column => value.
	 * @return int|false
	 */
	public function delete( $table, array $where ) {
		return $this->db->delete( $this->table( $table ), $where );
	}

	/**
	 * Get a single scalar value.
	 *
	 * @param string $sql  Prepared SQL.
	 * @param array  $args Values.
	 * @return mixed
	 */
	public function get_var( $sql, array $args = array() ) {
		if ( $args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $this->db->prepare( $sql, $args );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->db->get_var( $sql );
	}

	/**
	 * Run a custom query (SELECT).
	 *
	 * @param string $sql  Raw SQL.
	 * @param array  $args Prepare args.
	 * @return array
	 */
	public function query( $sql, array $args = array() ) {
		if ( $args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $this->db->prepare( $sql, $args );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->db->get_results( $sql );
	}

	/**
	 * Check if a row exists.
	 *
	 * @param string $table Table slug.
	 * @param array  $where WHERE conditions.
	 * @return bool
	 */
	public function row_exists( $table, array $where ) {
		return null !== $this->get_row( $table, $where );
	}

	// ─── Cached Queries ───────────────────────────────────────────────────

	/**
	 * Fetch and cache a user profile row.
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null
	 */
	public function get_user_profile( $user_id ) {
		$cache_key = 'xen_profile_' . (int) $user_id;
		$cached    = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$row = $this->get_row( 'user_profiles', array( 'user_id' => (int) $user_id ) );
		wp_cache_set( $cache_key, $row, '', 300 ); // 5 minutes
		return $row;
	}

	/**
	 * Invalidate the user profile cache.
	 *
	 * @param int $user_id WP user ID.
	 */
	public function flush_profile_cache( $user_id ) {
		wp_cache_delete( 'xen_profile_' . (int) $user_id );
	}

	/**
	 * Fetch user stats row (cached).
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null
	 */
	public function get_user_stats( $user_id ) {
		$cache_key = 'xen_stats_' . (int) $user_id;
		$cached    = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$row = $this->get_row( 'user_stats', array( 'user_id' => (int) $user_id ) );
		wp_cache_set( $cache_key, $row, '', 300 );
		return $row;
	}

	/**
	 * Fetch user life trees (cached).
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null
	 */
	public function get_user_life_trees( $user_id ) {
		$cache_key = 'xen_life_trees_' . (int) $user_id;
		$cached    = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$row = $this->get_row( 'user_life_trees', array( 'user_id' => (int) $user_id ) );
		wp_cache_set( $cache_key, $row, '', 300 );
		return $row;
	}
}
