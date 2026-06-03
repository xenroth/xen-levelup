<?php
/**
 * Random quest system – time-limited surprise quests assigned throughout the day.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Random_Quests
 */
class Xen_Random_Quests extends Xen_Database {

	/** How many hours a random quest remains active before expiring */
	const EXPIRY_HOURS = 3;

	/** Maximum active random quests a user can have at once */
	const MAX_ACTIVE = 2;

	public function __construct() {
		parent::__construct();
	}

	// ─── Generate ─────────────────────────────────────────────────────────

	/**
	 * Try to assign a new random quest to a user.
	 *
	 * Will NOT assign if the user already has MAX_ACTIVE random quests.
	 *
	 * @param int $user_id WP user ID.
	 * @return int|false Quest ID or false if skipped/failed.
	 */
	public function generate_for_user( $user_id ) {
		$user_id = (int) $user_id;

		// Check active limit
		$active = xen_levelup()->quests->get_user_quests( $user_id, 'random', 'active' );
		if ( count( $active ) >= self::MAX_ACTIVE ) {
			return false;
		}

		// Pick a random template
		$templates = xen_levelup()->quests->get_templates( 'all', 'daily' ); // reuse daily templates
		if ( empty( $templates ) ) {
			return false;
		}

		shuffle( $templates );
		$tmpl = $templates[0];

		$expires = gmdate( 'Y-m-d H:i:s', time() + ( self::EXPIRY_HOURS * HOUR_IN_SECONDS ) );

		$id = xen_levelup()->quests->assign_quest( $user_id, array(
			'template_id' => (int) $tmpl->id,
			'title'       => $tmpl->title,
			'description' => $tmpl->description,
			'category'    => $tmpl->category,
			'difficulty'  => $tmpl->difficulty,
			'quest_type'  => 'random',
			'xp_reward'   => (int) $tmpl->xp_reward,
			'coin_reward' => (int) $tmpl->coin_reward,
			'stat_rewards'=> $tmpl->stat_rewards ? json_decode( $tmpl->stat_rewards, true ) : array(),
			'quest_date'  => current_time( 'Y-m-d' ),
			'expires_at'  => $expires,
		) );

		if ( $id ) {
			// Notify the user
			xen_levelup()->notifications->add(
				$user_id,
				'random_quest',
				__( '⚡ Random Quest Available!', 'xen-levelup' ),
				sprintf(
					/* translators: 1: quest title, 2: hours */
					__( 'New quest: "%1$s". Complete it within %2$d hours!', 'xen-levelup' ),
					$tmpl->title,
					self::EXPIRY_HOURS
				),
				array( 'quest_id' => $id )
			);
		}

		return $id;
	}

	/**
	 * Assign a random quest to every active user (cron callback).
	 */
	public function generate_for_all_users() {
		$t    = $this->table( 'user_profiles' );
		$rows = $this->query( "SELECT user_id FROM {$t}" );
		foreach ( (array) $rows as $row ) {
			// 30 % chance per user per hour so not everyone gets one at once
			if ( wp_rand( 1, 100 ) <= 30 ) {
				$this->generate_for_user( $row->user_id );
			}
		}
	}

	// ─── Expire ───────────────────────────────────────────────────────────

	/**
	 * Expire stale random quests.
	 *
	 * Delegates to the base quest class.
	 */
	public function expire_stale() {
		xen_levelup()->quests->expire_stale_quests();
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get active random quests for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_active( $user_id ) {
		return xen_levelup()->quests->get_user_quests( $user_id, 'random', 'active' );
	}
}
