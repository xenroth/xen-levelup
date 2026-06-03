<?php
/**
 * Daily quest generation – generates quests every midnight based on user priorities.
 *
 * Distribution:  70% from top priority | 20% from 2nd priority | 10% from others
 * Total:         5 main quests + 2 secondary quests = 7 per day
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Daily_Quests
 */
class Xen_Daily_Quests extends Xen_Database {

	/** Total quests per day */
	const MAIN_COUNT      = 5;
	const SECONDARY_COUNT = 2;

	public function __construct() {
		parent::__construct();
	}

	// ─── Generate ─────────────────────────────────────────────────────────

	/**
	 * Generate daily quests for a single user.
	 *
	 * Skips generation if quests already exist for today.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $date    Y-m-d date string (defaults to today).
	 * @return array Generated quest IDs.
	 */
	public function generate_for_user( $user_id, $date = '' ) {
		$user_id = (int) $user_id;
		$date    = $date ?: current_time( 'Y-m-d' );

		// Already generated today?
		$existing = xen_levelup()->quests->get_user_quests_for_date( $user_id, $date, 'daily' );
		if ( ! empty( $existing ) ) {
			return array_column( (array) $existing, 'id' );
		}

		// Determine quest distribution
		$priorities    = $this->get_user_priorities( $user_id );
		$distribution  = $this->build_distribution( $priorities );

		$generated_ids = array();

		foreach ( $distribution as $category => $count ) {
			$templates = $this->pick_templates( $category, $count, $user_id );
			foreach ( $templates as $tmpl ) {
				$id = xen_levelup()->quests->assign_quest( $user_id, array(
					'template_id' => (int) $tmpl->id,
					'title'       => $tmpl->title,
					'description' => $tmpl->description,
					'category'    => $tmpl->category,
					'difficulty'  => $tmpl->difficulty,
					'quest_type'  => 'daily',
					'xp_reward'   => (int) $tmpl->xp_reward,
					'coin_reward' => (int) $tmpl->coin_reward,
					'stat_rewards'=> $tmpl->stat_rewards ? json_decode( $tmpl->stat_rewards, true ) : array(),
					'quest_date'  => $date,
					'expires_at'  => $date . ' 23:59:59',
				) );
				if ( $id ) {
					$generated_ids[] = $id;
				}
			}
		}

		return $generated_ids;
	}

	/**
	 * Bulk-generate daily quests for ALL registered users.
	 *
	 * Called by the daily cron job.
	 */
	public function generate_for_all_users() {
		$date    = current_time( 'Y-m-d' );
		$user_ids = $this->get_active_user_ids();

		foreach ( $user_ids as $user_id ) {
			$this->generate_for_user( $user_id, $date );
		}
	}

	// ─── Distribution ────────────────────────────────────────────────────

	/**
	 * Build the category => count distribution array.
	 *
	 * 70 % top priority (≈4 quests), 20 % second (≈2), 10 % rest (≈1)
	 * Total target: 7
	 *
	 * @param array $priorities Ordered category slugs.
	 * @return array
	 */
	private function build_distribution( array $priorities ) {
		$total = self::MAIN_COUNT + self::SECONDARY_COUNT; // 7
		$dist  = array();

		if ( empty( $priorities ) ) {
			// Default to physique
			return array( 'physique' => $total );
		}

		$top    = $priorities[0] ?? 'physique';
		$second = $priorities[1] ?? $top;

		// 70 % = 5, 20 % = 1-2, 10 % = rest
		$dist[ $top ]    = (int) round( $total * 0.70 ); // ~5
		$dist[ $second ] = (int) round( $total * 0.20 ); // ~1

		// Fill remaining from other categories
		$remaining = $total - array_sum( $dist );
		if ( $remaining > 0 ) {
			$others = array_slice( $priorities, 2 );
			if ( empty( $others ) ) {
				$others = array( $top );
			}
			shuffle( $others );
			foreach ( $others as $cat ) {
				if ( $remaining <= 0 ) break;
				$dist[ $cat ] = ( $dist[ $cat ] ?? 0 ) + 1;
				$remaining--;
			}
		}

		return array_filter( $dist );
	}

	// ─── Template Picker ─────────────────────────────────────────────────

	/**
	 * Pick $count templates from a category, avoiding repeats for today.
	 *
	 * @param string $category Category slug.
	 * @param int    $count    Number of templates to pick.
	 * @param int    $user_id  WP user ID.
	 * @return array Shuffled subset of template objects.
	 */
	private function pick_templates( $category, $count, $user_id ) {
		$level = xen_levelup()->user->get_level( $user_id );

		// Pull all active daily templates for the category
		$templates = xen_levelup()->quests->get_templates( $category, 'daily' );

		if ( empty( $templates ) ) {
			// Fallback to any category
			$templates = xen_levelup()->quests->get_templates( 'all', 'daily' );
		}

		// Filter out legendary difficulty from daily quests
		$templates = array_filter( $templates, function ( $t ) {
			return 'legendary' !== $t->difficulty;
		} );

		// Bias difficulty based on user level
		$templates = $this->bias_difficulty( array_values( $templates ), $level );

		// Avoid templates already used today
		$used_today = $this->get_used_template_ids_today( $user_id );
		$available  = array_filter( $templates, function ( $t ) use ( $used_today ) {
			return ! in_array( (int) $t->id, $used_today, true );
		} );

		if ( empty( $available ) ) {
			$available = $templates; // Allow repeats as fallback
		}

		$available = array_values( $available );
		shuffle( $available );

		return array_slice( $available, 0, min( $count, count( $available ) ) );
	}

	/**
	 * Bias template selection towards higher difficulties for higher-level users.
	 *
	 * @param array $templates Array of template objects.
	 * @param int   $level     User level.
	 * @return array
	 */
	private function bias_difficulty( array $templates, $level ) {
		if ( $level >= 50 ) {
			$preferred = array( 'hard', 'very_hard', 'extreme', 'medium' );
		} elseif ( $level >= 25 ) {
			$preferred = array( 'medium', 'hard', 'easy', 'very_hard' );
		} elseif ( $level >= 10 ) {
			$preferred = array( 'easy', 'medium', 'hard', 'very_easy' );
		} else {
			$preferred = array( 'very_easy', 'easy', 'medium' );
		}

		usort( $templates, function ( $a, $b ) use ( $preferred ) {
			$ai = array_search( $a->difficulty, $preferred, true );
			$bi = array_search( $b->difficulty, $preferred, true );
			$ai = ( false === $ai ) ? 999 : $ai;
			$bi = ( false === $bi ) ? 999 : $bi;
			return $ai - $bi;
		} );

		return $templates;
	}

	// ─── Helpers ─────────────────────────────────────────────────────────

	/**
	 * Get the user's ordered priority list from onboarding data.
	 *
	 * @param int $user_id WP user ID.
	 * @return array Category slugs ordered by priority.
	 */
	private function get_user_priorities( $user_id ) {
		$row = $this->get_row( 'onboarding', array( 'user_id' => (int) $user_id ) );

		if ( ! $row || ! $row->priorities_data ) {
			// Default balanced distribution
			return array( 'physique', 'discipline', 'intelligence', 'knowledge', 'wealth' );
		}

		$data = json_decode( $row->priorities_data, true );
		return is_array( $data ) ? array_values( $data ) : array( 'physique' );
	}

	/**
	 * Get template IDs already assigned to this user today.
	 *
	 * @param int $user_id WP user ID.
	 * @return int[]
	 */
	private function get_used_template_ids_today( $user_id ) {
		$t    = $this->table( 'user_quests' );
		$date = current_time( 'Y-m-d' );
		$rows = $this->query(
			"SELECT template_id FROM {$t} WHERE user_id = %d AND quest_date = %s AND template_id IS NOT NULL",
			array( (int) $user_id, $date )
		);
		return array_map( 'intval', array_column( (array) $rows, 'template_id' ) );
	}

	/**
	 * Get IDs of all WP users who have a XEN profile.
	 *
	 * @return int[]
	 */
	private function get_active_user_ids() {
		$t    = $this->table( 'user_profiles' );
		$rows = $this->query( "SELECT user_id FROM {$t}" );
		return array_map( 'intval', array_column( (array) $rows, 'user_id' ) );
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get today's daily quests for a user.
	 *
	 * Generates them if they don't exist yet.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_today( $user_id ) {
		$date = current_time( 'Y-m-d' );
		$this->generate_for_user( $user_id, $date );
		return xen_levelup()->quests->get_user_quests_for_date( $user_id, $date, 'daily' );
	}
}
