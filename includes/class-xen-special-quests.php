<?php
/**
 * Special quest system – weekly multi-day challenges with large rewards.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Special_Quests
 */
class Xen_Special_Quests extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Generate ─────────────────────────────────────────────────────────

	/**
	 * Assign the week's special quest to a user.
	 *
	 * Only assigns if the user doesn't already have an active special quest.
	 *
	 * @param int $user_id WP user ID.
	 * @return int|false Quest ID or false.
	 */
	public function generate_for_user( $user_id ) {
		$user_id = (int) $user_id;

		// Skip if already has a special quest active or pending
		$active  = xen_levelup()->quests->get_user_quests( $user_id, 'special', 'active' );
		$pending = xen_levelup()->quests->get_user_quests( $user_id, 'special', 'pending' );
		if ( ! empty( $active ) || ! empty( $pending ) ) {
			return false;
		}

		$templates = xen_levelup()->quests->get_templates( 'all', 'special' );
		if ( empty( $templates ) ) {
			return false;
		}

		shuffle( $templates );
		$tmpl = $templates[0];

		// Special quests expire at the end of the week
		$end_of_week = gmdate( 'Y-m-d H:i:s', strtotime( 'next monday midnight' ) - 1 );

		$id = xen_levelup()->quests->assign_quest( $user_id, array(
			'template_id' => (int) $tmpl->id,
			'title'       => $tmpl->title,
			'description' => $tmpl->description,
			'category'    => $tmpl->category,
			'difficulty'  => $tmpl->difficulty,
			'quest_type'  => 'special',
			'xp_reward'   => (int) $tmpl->xp_reward,
			'coin_reward' => (int) $tmpl->coin_reward,
			'stat_rewards'=> $tmpl->stat_rewards ? json_decode( $tmpl->stat_rewards, true ) : array(),
			'status'      => 'pending',
			'quest_date'  => current_time( 'Y-m-d' ),
			'expires_at'  => $end_of_week,
		) );

		if ( $id ) {
			xen_levelup()->notifications->add(
				$user_id,
				'special_quest',
				__( '🌟 Weekly Special Quest!', 'xen-levelup' ),
				sprintf(
					/* translators: %s = quest title */
					__( 'New special quest available: "%s". Complete it before the week ends!', 'xen-levelup' ),
					$tmpl->title
				),
				array( 'quest_id' => $id )
			);
		}

		return $id;
	}

	/**
	 * Assign the weekly special quest to all active users (cron callback).
	 */
	public function generate_for_all_users() {
		$t    = $this->table( 'user_profiles' );
		$rows = $this->query( "SELECT user_id FROM {$t}" );
		foreach ( (array) $rows as $row ) {
			$this->generate_for_user( $row->user_id );
		}
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get active special quests for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_active( $user_id ) {
		$t   = $this->table( 'user_quests' );
		$uid = (int) $user_id;
		return $this->query(
			"SELECT * FROM {$t} WHERE user_id = %d AND quest_type = 'special' AND status IN ('pending','active') ORDER BY assigned_at DESC",
			array( $uid )
		);
	}
}
