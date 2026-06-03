<?php
/**
 * Quest base class – shared logic for all quest types.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Quests
 */
class Xen_Quests extends Xen_Database {

	/** @var string[] Valid difficulty slugs */
	const DIFFICULTIES = array( 'very_easy', 'easy', 'medium', 'hard', 'very_hard', 'extreme', 'legendary' );

	/** @var string[] Valid quest type slugs */
	const TYPES = array( 'daily', 'random', 'special', 'legendary' );

	/** @var array Base XP rewards per difficulty */
	const DIFFICULTY_XP = array(
		'very_easy' => 20,
		'easy'      => 50,
		'medium'    => 100,
		'hard'      => 250,
		'very_hard' => 500,
		'extreme'   => 1000,
		'legendary' => 5000,
	);

	/** @var array Base coin rewards per difficulty */
	const DIFFICULTY_COINS = array(
		'very_easy' => 5,
		'easy'      => 10,
		'medium'    => 20,
		'hard'      => 40,
		'very_hard' => 80,
		'extreme'   => 150,
		'legendary' => 500,
	);

	public function __construct() {
		parent::__construct();
	}

	// ─── Templates ────────────────────────────────────────────────────────

	/**
	 * Retrieve quest templates for a given category, optionally filtered by type/difficulty.
	 *
	 * @param string $category   Category slug or 'all'.
	 * @param string $quest_type Quest type slug or 'all'.
	 * @return array
	 */
	public function get_templates( $category = 'all', $quest_type = 'all' ) {
		$t      = $this->table( 'quest_templates' );
		$sql    = "SELECT * FROM {$t} WHERE is_active = 1";
		$args   = array();

		if ( $category && 'all' !== $category ) {
			$sql   .= ' AND category = %s';
			$args[] = sanitize_key( $category );
		}
		if ( $quest_type && 'all' !== $quest_type ) {
			$sql   .= ' AND quest_type = %s';
			$args[] = sanitize_key( $quest_type );
		}

		return $this->query( $sql, $args );
	}

	/**
	 * Get a single quest template by ID.
	 *
	 * @param int $template_id Template ID.
	 * @return object|null
	 */
	public function get_template( $template_id ) {
		return $this->get_row( 'quest_templates', array( 'id' => (int) $template_id ) );
	}

	// ─── User Quest Queries ───────────────────────────────────────────────

	/**
	 * Get all active quests for a user.
	 *
	 * @param int    $user_id    WP user ID.
	 * @param string $quest_type Optional quest type filter.
	 * @param string $status     Status filter (default 'active').
	 * @return array
	 */
	public function get_user_quests( $user_id, $quest_type = '', $status = 'active' ) {
		$t    = $this->table( 'user_quests' );
		$sql  = "SELECT * FROM {$t} WHERE user_id = %d AND status = %s";
		$args = array( (int) $user_id, $status );

		if ( $quest_type ) {
			$sql   .= ' AND quest_type = %s';
			$args[] = sanitize_key( $quest_type );
		}
		$sql .= ' ORDER BY assigned_at DESC';

		return $this->query( $sql, $args );
	}

	/**
	 * Get quests for a specific date.
	 *
	 * @param int    $user_id    WP user ID.
	 * @param string $date       Y-m-d date string.
	 * @param string $quest_type Optional quest type filter.
	 * @return array
	 */
	public function get_user_quests_for_date( $user_id, $date, $quest_type = '' ) {
		$t    = $this->table( 'user_quests' );
		$sql  = "SELECT * FROM {$t} WHERE user_id = %d AND quest_date = %s";
		$args = array( (int) $user_id, sanitize_text_field( $date ) );

		if ( $quest_type ) {
			$sql   .= ' AND quest_type = %s';
			$args[] = sanitize_key( $quest_type );
		}
		$sql .= ' ORDER BY difficulty ASC, assigned_at DESC';

		return $this->query( $sql, $args );
	}

	/**
	 * Get a single user quest by ID (ensures ownership).
	 *
	 * @param int $quest_id User quest ID.
	 * @param int $user_id  WP user ID.
	 * @return object|null
	 */
	public function get_user_quest( $quest_id, $user_id ) {
		return $this->get_row( 'user_quests', array( 'id' => (int) $quest_id, 'user_id' => (int) $user_id ) );
	}

	// ─── Assign Quest ─────────────────────────────────────────────────────

	/**
	 * Assign a quest to a user.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param array  $quest_data  Quest fields array.
	 * @return int|false Inserted quest ID or false.
	 */
	public function assign_quest( $user_id, array $quest_data ) {
		$level    = xen_levelup()->user->get_level( $user_id );
		$base_xp  = (int) ( $quest_data['xp_reward'] ?? self::DIFFICULTY_XP[ $quest_data['difficulty'] ?? 'easy' ] ?? 50 );
		$xp       = xen_levelup()->leveling->scale_xp( $base_xp, $level );

		$allowed_statuses = array( 'active', 'pending' );
		$status           = isset( $quest_data['status'] ) && in_array( $quest_data['status'], $allowed_statuses, true )
			? $quest_data['status']
			: 'active';

		$data = array(
			'user_id'      => (int) $user_id,
			'template_id'  => isset( $quest_data['template_id'] ) ? (int) $quest_data['template_id'] : null,
			'title'        => sanitize_text_field( $quest_data['title'] ?? '' ),
			'description'  => sanitize_textarea_field( $quest_data['description'] ?? '' ),
			'category'     => sanitize_key( $quest_data['category'] ?? 'physique' ),
			'difficulty'   => sanitize_key( $quest_data['difficulty'] ?? 'easy' ),
			'quest_type'   => sanitize_key( $quest_data['quest_type'] ?? 'daily' ),
			'xp_reward'    => $xp,
			'coin_reward'  => (int) ( $quest_data['coin_reward'] ?? self::DIFFICULTY_COINS[ $quest_data['difficulty'] ?? 'easy' ] ?? 10 ),
			'stat_rewards' => isset( $quest_data['stat_rewards'] ) ? wp_json_encode( $quest_data['stat_rewards'] ) : null,
			'status'       => $status,
			'quest_date'   => $quest_data['quest_date'] ?? current_time( 'Y-m-d' ),
			'expires_at'   => $quest_data['expires_at'] ?? null,
		);

		return $this->insert( 'user_quests', $data );
	}

	// ─── Accept Quest ─────────────────────────────────────────────────────

	/**
	 * Accept a pending quest, transitioning it to active status.
	 *
	 * @param int $quest_id User quest ID.
	 * @param int $user_id  WP user ID.
	 * @return true|WP_Error
	 */
	public function accept_quest( $quest_id, $user_id ) {
		$quest = $this->get_user_quest( $quest_id, $user_id );

		if ( ! $quest ) {
			return new WP_Error( 'not_found', __( 'Quest not found.', 'xen-levelup' ) );
		}
		if ( 'pending' !== $quest->status ) {
			return new WP_Error( 'invalid_status', __( 'Only pending quests can be accepted.', 'xen-levelup' ) );
		}

		$this->update(
			'user_quests',
			array( 'status' => 'active' ),
			array( 'id' => (int) $quest_id, 'user_id' => (int) $user_id )
		);

		return true;
	}

	// ─── Complete Quest ───────────────────────────────────────────────────

	/**
	 * Mark a quest as completed, award XP, coins, and stat rewards.
	 *
	 * @param int $quest_id User quest ID.
	 * @param int $user_id  WP user ID.
	 * @return array|WP_Error Result array or WP_Error.
	 */
	public function complete_quest( $quest_id, $user_id ) {
		$quest = $this->get_user_quest( $quest_id, $user_id );

		if ( ! $quest ) {
			return new WP_Error( 'not_found', __( 'Quest not found.', 'xen-levelup' ) );
		}
		if ( 'active' !== $quest->status ) {
			return new WP_Error( 'invalid_status', __( 'Quest is no longer active.', 'xen-levelup' ) );
		}

		// Mark complete
		$this->update(
			'user_quests',
			array( 'status' => 'completed', 'completed_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $quest_id, 'user_id' => (int) $user_id )
		);

		// Award XP
		$xp_result = xen_levelup()->leveling->add_xp(
			$user_id,
			(int) $quest->xp_reward,
			'quest',
			$quest_id,
			sprintf( __( 'Quest: %s', 'xen-levelup' ), $quest->title )
		);

		// Award coins
		xen_levelup()->currency->add(
			$user_id,
			(int) $quest->coin_reward,
			'quest',
			sprintf( __( 'Quest reward: %s', 'xen-levelup' ), $quest->title ),
			$quest_id,
			'quest'
		);

		// Apply stat rewards
		$stat_rewards = array();
		if ( $quest->stat_rewards ) {
			$stat_rewards = json_decode( $quest->stat_rewards, true );
			if ( is_array( $stat_rewards ) ) {
				xen_levelup()->stats->apply_stat_rewards( $user_id, $stat_rewards );
			}
		}

		// Increment quest counter on profile
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}xen_user_profiles SET total_quests = total_quests + 1 WHERE user_id = %d",
			(int) $user_id
		) );
		xen_levelup()->user->flush_profile_cache( $user_id );

		// Fire action
		do_action( 'xen_quest_completed', $user_id, array(
			'quest'       => $quest,
			'xp_result'   => $xp_result,
			'stat_rewards'=> $stat_rewards,
		) );

		return array(
			'success'      => true,
			'quest'        => $quest,
			'xp_earned'    => (int) $quest->xp_reward,
			'coins_earned' => (int) $quest->coin_reward,
			'stat_rewards' => $stat_rewards,
			'leveled_up'   => $xp_result['leveled_up'] ?? false,
			'new_level'    => $xp_result['new_level']  ?? null,
		);
	}

	// ─── Expire Quests ────────────────────────────────────────────────────

	/**
	 * Expire active quests whose expires_at has passed.
	 *
	 * Typically called by a cron job.
	 */
	public function expire_stale_quests() {
		global $wpdb;
		$table = $wpdb->prefix . 'xen_user_quests';
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET status = 'expired' WHERE status IN ('active','pending') AND expires_at IS NOT NULL AND expires_at < %s",
			current_time( 'mysql' )
		) );
	}

	// ─── Helpers ──────────────────────────────────────────────────────────

	/**
	 * Human-readable difficulty label.
	 *
	 * @param string $slug Difficulty slug.
	 * @return string
	 */
	public static function difficulty_label( $slug ) {
		$labels = array(
			'very_easy' => __( 'Very Easy', 'xen-levelup' ),
			'easy'      => __( 'Easy',      'xen-levelup' ),
			'medium'    => __( 'Medium',    'xen-levelup' ),
			'hard'      => __( 'Hard',      'xen-levelup' ),
			'very_hard' => __( 'Very Hard', 'xen-levelup' ),
			'extreme'   => __( 'Extreme',   'xen-levelup' ),
			'legendary' => __( 'Legendary', 'xen-levelup' ),
		);
		return $labels[ $slug ] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * CSS class suffix for difficulty colouring.
	 *
	 * @param string $slug Difficulty slug.
	 * @return string
	 */
	public static function difficulty_class( $slug ) {
		$map = array(
			'very_easy' => 'very-easy',
			'easy'      => 'easy',
			'medium'    => 'medium',
			'hard'      => 'hard',
			'very_hard' => 'very-hard',
			'extreme'   => 'extreme',
			'legendary' => 'legendary',
		);
		return $map[ $slug ] ?? 'easy';
	}
}
