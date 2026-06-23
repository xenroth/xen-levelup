<?php
/**
 * User management – profile creation, retrieval, and updates.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_User
 */
class Xen_User extends Xen_Database {

	public function __construct() {
		parent::__construct();
		add_action( 'user_register', array( $this, 'on_user_register' ) );
		add_action( 'wp_login',      array( $this, 'on_user_login' ), 10, 2 );
	}

	// ─── Hooks ────────────────────────────────────────────────────────────

	/**
	 * Create a new XEN profile when a WordPress user is registered.
	 *
	 * @param int $user_id WP user ID.
	 */
	public function on_user_register( $user_id ) {
		if ( ! $this->profile_exists( $user_id ) ) {
			$this->create_profile( $user_id );
		}
	}

	/**
	 * Handle login: update login streak and last_login date.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       WP_User object.
	 */
	public function on_user_login( $user_login, $user ) {
		$user_id = $user->ID;
		$profile = $this->get_profile( $user_id );
		if ( ! $profile ) {
			$this->create_profile( $user_id );
			return;
		}

		$today     = current_time( 'Y-m-d' );
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( current_time( 'Y-m-d' ) ) ) );

		$streak = (int) $profile->login_streak;
		if ( $profile->last_login === $today ) {
			return; // Already logged today
		}
		if ( $profile->last_login === $yesterday ) {
			$streak++;
		} else {
			$streak = 1; // Reset
		}

		$this->update(
			'user_profiles',
			array(
				'login_streak' => $streak,
				'last_login'   => $today,
			),
			array( 'user_id' => $user_id )
		);
		$this->flush_profile_cache( $user_id );
	}

	// ─── Profile CRUD ─────────────────────────────────────────────────────

	/**
	 * Check if a XEN profile already exists for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return bool
	 */
	public function profile_exists( $user_id ) {
		return $this->row_exists( 'user_profiles', array( 'user_id' => (int) $user_id ) );
	}

	/**
	 * Create an empty XEN profile (level 1, 0 XP) for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int|false Inserted profile ID or false.
	 */
	public function create_profile( $user_id ) {
		$user_id = (int) $user_id;

		// Create profile
		$profile_id = $this->insert(
			'user_profiles',
			array(
				'user_id'        => $user_id,
				'level'          => 1,
				'experience'     => 0,
				'coins'          => 0,
				'rebirth_count'  => 0,
				'rank_title'     => 'Unranked',
				'login_streak'   => 1,
				'last_login'     => current_time( 'Y-m-d' ),
				'onboarding_done'=> 0,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%d' )
		);

		if ( ! $profile_id ) {
			return false;
		}

		// Create default stats
		$this->insert( 'user_stats',
			array( 'user_id' => $user_id, 'strength' => 5, 'intelligence' => 5, 'discipline' => 5, 'endurance' => 5, 'wisdom' => 5, 'charisma' => 5, 'focus' => 5, 'vitality' => 5 ),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' )
		);

		// Create default life trees
		$this->insert( 'user_life_trees',
			array( 'user_id' => $user_id, 'physique' => 5, 'intelligence' => 5, 'knowledge' => 5, 'discipline' => 5, 'wealth' => 5, 'communication' => 5, 'leadership' => 5, 'relationships' => 5, 'spirituality' => 5, 'longevity' => 5 ),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' )
		);

		// Create onboarding entry
		$this->insert( 'onboarding', array( 'user_id' => $user_id, 'current_step' => 0 ), array( '%d', '%d' ) );

		return $profile_id;
	}

	/**
	 * Retrieve a user's XEN profile.
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null
	 */
	public function get_profile( $user_id ) {
		return $this->get_user_profile( (int) $user_id );
	}

	/**
	 * Update fields in a user's XEN profile.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $data    Associative array of field => value.
	 * @return int|false
	 */
	public function update_profile( $user_id, array $data ) {
		$result = $this->update( 'user_profiles', $data, array( 'user_id' => (int) $user_id ) );
		$this->flush_profile_cache( $user_id );
		return $result;
	}

	// ─── Level / XP ───────────────────────────────────────────────────────

	/**
	 * Get current level for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_level( $user_id ) {
		$profile = $this->get_profile( $user_id );
		return $profile ? (int) $profile->level : 1;
	}

	/**
	 * Get total accumulated XP.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_xp( $user_id ) {
		$profile = $this->get_profile( $user_id );
		return $profile ? (int) $profile->experience : 0;
	}

	// ─── Rank Title ───────────────────────────────────────────────────────

	/**
	 * Compute the rank title based on level (legacy / fallback only).
	 * Kept for backward compatibility with admin save handler.
	 *
	 * @param int $level User level.
	 * @return string
	 */
	public static function rank_title_for_level( $level ) {
		$level = (int) $level;
		if ( $level >= 100 ) return 'Shadow Monarch';
		if ( $level >= 75  ) return 'National-Level Hunter';
		if ( $level >= 60  ) return 'S-Rank Hunter';
		if ( $level >= 45  ) return 'A-Rank Hunter';
		if ( $level >= 30  ) return 'B-Rank Hunter';
		if ( $level >= 20  ) return 'C-Rank Hunter';
		if ( $level >= 10  ) return 'D-Rank Hunter';
		if ( $level >= 5   ) return 'E-Rank Hunter';
		return 'Unranked';
	}

	/**
	 * Get the rank title for a given rebirth count using the DB rank_definitions table.
	 * Falls back to level-based lookup if the ranks module isn't loaded yet.
	 *
	 * @param int $rebirth_count
	 * @return string
	 */
	public static function rank_title_for_rebirth( $rebirth_count ) {
		if ( function_exists( 'xen_levelup' ) && isset( xen_levelup()->ranks ) ) {
			return xen_levelup()->ranks->title_for_rebirth( $rebirth_count );
		}
		// Fallback
		return 'Unranked';
	}

	/**
	 * Get a user's current rebirth count.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public function get_rebirth_count( $user_id ) {
		$profile = $this->get_profile( $user_id );
		return $profile ? (int) $profile->rebirth_count : 0;
	}

	/**
	 * Update the rank title in the profile based on the user's rebirth count.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $rebirth_count Current rebirth count (leave -1 to read from profile).
	 */
	public function sync_rank_title( $user_id, $rebirth_count = -1 ) {
		if ( $rebirth_count < 0 ) {
			$rebirth_count = $this->get_rebirth_count( $user_id );
		}
		// If user has never rebirthed, maintain legacy behavior: derive rank from level
		if ( (int) $rebirth_count === 0 ) {
			$profile = $this->get_profile( $user_id );
			$level = $profile ? (int) $profile->level : 1;
			$title = self::rank_title_for_level( $level );
		} else {
			$title = self::rank_title_for_rebirth( $rebirth_count );
		}
		$this->update_profile( $user_id, array( 'rank_title' => $title ) );
	}

	// ─── Full User Data ───────────────────────────────────────────────────

	/**
	 * Return a complete user data object including profile, stats, life trees.
	 *
	 * @param int $user_id WP user ID.
	 * @return array|null
	 */
	public function get_full_data( $user_id ) {
		$user_id = (int) $user_id;
		$wp_user = get_userdata( $user_id );
		if ( ! $wp_user ) {
			return null;
		}

		$profile   = $this->get_profile( $user_id );

		if ( ! $profile ) {
			$this->create_profile( $user_id );
			$profile = $this->get_profile( $user_id );
		}

		$level    = (int) $profile->level;
		$leveling = xen_levelup()->leveling;
		$stats    = xen_levelup()->stats->get_all_stats( $user_id );

		return array(
			'user_id'       => $user_id,
			'display_name'  => $wp_user->display_name,
			'avatar_url'    => get_avatar_url( $user_id, array( 'size' => 120 ) ),
			'profile'       => $profile,
			'stats'         => $stats,
			'level'         => $level,
			'xp'            => (int) $profile->experience,
			'experience'    => (int) $profile->experience,
			'xp_next_level' => $leveling->xp_for_next_level( $level ),
			'xp_progress'   => $leveling->level_progress_percent( $user_id ),
			'coins'         => (int) $profile->coins,
			'rank_title'    => $profile->rank_title,
			'rebirth_count' => (int) ( $profile->rebirth_count ?? 0 ),
			'current_title' => $profile->current_title,
			'profile_frame' => $profile->profile_frame,
			'name_color'    => $profile->name_color,
		);
	}

	// ─── Admin: list users ────────────────────────────────────────────────

	/**
	 * Get paginated list of all XEN users with their profiles.
	 *
	 * @param int    $per_page Number per page.
	 * @param int    $page     1-based page number.
	 * @param string $search   Optional search term.
	 * @return array { users: array, total: int }
	 */
	public function get_all_users( $per_page = 20, $page = 1, $search = '' ) {
		global $wpdb;
		$offset = ( max( 1, (int) $page ) - 1 ) * (int) $per_page;
		$p      = $wpdb->prefix;

		$search_sql = '';
		$args       = array();

		if ( $search ) {
			$search_sql = ' AND ( u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s )';
			$like       = '%' . $wpdb->esc_like( sanitize_text_field( $search ) ) . '%';
			$args[]     = $like;
			$args[]     = $like;
			$args[]     = $like;
		}

		$total_sql = "SELECT COUNT(*) FROM {$p}users u
			LEFT JOIN {$p}xen_user_profiles xp ON xp.user_id = u.ID
			WHERE 1=1 {$search_sql}";

		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( $total_sql, $args ) : $total_sql ); // phpcs:ignore

		$args[] = (int) $per_page;
		$args[] = $offset;

		$rows_sql = "SELECT u.ID, u.user_login, u.display_name, u.user_email,
				xp.level, xp.experience, xp.coins, xp.rank_title, xp.onboarding_done
			FROM {$p}users u
			LEFT JOIN {$p}xen_user_profiles xp ON xp.user_id = u.ID
			WHERE 1=1 {$search_sql}
			ORDER BY xp.level DESC, xp.experience DESC
			LIMIT %d OFFSET %d";

		$users = $wpdb->get_results( $wpdb->prepare( $rows_sql, $args ) ); // phpcs:ignore

		return array( 'users' => $users, 'total' => $total );
	}
}
