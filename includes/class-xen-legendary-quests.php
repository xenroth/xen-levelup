<?php
/**
 * Legendary quest system – weekly elite quests assigned to 10 random active users.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Legendary_Quests
 */
class Xen_Legendary_Quests extends Xen_Database {

	/** Number of users to receive a legendary quest each week */
	const CHOSEN_COUNT = 10;

	public function __construct() {
		parent::__construct();
	}

	// ─── Weekly Assignment ────────────────────────────────────────────────

	/**
	 * Select 10 random active users and assign legendary quests.
	 *
	 * Called once per week by the cron system.
	 */
	public function run_weekly_assignment() {
		$chosen    = $this->select_random_users( self::CHOSEN_COUNT );
		$templates = xen_levelup()->quests->get_templates( 'all', 'legendary' );

		if ( empty( $templates ) ) {
			return;
		}

		$end_of_week = gmdate( 'Y-m-d H:i:s', strtotime( 'next monday midnight' ) - 1 );

		foreach ( $chosen as $user_id ) {
			// Skip users who already have an active or pending legendary quest
			$existing = xen_levelup()->quests->get_user_quests( $user_id, 'legendary', 'active' );
			$pending  = xen_levelup()->quests->get_user_quests( $user_id, 'legendary', 'pending' );
			if ( ! empty( $existing ) || ! empty( $pending ) ) {
				continue;
			}

			shuffle( $templates );
			$tmpl = $templates[0];

			$id = xen_levelup()->quests->assign_quest( $user_id, array(
				'template_id' => (int) $tmpl->id,
				'title'       => $tmpl->title,
				'description' => $tmpl->description,
				'category'    => $tmpl->category,
				'difficulty'  => 'legendary',
				'quest_type'  => 'legendary',
				'xp_reward'   => (int) $tmpl->xp_reward,
				'coin_reward' => (int) $tmpl->coin_reward,
				'stat_rewards'=> $tmpl->stat_rewards ? json_decode( $tmpl->stat_rewards, true ) : array(),
				'status'      => 'pending',
				'quest_date'  => current_time( 'Y-m-d' ),
				'expires_at'  => $end_of_week,
			) );

			if ( $id ) {
				// Big announcement notification
				xen_levelup()->notifications->add(
					$user_id,
					'legendary_quest',
					__( '👑 You Have Been Chosen!', 'xen-levelup' ),
					sprintf(
						/* translators: %s = quest title */
						__( 'The System has selected you for a Legendary Quest: "%s". Complete it to earn massive rewards!', 'xen-levelup' ),
						$tmpl->title
					),
					array( 'quest_id' => $id, 'legendary' => true )
				);
			}
		}
	}

	// ─── User Selection ───────────────────────────────────────────────────

	/**
	 * Select $count random active users (active = logged in within last 7 days).
	 *
	 * @param int $count Number of users to select.
	 * @return int[] User IDs.
	 */
	private function select_random_users( $count ) {
		$t        = $this->table( 'user_profiles' );
		$cutoff   = gmdate( 'Y-m-d', strtotime( '-7 days' ) );

		$rows = $this->query(
			"SELECT user_id FROM {$t} WHERE last_login >= %s ORDER BY RAND() LIMIT %d",
			array( $cutoff, (int) $count )
		);

		if ( empty( $rows ) ) {
			// Fallback: pick from all users
			$rows = $this->query(
				"SELECT user_id FROM {$t} ORDER BY RAND() LIMIT %d",
				array( (int) $count )
			);
		}

		return array_map( 'intval', array_column( (array) $rows, 'user_id' ) );
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get active legendary quests for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_active( $user_id ) {
		$t      = $this->table( 'user_quests' );
		$uid    = (int) $user_id;
		return $this->query(
			"SELECT * FROM {$t} WHERE user_id = %d AND quest_type = 'legendary' AND status IN ('pending','active') ORDER BY assigned_at DESC",
			array( $uid )
		);
	}

	/**
	 * Get all legendary quests ever assigned.
	 *
	 * @param int $user_id Optional. When provided, returns only that user's quests.
	 *                     When 0 (default), returns all legendary quests (admin use).
	 * @return array
	 */
	public function get_all( $user_id = 0 ) {
		$t = $this->table( 'user_quests' );

		if ( $user_id ) {
			return $this->query(
				"SELECT * FROM {$t} WHERE user_id = %d AND quest_type = 'legendary' ORDER BY assigned_at DESC",
				array( (int) $user_id )
			);
		}

		return $this->query(
			"SELECT * FROM {$t} WHERE quest_type = 'legendary' ORDER BY assigned_at DESC"
		);
	}
}
