<?php
/**
 * Onboarding wizard – multi-step assessment and stat generation.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Onboarding
 */
class Xen_Onboarding extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Redirect ─────────────────────────────────────────────────────────

	/**
	 * After login, redirect new users to the onboarding page.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       WP_User object.
	 */
	public function maybe_redirect_to_onboarding( $user_login, $user ) {
		if ( is_admin() ) {
			return;
		}

		$user_id = (int) $user->ID;
		$profile = xen_levelup()->user->get_profile( $user_id );

		if ( ! $profile ) {
			xen_levelup()->user->create_profile( $user_id );
			$profile = xen_levelup()->user->get_profile( $user_id );
		}

		if ( $profile && ! $profile->onboarding_done ) {
			$page_id = (int) get_option( 'xen_levelup_onboarding_page', 0 );
			if ( $page_id ) {
				wp_safe_redirect( get_permalink( $page_id ) );
				exit;
			}
		}
	}

	// ─── Status ───────────────────────────────────────────────────────────

	/**
	 * Check whether a user has completed onboarding.
	 *
	 * @param int $user_id WP user ID.
	 * @return bool
	 */
	public function is_complete( $user_id ) {
		$profile = xen_levelup()->user->get_profile( (int) $user_id );
		return $profile ? (bool) $profile->onboarding_done : false;
	}

	/**
	 * Get the current onboarding step for a user (0 = not started).
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_current_step( $user_id ) {
		$row = $this->get_row( 'onboarding', array( 'user_id' => (int) $user_id ) );
		return $row ? (int) $row->current_step : 0;
	}

	// ─── Save Steps ───────────────────────────────────────────────────────

	/**
	 * Save Step 1 – personality assessment.
	 *
	 * @param int   $user_id     WP user ID.
	 * @param array $personality Trait => 1–10 value map.
	 * @return bool
	 */
	public function save_step_1( $user_id, array $personality ) {
		$user_id = (int) $user_id;
		$clean   = $this->sanitize_personality( $personality );

		$this->ensure_row( $user_id );
		$this->update( 'onboarding', array(
			'personality_data' => wp_json_encode( $clean ),
			'current_step'     => max( 1, $this->get_current_step( $user_id ) ),
		), array( 'user_id' => $user_id ) );

		return true;
	}

	/**
	 * Save Step 2 – interests assessment.
	 *
	 * @param int   $user_id   WP user ID.
	 * @param array $interests Interest slug => 1–10 score map.
	 * @return bool
	 */
	public function save_step_2( $user_id, array $interests ) {
		$user_id = (int) $user_id;
		$clean   = $this->sanitize_interests( $interests );

		$this->ensure_row( $user_id );
		$this->update( 'onboarding', array(
			'interests_data' => wp_json_encode( $clean ),
			'current_step'   => max( 2, $this->get_current_step( $user_id ) ),
		), array( 'user_id' => $user_id ) );

		return true;
	}

	/**
	 * Save Step 3 – life priorities (ordered list).
	 *
	 * @param int   $user_id    WP user ID.
	 * @param array $priorities Ordered category slugs.
	 * @return bool
	 */
	public function save_step_3( $user_id, array $priorities ) {
		$user_id = (int) $user_id;
		$clean   = $this->sanitize_priorities( $priorities );

		$this->ensure_row( $user_id );
		$this->update( 'onboarding', array(
			'priorities_data' => wp_json_encode( $clean ),
			'current_step'    => 3,
		), array( 'user_id' => $user_id ) );

		return true;
	}

	// ─── Complete Onboarding ──────────────────────────────────────────────

	/**
	 * Finalize onboarding: generate stats and mark profile as complete.
	 *
	 * @param int $user_id WP user ID.
	 * @return array|WP_Error Generated stats or error.
	 */
	public function complete( $user_id ) {
		$user_id = (int) $user_id;
		$row     = $this->get_row( 'onboarding', array( 'user_id' => $user_id ) );

		if ( ! $row ) {
			return new WP_Error( 'no_data', __( 'Onboarding data not found.', 'xen-levelup' ) );
		}

		$personality = $row->personality_data ? json_decode( $row->personality_data, true ) : array();
		$interests   = $row->interests_data   ? json_decode( $row->interests_data,   true ) : array();
		$priorities  = $row->priorities_data  ? json_decode( $row->priorities_data,  true ) : array();

		// Generate and persist initial stats
		$stats = xen_levelup()->stats->generate_initial_stats( $user_id, $priorities, $interests, $personality );

		// Generate first daily quests
		xen_levelup()->daily_quests->generate_for_user( $user_id );

		// Mark onboarding complete
		$this->update( 'onboarding', array( 'completed' => 1, 'current_step' => 4 ), array( 'user_id' => $user_id ) );
		xen_levelup()->user->update_profile( $user_id, array( 'onboarding_done' => 1 ) );

		// Welcome notification
		xen_levelup()->notifications->add(
			$user_id,
			'welcome',
			__( '🎮 Welcome, Hunter!', 'xen-levelup' ),
			__( 'Your character stats have been generated. Your first daily quests are ready. Begin your journey!', 'xen-levelup' ),
			array()
		);

		// Award initial coins
		xen_levelup()->currency->add( $user_id, 100, 'welcome', __( 'Welcome bonus', 'xen-levelup' ) );

		do_action( 'xen_onboarding_complete', $user_id );

		return $stats;
	}

	// ─── Data Accessors ───────────────────────────────────────────────────

	/**
	 * Get all onboarding data for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null
	 */
	public function get_data( $user_id ) {
		return $this->get_row( 'onboarding', array( 'user_id' => (int) $user_id ) );
	}

	// ─── Valid Slugs ─────────────────────────────────────────────────────

	/** @return string[] Valid personality trait keys */
	public static function personality_traits() {
		return array(
			'introvert_extrovert',
			'analytical_creative',
			'competitive_cooperative',
			'active_passive',
			'structured_flexible',
		);
	}

	/** @return string[] Valid interest slugs */
	public static function interest_slugs() {
		return array(
			'physical_fitness', 'strength_training', 'sports',
			'reading', 'learning', 'career_success', 'business',
			'leadership', 'communication', 'productivity',
			'mental_health', 'spiritual_growth', 'longevity',
			'relationships', 'creativity',
		);
	}

	/** @return string[] Valid life priority categories */
	public static function priority_categories() {
		return array(
			'physique', 'intelligence', 'knowledge', 'discipline',
			'wealth', 'communication', 'leadership', 'relationships',
			'spirituality', 'longevity',
		);
	}

	// ─── Sanitization ────────────────────────────────────────────────────

	/**
	 * Sanitize personality data – clamp each trait to 1–10.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_personality( array $data ) {
		$clean = array();
		foreach ( self::personality_traits() as $trait ) {
			$val         = isset( $data[ $trait ] ) ? (int) $data[ $trait ] : 5;
			$clean[$trait] = max( 1, min( 10, $val ) );
		}
		return $clean;
	}

	/**
	 * Sanitize interest data – clamp each score to 1–10.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private function sanitize_interests( array $data ) {
		$clean = array();
		foreach ( self::interest_slugs() as $slug ) {
			$val         = isset( $data[ $slug ] ) ? (int) $data[ $slug ] : 5;
			$clean[$slug] = max( 1, min( 10, $val ) );
		}
		return $clean;
	}

	/**
	 * Sanitize priority list – keep only valid slugs, max 10.
	 *
	 * @param array $data Raw ordered array.
	 * @return array
	 */
	private function sanitize_priorities( array $data ) {
		$valid = self::priority_categories();
		$clean = array();
		foreach ( $data as $item ) {
			$slug = sanitize_key( $item );
			if ( in_array( $slug, $valid, true ) && ! in_array( $slug, $clean, true ) ) {
				$clean[] = $slug;
			}
		}
		// Fill defaults if user sent fewer than all categories
		foreach ( $valid as $cat ) {
			if ( ! in_array( $cat, $clean, true ) ) {
				$clean[] = $cat;
			}
		}
		return array_slice( $clean, 0, 10 );
	}

	// ─── Private Helpers ─────────────────────────────────────────────────

	/**
	 * Ensure an onboarding row exists for a user.
	 *
	 * @param int $user_id WP user ID.
	 */
	private function ensure_row( $user_id ) {
		if ( ! $this->row_exists( 'onboarding', array( 'user_id' => (int) $user_id ) ) ) {
			$this->insert( 'onboarding', array( 'user_id' => (int) $user_id, 'current_step' => 0 ), array( '%d', '%d' ) );
		}
	}
}
