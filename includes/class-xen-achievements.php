<?php
/**
 * Achievement system – definitions, condition checking, and awarding.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Achievements
 */
class Xen_Achievements extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Check Entry Points ───────────────────────────────────────────────

	/**
	 * Check level-based achievements after XP is added.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $xp_data XP result from Xen_Leveling::add_xp().
	 */
	public function check_level_achievements( $user_id, $xp_data ) {
		if ( empty( $xp_data['leveled_up'] ) ) {
			return;
		}
		$this->check_and_award( $user_id, 'level' );
	}

	/**
	 * Check quest-based achievements after a quest is completed.
	 *
	 * @param int   $user_id   WP user ID.
	 * @param array $quest_data Quest completion data.
	 */
	public function check_quest_achievements( $user_id, $quest_data ) {
		$this->check_and_award( $user_id, 'total_quests' );
		$this->check_and_award( $user_id, 'category_quests' );
		if ( isset( $quest_data['quest']->quest_type ) && 'legendary' === $quest_data['quest']->quest_type ) {
			$this->check_and_award( $user_id, 'legendary_quests' );
		}
	}

	/**
	 * Check task-based achievements.
	 *
	 * @param int   $user_id   WP user ID.
	 * @param array $task_data Task completion data.
	 */
	public function check_task_achievements( $user_id, $task_data ) {
		$this->check_and_award( $user_id, 'total_tasks' );
	}

	/**
	 * Check habit-based achievements.
	 *
	 * @param int   $user_id    WP user ID.
	 * @param array $habit_data Habit log data.
	 */
	public function check_habit_achievements( $user_id, $habit_data ) {
		$this->check_and_award( $user_id, 'habit_streak' );
	}

	// ─── Core Checker ─────────────────────────────────────────────────────

	/**
	 * Check all unearned achievements of a given requirement type and award eligible ones.
	 *
	 * @param int    $user_id      WP user ID.
	 * @param string $req_type     Requirement type to check.
	 */
	private function check_and_award( $user_id, $req_type ) {
		$user_id = (int) $user_id;

		// Fetch all achievements of this type the user hasn't earned yet
		$pending = $this->get_unearned_by_type( $user_id, $req_type );

		foreach ( $pending as $achievement ) {
			if ( $this->is_condition_met( $user_id, $achievement ) ) {
				$this->award( $user_id, $achievement );
			}
		}
	}

	/**
	 * Test whether the condition for a specific achievement is met.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param object $achievement Achievement DB row.
	 * @return bool
	 */
	private function is_condition_met( $user_id, $achievement ) {
		$req_value = (int) $achievement->requirement_value;
		$extra     = $achievement->requirement_extra ?? '';

		switch ( $achievement->requirement_type ) {

			case 'level':
				$level = xen_levelup()->user->get_level( $user_id );
				return $level >= $req_value;

			case 'total_quests':
				$profile = xen_levelup()->user->get_profile( $user_id );
				return $profile && (int) $profile->total_quests >= $req_value;

			case 'total_tasks':
				$profile = xen_levelup()->user->get_profile( $user_id );
				return $profile && (int) $profile->total_tasks >= $req_value;

			case 'habit_streak':
				$longest = xen_levelup()->habits->get_longest_streak( $user_id );
				return $longest >= $req_value;

			case 'legendary_quests':
				$count = $this->count_completed_quests_by_type( $user_id, 'legendary' );
				return $count >= $req_value;

			case 'category_quests':
				if ( ! $extra ) return false;
				$count = $this->count_completed_quests_by_category( $user_id, $extra );
				return $count >= $req_value;

			default:
				return false;
		}
	}

	// ─── Award ────────────────────────────────────────────────────────────

	/**
	 * Grant an achievement to a user.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param object $achievement Achievement DB row.
	 */
	public function award( $user_id, $achievement ) {
		$user_id = (int) $user_id;
		$ach_id  = (int) $achievement->id;

		// Idempotent check
		if ( $this->row_exists( 'user_achievements', array( 'user_id' => $user_id, 'achievement_id' => $ach_id ) ) ) {
			return;
		}

		$this->insert(
			'user_achievements',
			array( 'user_id' => $user_id, 'achievement_id' => $ach_id ),
			array( '%d', '%d' )
		);

		// Award bonus XP
		if ( $achievement->xp_reward > 0 ) {
			xen_levelup()->leveling->add_xp(
				$user_id,
				(int) $achievement->xp_reward,
				'achievement',
				$ach_id,
				sprintf( __( 'Achievement: %s', 'xen-levelup' ), $achievement->title )
			);
		}

		// Award bonus coins
		if ( $achievement->coin_reward > 0 ) {
			xen_levelup()->currency->add(
				$user_id,
				(int) $achievement->coin_reward,
				'achievement',
				sprintf( __( 'Achievement: %s', 'xen-levelup' ), $achievement->title ),
				$ach_id,
				'achievement'
			);
		}

		// Notification
		xen_levelup()->notifications->add(
			$user_id,
			'achievement',
			sprintf(
				/* translators: %s = achievement title */
				__( '🏆 Achievement Unlocked: %s', 'xen-levelup' ),
				$achievement->title
			),
			$achievement->description,
			array( 'achievement_id' => $ach_id, 'icon' => $achievement->icon )
		);

		do_action( 'xen_achievement_unlocked', $user_id, $achievement );
	}

	// ─── Retrieve ─────────────────────────────────────────────────────────

	/**
	 * Get all achievements earned by a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return array
	 */
	public function get_user_achievements( $user_id ) {
		$ua = $this->table( 'user_achievements' );
		$a  = $this->table( 'achievements' );
		return $this->query(
			"SELECT a.*, ua.earned_at FROM {$a} a
			 INNER JOIN {$ua} ua ON ua.achievement_id = a.id
			 WHERE ua.user_id = %d
			 ORDER BY ua.earned_at DESC",
			array( (int) $user_id )
		);
	}

	/**
	 * Get all achievement definitions (optionally with earned status for a user).
	 *
	 * @param int $user_id WP user ID (0 for no earned status).
	 * @return array
	 */
	public function get_all( $user_id = 0 ) {
		$a  = $this->table( 'achievements' );
		$ua = $this->table( 'user_achievements' );

		if ( $user_id ) {
			return $this->query(
				"SELECT a.*, (ua.achievement_id IS NOT NULL) AS earned, ua.earned_at
				 FROM {$a} a
				 LEFT JOIN {$ua} ua ON ua.achievement_id = a.id AND ua.user_id = %d
				 WHERE a.is_active = 1
				 ORDER BY a.category ASC, a.requirement_value ASC",
				array( (int) $user_id )
			);
		}

		return $this->get_rows( 'achievements', array( 'is_active' => 1 ), 'category ASC, requirement_value ASC' );
	}

	// ─── Private Helpers ─────────────────────────────────────────────────

	/**
	 * Get unearned achievements of a specific requirement type.
	 *
	 * @param int    $user_id  WP user ID.
	 * @param string $req_type Requirement type.
	 * @return array
	 */
	private function get_unearned_by_type( $user_id, $req_type ) {
		$a  = $this->table( 'achievements' );
		$ua = $this->table( 'user_achievements' );
		return $this->query(
			"SELECT a.* FROM {$a} a
			 LEFT JOIN {$ua} ua ON ua.achievement_id = a.id AND ua.user_id = %d
			 WHERE a.requirement_type = %s AND a.is_active = 1 AND ua.achievement_id IS NULL",
			array( (int) $user_id, $req_type )
		);
	}

	/**
	 * Count completed quests by type.
	 *
	 * @param int    $user_id    WP user ID.
	 * @param string $quest_type Quest type.
	 * @return int
	 */
	private function count_completed_quests_by_type( $user_id, $quest_type ) {
		$t = $this->table( 'user_quests' );
		return (int) $this->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE user_id = %d AND quest_type = %s AND status = 'completed'",
			array( (int) $user_id, $quest_type )
		);
	}

	/**
	 * Count completed quests by category.
	 *
	 * @param int    $user_id  WP user ID.
	 * @param string $category Category slug.
	 * @return int
	 */
	private function count_completed_quests_by_category( $user_id, $category ) {
		$t = $this->table( 'user_quests' );
		return (int) $this->get_var(
			"SELECT COUNT(*) FROM {$t} WHERE user_id = %d AND category = %s AND status = 'completed'",
			array( (int) $user_id, $category )
		);
	}
}
