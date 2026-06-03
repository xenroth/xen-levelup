<?php
/**
 * Stat system – RPG stats and Life Development Trees.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Stats
 */
class Xen_Stats extends Xen_Database {

	/** @var string[] Valid RPG stat names */
	const RPG_STATS = array( 'strength', 'intelligence', 'discipline', 'endurance', 'wisdom', 'charisma', 'focus', 'vitality' );

	/** @var string[] Valid life tree names */
	const LIFE_TREES = array( 'physique', 'intelligence', 'knowledge', 'discipline', 'wealth', 'communication', 'leadership', 'relationships', 'spirituality', 'longevity' );

	/** @var string[] Life tree icons */
	const LIFE_TREE_ICONS = array(
		'physique'      => '🏋️',
		'intelligence'  => '🧠',
		'knowledge'     => '📚',
		'discipline'    => '⚡',
		'wealth'        => '💰',
		'communication' => '🗣',
		'leadership'    => '👑',
		'relationships' => '❤️',
		'spirituality'  => '🕊',
		'longevity'     => '🛡',
	);

	public function __construct() {
		parent::__construct();
	}

	// ─── Stat Generation ─────────────────────────────────────────────────

	/**
	 * Generate and persist initial stats based on onboarding data.
	 *
	 * @param int   $user_id    WP user ID.
	 * @param array $priorities Ordered array of category slugs (highest first).
	 * @param array $interests  Map of interest_slug => 1-10 score.
	 * @param array $personality Map of trait_slug => 1-10 value.
	 * @return array Generated stats array.
	 */
	public function generate_initial_stats( $user_id, array $priorities, array $interests, array $personality ) {
		$life = array_fill_keys( self::LIFE_TREES, 5 );

		// 1. Priority bonuses (highest priority gets largest bonus)
		$priority_bonuses = array( 12, 8, 5, 4, 3, 2, 1, 1, 1, 1 );
		foreach ( $priorities as $idx => $cat ) {
			if ( isset( $life[ $cat ] ) && isset( $priority_bonuses[ $idx ] ) ) {
				$life[ $cat ] += $priority_bonuses[ $idx ];
			}
		}

		// 2. Interest bonuses
		$interest_map = array(
			'physical_fitness'  => array( 'physique' => 2, 'longevity' => 1 ),
			'strength_training' => array( 'physique' => 3 ),
			'sports'            => array( 'physique' => 2, 'leadership' => 1 ),
			'reading'           => array( 'knowledge' => 2, 'intelligence' => 1 ),
			'learning'          => array( 'intelligence' => 2, 'knowledge' => 1 ),
			'career_success'    => array( 'wealth' => 2, 'leadership' => 1 ),
			'business'          => array( 'wealth' => 3, 'leadership' => 1 ),
			'leadership'        => array( 'leadership' => 3, 'communication' => 1 ),
			'communication'     => array( 'communication' => 3 ),
			'productivity'      => array( 'discipline' => 2, 'wealth' => 1 ),
			'mental_health'     => array( 'spirituality' => 2, 'longevity' => 1 ),
			'spiritual_growth'  => array( 'spirituality' => 3 ),
			'longevity'         => array( 'longevity' => 3, 'physique' => 1 ),
			'relationships'     => array( 'relationships' => 3, 'communication' => 1 ),
			'creativity'        => array( 'intelligence' => 1, 'knowledge' => 1, 'wealth' => 1 ),
		);

		foreach ( $interests as $slug => $score ) {
			$score = max( 1, min( 10, (int) $score ) );
			if ( isset( $interest_map[ $slug ] ) ) {
				foreach ( $interest_map[ $slug ] as $tree => $base ) {
					$life[ $tree ] += (int) round( $base * $score / 10 * 5 );
				}
			}
		}

		// 3. Personality modifiers
		$ext   = max( 1, min( 10, (int) ( $personality['introvert_extrovert']    ?? 5 ) ) );
		$creat = max( 1, min( 10, (int) ( $personality['analytical_creative']    ?? 5 ) ) );
		$comp  = max( 1, min( 10, (int) ( $personality['competitive_cooperative'] ?? 5 ) ) );
		$actv  = max( 1, min( 10, (int) ( $personality['active_passive']         ?? 5 ) ) );
		$strc  = max( 1, min( 10, (int) ( $personality['structured_flexible']    ?? 5 ) ) );

		$life['communication'] += (int) round( ( $ext - 5 ) * 0.6 );
		$life['leadership']    += (int) round( ( $ext - 5 ) * 0.4 );
		$life['relationships'] += (int) round( ( $ext - 5 ) * 0.3 );
		$life['knowledge']     += (int) round( ( 10 - $creat ) * 0.4 );
		$life['intelligence']  += (int) round( ( 10 - $creat ) * 0.3 );
		$life['wealth']        += (int) round( $comp * 0.4 );
		$life['leadership']    += (int) round( $comp * 0.3 );
		$life['physique']      += (int) round( $actv * 0.5 );
		$life['longevity']     += (int) round( $actv * 0.3 );
		$life['discipline']    += (int) round( $strc * 0.6 );
		$life['knowledge']     += (int) round( $strc * 0.3 );

		// 4. Random uniqueness (±4)
		foreach ( $life as $tree => $val ) {
			$life[ $tree ] = max( 1, $val + wp_rand( -4, 4 ) );
		}

		// 5. Derive RPG stats from life trees
		$rpg = array(
			'strength'     => (int) round( $life['physique'] * 0.8    + $life['discipline'] * 0.2    + wp_rand( 1, 4 ) ),
			'intelligence' => (int) round( $life['intelligence'] * 0.7 + $life['knowledge'] * 0.3    + wp_rand( 1, 4 ) ),
			'discipline'   => (int) round( $life['discipline'] * 0.8   + $life['spirituality'] * 0.2 + wp_rand( 1, 4 ) ),
			'endurance'    => (int) round( $life['physique'] * 0.5     + $life['longevity'] * 0.5    + wp_rand( 1, 4 ) ),
			'wisdom'       => (int) round( $life['knowledge'] * 0.5    + $life['spirituality'] * 0.5 + wp_rand( 1, 4 ) ),
			'charisma'     => (int) round( $life['communication'] * 0.6 + $life['leadership'] * 0.4  + wp_rand( 1, 4 ) ),
			'focus'        => (int) round( $life['discipline'] * 0.5   + $life['intelligence'] * 0.5 + wp_rand( 1, 4 ) ),
			'vitality'     => (int) round( $life['longevity'] * 0.6    + $life['physique'] * 0.4     + wp_rand( 1, 4 ) ),
		);

		// Persist
		$this->save_life_trees( $user_id, $life );
		$this->save_rpg_stats( $user_id, $rpg );

		return array( 'life_trees' => $life, 'rpg_stats' => $rpg );
	}

	// ─── Persist ─────────────────────────────────────────────────────────

	/**
	 * Upsert life tree values.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $trees   Map of tree => value.
	 */
	public function save_life_trees( $user_id, array $trees ) {
		$user_id = (int) $user_id;
		$safe    = array( 'user_id' => $user_id );
		foreach ( self::LIFE_TREES as $key ) {
			if ( isset( $trees[ $key ] ) ) {
				$safe[ $key ] = max( 1, (int) $trees[ $key ] );
			}
		}

		if ( $this->row_exists( 'user_life_trees', array( 'user_id' => $user_id ) ) ) {
			unset( $safe['user_id'] );
			$this->update( 'user_life_trees', $safe, array( 'user_id' => $user_id ) );
		} else {
			$this->insert( 'user_life_trees', $safe );
		}
		wp_cache_delete( 'xen_life_trees_' . $user_id );
	}

	/**
	 * Upsert RPG stat values.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $stats   Map of stat => value.
	 */
	public function save_rpg_stats( $user_id, array $stats ) {
		$user_id = (int) $user_id;
		$safe    = array( 'user_id' => $user_id );
		foreach ( self::RPG_STATS as $key ) {
			if ( isset( $stats[ $key ] ) ) {
				$safe[ $key ] = max( 1, (int) $stats[ $key ] );
			}
		}

		if ( $this->row_exists( 'user_stats', array( 'user_id' => $user_id ) ) ) {
			unset( $safe['user_id'] );
			$this->update( 'user_stats', $safe, array( 'user_id' => $user_id ) );
		} else {
			$this->insert( 'user_stats', $safe );
		}
		wp_cache_delete( 'xen_stats_' . $user_id );
	}

	// ─── Increment from Quests/Tasks ─────────────────────────────────────

	/**
	 * Apply stat rewards (from a completed quest/task).
	 *
	 * @param int   $user_id     WP user ID.
	 * @param array $stat_rewards Map of stat_slug => increment.
	 */
	public function apply_stat_rewards( $user_id, array $stat_rewards ) {
		if ( empty( $stat_rewards ) ) {
			return;
		}
		$user_id = (int) $user_id;

		$life    = (array) $this->get_user_life_trees( $user_id );
		$rpg     = (array) $this->get_user_stats( $user_id );
		$changed = false;

		foreach ( $stat_rewards as $stat => $amount ) {
			$amount = (int) $amount;
			if ( in_array( $stat, self::LIFE_TREES, true ) && isset( $life[ $stat ] ) ) {
				$life[ $stat ] = max( 1, (int) $life[ $stat ] + $amount );
				$changed = true;
			} elseif ( in_array( $stat, self::RPG_STATS, true ) && isset( $rpg[ $stat ] ) ) {
				$rpg[ $stat ] = max( 1, (int) $rpg[ $stat ] + $amount );
				$changed = true;
			}
		}

		if ( $changed ) {
			$this->save_life_trees( $user_id, $life );
			$this->save_rpg_stats( $user_id, $rpg );
		}
	}

	// ─── Getters ─────────────────────────────────────────────────────────

	/**
	 * Get all stats (RPG + life trees) for a user as a structured array.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_all_stats( $user_id ) {
		$user_id    = (int) $user_id;
		$rpg        = $this->get_user_stats( $user_id );
		$life_trees = $this->get_user_life_trees( $user_id );

		$tree_keys  = array_flip( self::LIFE_TREES );
		$rpg_keys   = array_flip( self::RPG_STATS );

		return array(
			'rpg'        => $rpg        ? array_intersect_key( (array) $rpg,        $rpg_keys  ) : array_fill_keys( self::RPG_STATS,   5 ),
			'life_trees' => $life_trees ? array_intersect_key( (array) $life_trees,  $tree_keys ) : array_fill_keys( self::LIFE_TREES, 5 ),
			'icons'      => self::LIFE_TREE_ICONS,
		);
	}

	/**
	 * Return human-readable label for a life tree key.
	 *
	 * @param string $key Life tree key.
	 * @return string
	 */
	public static function life_tree_label( $key ) {
		$labels = array(
			'physique'      => __( 'Physique',       'xen-levelup' ),
			'intelligence'  => __( 'Intelligence',   'xen-levelup' ),
			'knowledge'     => __( 'Knowledge',      'xen-levelup' ),
			'discipline'    => __( 'Discipline',     'xen-levelup' ),
			'wealth'        => __( 'Wealth',         'xen-levelup' ),
			'communication' => __( 'Communication',  'xen-levelup' ),
			'leadership'    => __( 'Leadership',     'xen-levelup' ),
			'relationships' => __( 'Relationships',  'xen-levelup' ),
			'spirituality'  => __( 'Spirituality',   'xen-levelup' ),
			'longevity'     => __( 'Longevity',      'xen-levelup' ),
		);
		return $labels[ $key ] ?? ucfirst( $key );
	}

	/**
	 * Return human-readable label for an RPG stat key.
	 *
	 * @param string $key RPG stat key.
	 * @return string
	 */
	public static function rpg_stat_label( $key ) {
		$labels = array(
			'strength'     => __( 'Strength',     'xen-levelup' ),
			'intelligence' => __( 'Intelligence', 'xen-levelup' ),
			'discipline'   => __( 'Discipline',   'xen-levelup' ),
			'endurance'    => __( 'Endurance',    'xen-levelup' ),
			'wisdom'       => __( 'Wisdom',       'xen-levelup' ),
			'charisma'     => __( 'Charisma',     'xen-levelup' ),
			'focus'        => __( 'Focus',        'xen-levelup' ),
			'vitality'     => __( 'Vitality',     'xen-levelup' ),
		);
		return $labels[ $key ] ?? ucfirst( $key );
	}
}
