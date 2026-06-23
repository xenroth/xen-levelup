<?php
/**
 * Leveling system – XP formulas, level-up processing, and progress calculation.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Leveling
 */
class Xen_Leveling extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── XP Formula ───────────────────────────────────────────────────────

	/**
	 * XP required to advance FROM $level TO $level + 1.
	 *
	 * Approximate milestones:
	 *   L1 → L2 :   100 XP
	 *   L9 → L10:  ~2 000 XP
	 *  L19 → L20: ~10 000 XP
	 *  L49 → L50: ~90 000 XP
	 *  L99 → L100: ~800 000 XP
	 *
	 * @param int $level Current level (1–99).
	 * @return int
	 */
	public function xp_for_next_level( $level ) {
		$level = max( 1, (int) $level );
		if ( $level >= XEN_MAX_LEVEL ) {
			return 0;
		}
		// Base quadratic + exponential component for high-level steepness
		$xp = (int) floor( 100 * pow( $level, 1.9 ) * ( 1 + $level * 0.005 ) );
		return max( 100, $xp );
	}

	/**
	 * Total cumulative XP needed to reach $level from level 1.
	 *
	 * @param int $level Target level.
	 * @return int
	 */
	public function total_xp_for_level( $level ) {
		$level = (int) $level;
		$total = 0;
		for ( $i = 1; $i < $level; $i++ ) {
			$total += $this->xp_for_next_level( $i );
		}
		return $total;
	}

	/**
	 * Determine what level a user should be given their total accumulated XP.
	 *
	 * @param int $total_xp Total accumulated XP.
	 * @return int Level (1–100).
	 */
	public function level_from_xp( $total_xp ) {
		$total_xp = (int) $total_xp;
		$level    = 1;
		$running  = 0;
		while ( $level < XEN_MAX_LEVEL ) {
			$needed = $this->xp_for_next_level( $level );
			if ( $running + $needed > $total_xp ) {
				break;
			}
			$running += $needed;
			$level++;
		}
		return $level;
	}

	/**
	 * XP earned within the current level (not cumulative).
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function xp_in_current_level( $user_id ) {
		$profile      = xen_levelup()->user->get_profile( $user_id );
		$total_xp     = $profile ? (int) $profile->experience : 0;
		$level        = $profile ? (int) $profile->level : 1;
		$xp_to_level  = $this->total_xp_for_level( $level );
		return max( 0, $total_xp - $xp_to_level );
	}

	/**
	 * Progress percentage towards the next level (0–100).
	 *
	 * @param int $user_id WP user ID.
	 * @return float
	 */
	public function level_progress_percent( $user_id ) {
		$profile  = xen_levelup()->user->get_profile( $user_id );
		if ( ! $profile ) {
			return 0.0;
		}
		$level = (int) $profile->level;
		if ( $level >= XEN_MAX_LEVEL ) {
			return 100.0;
		}
		$xp_in    = $this->xp_in_current_level( $user_id );
		$xp_need  = $this->xp_for_next_level( $level );
		if ( $xp_need <= 0 ) {
			return 100.0;
		}
		return round( min( 100, ( $xp_in / $xp_need ) * 100 ), 2 );
	}

	// ─── Add XP ───────────────────────────────────────────────────────────

	/**
	 * Add XP to a user and handle level-ups.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param int    $xp_amount   XP to add.
	 * @param string $source_type Source type (quest, task, habit, achievement…).
	 * @param int    $source_id   Source record ID.
	 * @param string $description Human-readable description.
	 * @return array { leveled_up: bool, old_level: int, new_level: int, new_xp: int }
	 */
	public function add_xp( $user_id, $xp_amount, $source_type = 'general', $source_id = 0, $description = '' ) {
		$user_id   = (int) $user_id;
		$xp_amount = max( 0, (int) $xp_amount );

		if ( ! $xp_amount ) {
			return array( 'leveled_up' => false );
		}

		$profile   = xen_levelup()->user->get_profile( $user_id );
		if ( ! $profile ) {
			return array( 'leveled_up' => false );
		}

		$old_level = (int) $profile->level;

		// ── Rebirth trigger ───────────────────────────────────────────────
		// A user at max level (100) who earns any additional XP is reborn.
		if ( $old_level >= XEN_MAX_LEVEL ) {
			return $this->process_rebirth( $user_id, $xp_amount, $source_type, $source_id, $description );
		}

		$new_xp    = (int) $profile->experience + $xp_amount;

		// Recalculate level
		$new_level  = $this->level_from_xp( $new_xp );
		$new_level  = min( XEN_MAX_LEVEL, $new_level );
		$leveled_up = $new_level > $old_level;

		// Persist XP + level
		xen_levelup()->user->update_profile( $user_id, array(
			'experience' => $new_xp,
			'level'      => $new_level,
		) );

		// Ensure stored `rank_title` stays in sync for non-rebirthed users.
		// Rebirths remain the canonical tier change; for users with zero
		// rebirths we keep the legacy behavior and derive the title from level.
		$profile = xen_levelup()->user->get_profile( $user_id );
		if ( $profile && (int) ( $profile->rebirth_count ?? 0 ) === 0 ) {
			xen_levelup()->user->sync_rank_title( $user_id, 0 );
		}

		// Log the XP transaction
		$this->insert( 'xp_log', array(
			'user_id'      => $user_id,
			'xp_amount'    => $xp_amount,
			'source_type'  => sanitize_text_field( $source_type ),
			'source_id'    => $source_id ? (int) $source_id : null,
			'description'  => sanitize_text_field( $description ),
			'level_before' => $old_level,
			'level_after'  => $new_level,
		), array( '%d', '%d', '%s', '%d', '%s', '%d', '%d' ) );

		$result = array(
			'leveled_up' => $leveled_up,
			'old_level'  => $old_level,
			'new_level'  => $new_level,
			'new_xp'     => $new_xp,
		);

		// Fire action hooks for modules to react
		do_action( 'xen_xp_added', $user_id, $result );

		if ( $leveled_up ) {
			do_action( 'xen_level_up', $user_id, $old_level, $new_level );
			$this->on_level_up( $user_id, $old_level, $new_level );
		}

		return $result;
	}

	// ─── Rebirth ──────────────────────────────────────────────────────────

	/**
	 * Process a rebirth event: reset level/XP, increment rebirth count, update rank.
	 *
	 * @param int    $user_id
	 * @param int    $xp_amount      XP that triggered the rebirth (logged).
	 * @param string $source_type
	 * @param int    $source_id
	 * @param string $description
	 * @return array
	 */
	private function process_rebirth( $user_id, $xp_amount, $source_type, $source_id, $description ) {
		$profile       = xen_levelup()->user->get_profile( $user_id );
		$old_rebirth   = (int) ( $profile->rebirth_count ?? 0 );
		$new_rebirth   = $old_rebirth + 1;

		// Reset level and XP, increment rebirth count
		xen_levelup()->user->update_profile( $user_id, array(
			'level'         => 1,
			'experience'    => 0,
			'rebirth_count' => $new_rebirth,
		) );

		// Sync rank title from the ranks table
		xen_levelup()->user->sync_rank_title( $user_id, $new_rebirth );

		// Refresh profile to get new rank_title
		$profile     = xen_levelup()->user->get_profile( $user_id );
		$new_rank    = $profile ? $profile->rank_title : '';

		// Log it
		$this->insert( 'xp_log', array(
			'user_id'      => $user_id,
			'xp_amount'    => $xp_amount,
			'source_type'  => sanitize_text_field( $source_type ),
			'source_id'    => $source_id ? (int) $source_id : null,
			'description'  => sanitize_text_field( 'REBIRTH #' . $new_rebirth . ' — ' . $description ),
			'level_before' => XEN_MAX_LEVEL,
			'level_after'  => 1,
		), array( '%d', '%d', '%s', '%d', '%s', '%d', '%d' ) );

		// Coin bonus for rebirth (500 × rebirth_count)
		$coin_bonus = 500 * $new_rebirth;
		xen_levelup()->currency->add(
			$user_id,
			$coin_bonus,
			'rebirth',
			/* translators: %d = rebirth number */
			sprintf( __( '🔄 Rebirth #%d Bonus', 'xen-levelup' ), $new_rebirth )
		);

		// Notification
		xen_levelup()->notifications->add(
			$user_id,
			'rebirth',
			/* translators: %d = rebirth number, %s = rank title */
			sprintf( __( '🔄 REBIRTH #%1$d — You are now %2$s!', 'xen-levelup' ), $new_rebirth, $new_rank ),
			sprintf(
				/* translators: 1: rebirth number, 2: rank title, 3: coin bonus */
				__( 'You reached Level 100 and have been reborn! Level reset to 1. You are now %2$s and received %3$s bonus coins. Keep rising, Hunter!', 'xen-levelup' ),
				$new_rebirth,
				$new_rank,
				number_format( $coin_bonus )
			),
			array( 'rebirth_count' => $new_rebirth, 'new_rank' => $new_rank )
		);

		$result = array(
			'reborn'       => true,
			'leveled_up'   => false,
			'old_level'    => XEN_MAX_LEVEL,
			'new_level'    => 1,
			'new_xp'       => 0,
			'rebirth_count'=> $new_rebirth,
			'new_rank'     => $new_rank,
		);

		do_action( 'xen_xp_added',  $user_id, $result );
		do_action( 'xen_rebirth',   $user_id, $new_rebirth, $new_rank );

		return $result;
	}

	// ─── Level-Up Processing ──────────────────────────────────────────────

	/**
	 * Handle level-up rewards: coins, notifications, etc.
	 *
	 * @param int $user_id   WP user ID.
	 * @param int $old_level Previous level.
	 * @param int $new_level New level.
	 */
	private function on_level_up( $user_id, $old_level, $new_level ) {
		// Coin reward per level gained
		$coins_per_level = 50 + ( $new_level * 5 );
		for ( $l = $old_level + 1; $l <= $new_level; $l++ ) {
			xen_levelup()->currency->add(
				$user_id,
				$coins_per_level,
				'level_up',
				/* translators: %d = level number */
				sprintf( __( 'Level Up! Reached Level %d', 'xen-levelup' ), $l )
			);
		}

		// Send notification
		xen_levelup()->notifications->add(
			$user_id,
			'level_up',
			/* translators: %d = level number */
			sprintf( __( '🎉 Level Up! You are now Level %d!', 'xen-levelup' ), $new_level ),
			sprintf(
				/* translators: 1: old level, 2: new level */
				__( 'You advanced from Level %1$d to Level %2$d. Keep pushing forward, Hunter!', 'xen-levelup' ),
				$old_level,
				$new_level
			),
			array( 'old_level' => $old_level, 'new_level' => $new_level )
		);
	}

	// ─── Scaled Rewards ───────────────────────────────────────────────────

	/**
	 * Scale a base XP reward by the user's current level.
	 *
	 * Higher-level users get more XP from the same quest.
	 *
	 * @param int $base_xp  Base XP value.
	 * @param int $level    User's current level.
	 * @return int
	 */
	public function scale_xp( $base_xp, $level ) {
		$level     = max( 1, (int) $level );
		$base_xp   = max( 0, (int) $base_xp );
		// Small multiplier so higher-level players still feel rewarded
		$multiplier = 1 + ( ( $level - 1 ) * 0.01 ); // +1% per level above 1
		return (int) round( $base_xp * $multiplier );
	}

	// ─── XP History ───────────────────────────────────────────────────────

	/**
	 * Retrieve recent XP log entries for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $limit   Max rows.
	 * @return array
	 */
	public function get_xp_log( $user_id, $limit = 20 ) {
		$t = $this->table( 'xp_log' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $this->query(
			"SELECT * FROM {$t} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
			array( (int) $user_id, (int) $limit )
		);
	}
}
