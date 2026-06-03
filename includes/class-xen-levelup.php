<?php
/**
 * Core plugin bootstrap class.
 *
 * Loads all modules, registers global hooks, enqueues assets.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_LevelUp
 */
final class Xen_LevelUp {

	/** @var Xen_LevelUp|null Singleton instance */
	private static $instance = null;

	// Module references
	public $user;
	public $stats;
	public $leveling;
	public $currency;
	public $quests;
	public $daily_quests;
	public $random_quests;
	public $special_quests;
	public $legendary_quests;
	public $tasks;
	public $habits;
	public $achievements;
	public $rankings;
	public $shop;
	public $onboarding;
	public $notifications;
	public $cron;
	public $shortcodes;
	public $ajax;
	public $rest_api;
	public $daily_checkin;
	public $overview;
	public $social;
	public $ranks;

	/**
	 * Get or create the singleton instance.
	 *
	 * @return Xen_LevelUp
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Private constructor – use get_instance(). */
	private function __construct() {
		$this->load_dependencies();
		$this->init_modules();
		$this->register_hooks();
	}

	/** Prevent cloning. */
	private function __clone() {}

	/** Prevent unserialization. */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	// ─── Load Dependencies ────────────────────────────────────────────────

	/**
	 * Explicitly load all class files (fallback for environments without autoloader).
	 */
	private function load_dependencies() {
		$includes = array(
			'includes/class-xen-installer.php',
			'includes/class-xen-database.php',
			'includes/class-xen-user.php',
			'includes/class-xen-stats.php',
			'includes/class-xen-leveling.php',
			'includes/class-xen-currency.php',
			'includes/class-xen-quests.php',
			'includes/class-xen-daily-quests.php',
			'includes/class-xen-random-quests.php',
			'includes/class-xen-special-quests.php',
			'includes/class-xen-legendary-quests.php',
			'includes/class-xen-tasks.php',
			'includes/class-xen-habits.php',
			'includes/class-xen-achievements.php',
			'includes/class-xen-rankings.php',
			'includes/class-xen-shop.php',
			'includes/class-xen-onboarding.php',
			'includes/class-xen-notifications.php',
			'includes/class-xen-cron.php',
			'includes/class-xen-shortcodes.php',
			'includes/class-xen-ajax.php',
			'includes/class-xen-rest-api.php',
			'includes/class-xen-daily-checkin.php',
			'includes/class-xen-overview.php',
			'includes/class-xen-social.php',
			'includes/class-xen-ranks.php',
		);

		foreach ( $includes as $file ) {
			$path = XEN_LEVELUP_PLUGIN_DIR . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}

		// Admin only
		if ( is_admin() ) {
			$admin = XEN_LEVELUP_PLUGIN_DIR . 'admin/class-xen-admin.php';
			if ( file_exists( $admin ) ) {
				require_once $admin;
			}
		}
	}

	// ─── Init Modules ─────────────────────────────────────────────────────

	/**
	 * Instantiate all module classes.
	 */
	private function init_modules() {
		$this->user              = new Xen_User();
		$this->stats             = new Xen_Stats();
		$this->leveling          = new Xen_Leveling();
		$this->currency          = new Xen_Currency();
		$this->quests            = new Xen_Quests();
		$this->daily_quests      = new Xen_Daily_Quests();
		$this->random_quests     = new Xen_Random_Quests();
		$this->special_quests    = new Xen_Special_Quests();
		$this->legendary_quests  = new Xen_Legendary_Quests();
		$this->tasks             = new Xen_Tasks();
		$this->habits            = new Xen_Habits();
		$this->achievements      = new Xen_Achievements();
		$this->rankings          = new Xen_Rankings();
		$this->shop              = new Xen_Shop();
		$this->onboarding        = new Xen_Onboarding();
		$this->notifications     = new Xen_Notifications();
		$this->cron              = new Xen_Cron();
		$this->shortcodes        = new Xen_Shortcodes();
		$this->ajax              = new Xen_Ajax();
		$this->rest_api          = new Xen_Rest_Api();
		$this->daily_checkin     = new Xen_Daily_Checkin();
		$this->overview          = new Xen_Overview();
		$this->social            = new Xen_Social();
		$this->ranks             = new Xen_Ranks();

		if ( is_admin() ) {
			new Xen_Admin();
		}
	}

	// ─── Hooks ────────────────────────────────────────────────────────────

	/**
	 * Register global WordPress hooks.
	 */
	private function register_hooks() {
		add_action( 'init',                   array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts',     array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_head',                array( $this, 'output_inline_vars' ) );

		// Redirect new users to onboarding on login
		add_action( 'wp_login', array( $this->onboarding, 'maybe_redirect_to_onboarding' ), 10, 2 );

		// Create profile for newly registered users
		add_action( 'user_register', array( $this->user, 'create_profile' ) );

		// Block WP admin for non-admins when the option is enabled
		add_action( 'admin_init', array( $this, 'maybe_block_wp_admin' ) );

		// Custom avatar override
		add_filter( 'get_avatar_url', array( $this, 'custom_avatar_url' ), 10, 3 );

		// Award achievements after XP/quest/task events
		add_action( 'xen_xp_added',             array( $this->achievements, 'check_level_achievements' ), 10, 2 );
		add_action( 'xen_quest_completed',       array( $this->achievements, 'check_quest_achievements' ), 10, 2 );
		add_action( 'xen_task_completed',        array( $this->achievements, 'check_task_achievements' ), 10, 2 );
		add_action( 'xen_habit_logged',          array( $this->achievements, 'check_habit_achievements' ), 10, 2 );

		// Update rankings after profile change
		add_action( 'xen_xp_added',             array( $this->rankings, 'schedule_update' ), 20, 2 );

		// Post to activity feed on key game events
		add_action( 'xen_daily_checkin',   array( $this->social, 'on_checkin' ),        10, 4 );
		add_action( 'xen_task_completed',  array( $this->social, 'on_task_complete' ),  10, 2 );
		add_action( 'xen_quest_completed', array( $this->social, 'on_quest_complete' ), 10, 2 );
		add_action( 'xen_onboarding_complete', array( $this->social, 'on_onboarding_complete' ), 10, 1 );
		add_action( 'xen_rebirth',             array( $this->social, 'on_rebirth' ),              10, 3 );
	}

	/**
	 * Block access to WP admin for non-administrator users.
	 * Allows AJAX requests through unconditionally.
	 */
	public function maybe_block_wp_admin() {
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( ! get_option( 'xen_disable_wp_dashboard', 0 ) ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		$redirect = (int) get_option( 'xen_levelup_dashboard_page', 0 )
			? get_permalink( (int) get_option( 'xen_levelup_dashboard_page' ) )
			: home_url( '/' );
		wp_safe_redirect( esc_url_raw( $redirect ) );
		exit;
	}

	/**
	 * Return the user's custom avatar URL when one has been uploaded.
	 *
	 * @param string $url     Gravatar/default avatar URL.
	 * @param mixed  $id_or_email
	 * @param array  $args
	 * @return string
	 */
	public function custom_avatar_url( $url, $id_or_email, $args ) {
		$user_id = 0;
		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user_id = (int) $id_or_email->ID;
		} elseif ( $id_or_email instanceof \WP_Comment ) {
			$user_id = (int) $id_or_email->user_id;
		}
		if ( ! $user_id ) {
			return $url;
		}
		$custom = get_user_meta( $user_id, 'xen_avatar_url', true );
		return $custom ? esc_url( $custom ) : $url;
	}

	// ─── i18n ─────────────────────────────────────────────────────────────

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'xen-levelup',
			false,
			dirname( XEN_LEVELUP_PLUGIN_BASE ) . '/languages/'
		);
	}

	// ─── Asset Enqueueing ────────────────────────────────────────────────

	/**
	 * Enqueue front-end scripts and styles.
	 */
	public function enqueue_public_assets() {
		$ver = XEN_LEVELUP_VERSION;
		$url = XEN_LEVELUP_PLUGIN_URL;

		// Styles
		wp_enqueue_style( 'xen-main',       $url . 'public/css/xen-main.css',       array(), $ver );
		wp_enqueue_style( 'xen-animations', $url . 'public/css/xen-animations.css', array( 'xen-main' ), $ver );
		wp_enqueue_style( 'xen-components', $url . 'public/css/xen-components.css', array( 'xen-main' ), $ver );
		wp_enqueue_style( 'xen-responsive',  $url . 'public/css/xen-responsive.css',  array( 'xen-main' ), $ver );

		// Scripts
		wp_enqueue_script( 'xen-animations', $url . 'public/js/xen-animations.js', array( 'jquery' ), $ver, true );
		wp_enqueue_script( 'xen-main',       $url . 'public/js/xen-main.js',       array( 'jquery', 'xen-animations' ), $ver, true );
		wp_enqueue_script( 'xen-onboarding', $url . 'public/js/xen-onboarding.js', array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-dashboard',  $url . 'public/js/xen-dashboard.js',  array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-quests',         $url . 'public/js/xen-quests.js',         array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-habits',         $url . 'public/js/xen-habits.js',         array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-shop',           $url . 'public/js/xen-shop.js',           array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-quest-hub',      $url . 'public/js/xen-quest-hub.js',      array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-profile-wallet', $url . 'public/js/xen-profile-wallet.js', array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-social',         $url . 'public/js/xen-social.js',         array( 'jquery', 'xen-main' ), $ver, true );

		// Localise AJAX data
		wp_localize_script( 'xen-main', 'xenData', array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'restUrl'        => esc_url_raw( rest_url( 'xen/v1' ) ),
			'nonce'          => wp_create_nonce( 'xen_nonce' ),
			'restNonce'      => wp_create_nonce( 'wp_rest' ),
			'userId'         => get_current_user_id(),
			'isLoggedIn'     => is_user_logged_in() ? 'yes' : 'no',
			'loginUrl'       => wp_login_url( get_permalink() ),
			'currencyName'   => Xen_Currency::name(),
			'currencySymbol' => Xen_Currency::symbol(),
			'whatsNewVersion' => XEN_LEVELUP_VERSION,
			'currentUser'    => is_user_logged_in() ? array(
				'id'     => get_current_user_id(),
				'name'   => wp_get_current_user()->display_name,
				'avatar' => get_avatar_url( get_current_user_id(), array( 'size' => 48 ) ),
			) : null,
			'i18n'           => array(
				'levelUp'         => esc_html__( 'LEVEL UP!', 'xen-levelup' ),
				'questComplete'   => esc_html__( 'Quest Complete!', 'xen-levelup' ),
				'achievementUnlocked' => esc_html__( 'Achievement Unlocked!', 'xen-levelup' ),
				'confirm'         => esc_html__( 'Are you sure?', 'xen-levelup' ),
				'processing'      => esc_html__( 'Processing…', 'xen-levelup' ),
				'error'           => esc_html__( 'Something went wrong. Please try again.', 'xen-levelup' ),
				'buy'             => esc_html__( 'Buy', 'xen-levelup' ),
				'equip'           => esc_html__( 'Equip', 'xen-levelup' ),
				'equipped'        => esc_html__( 'Equipped', 'xen-levelup' ),
				'loginToBuy'      => esc_html__( 'Login to Buy', 'xen-levelup' ),
				'shopEmpty'       => esc_html__( 'Shop is empty.', 'xen-levelup' ),
				'purchaseSuccess' => esc_html__( 'Item purchased!', 'xen-levelup' ),
				'equipSuccess'    => esc_html__( 'Item equipped!', 'xen-levelup' ),
				'unequipSuccess'  => esc_html__( 'Item unequipped.', 'xen-levelup' ),
				'prev'            => esc_html__( 'Prev', 'xen-levelup' ),
				'next'            => esc_html__( 'Next', 'xen-levelup' ),
			),
		) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'xen-levelup' ) ) {
			return;
		}

		$ver = XEN_LEVELUP_VERSION;
		$url = XEN_LEVELUP_PLUGIN_URL;

		wp_enqueue_style( 'xen-admin', $url . 'admin/css/admin.css', array(), $ver );
		wp_enqueue_script( 'xen-admin', $url . 'admin/js/admin.js', array( 'jquery', 'wp-util' ), $ver, true );

		wp_localize_script( 'xen-admin', 'xenAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'xen_admin_nonce' ),
		) );
	}

	/**
	 * Output inline JS configuration variables in <head>.
	 */
	public function output_inline_vars() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$profile = xen_levelup()->user->get_profile( get_current_user_id() );
		if ( ! $profile ) {
			return;
		}
		printf(
			'<script>window.xenUserLevel=%d;window.xenUserXP=%d;</script>' . "\n",
			(int) $profile->level,
			(int) $profile->experience
		);
	}

	/**
	 * Return a module instance by name.
	 *
	 * @param string $module Module property name.
	 * @return mixed|null
	 */
	public function get_module( $module ) {
		return isset( $this->$module ) ? $this->$module : null;
	}
}
