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

		// Redirect new users to onboarding
		add_action( 'wp_login', array( $this->onboarding, 'maybe_redirect_to_onboarding' ), 10, 2 );

		// Award achievements after XP/quest/task events
		add_action( 'xen_xp_added',             array( $this->achievements, 'check_level_achievements' ), 10, 2 );
		add_action( 'xen_quest_completed',       array( $this->achievements, 'check_quest_achievements' ), 10, 2 );
		add_action( 'xen_task_completed',        array( $this->achievements, 'check_task_achievements' ), 10, 2 );
		add_action( 'xen_habit_logged',          array( $this->achievements, 'check_habit_achievements' ), 10, 2 );

		// Update rankings after profile change
		add_action( 'xen_xp_added',             array( $this->rankings, 'schedule_update' ), 20, 2 );
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
		wp_enqueue_script( 'xen-quests',     $url . 'public/js/xen-quests.js',     array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-habits',     $url . 'public/js/xen-habits.js',     array( 'jquery', 'xen-main' ), $ver, true );
		wp_enqueue_script( 'xen-shop',       $url . 'public/js/xen-shop.js',       array( 'jquery', 'xen-main' ), $ver, true );

		// Localise AJAX data
		wp_localize_script( 'xen-main', 'xenData', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'restUrl'  => esc_url_raw( rest_url( 'xen/v1' ) ),
			'nonce'    => wp_create_nonce( 'xen_nonce' ),
			'restNonce'=> wp_create_nonce( 'wp_rest' ),
			'userId'   => get_current_user_id(),
			'isLoggedIn' => is_user_logged_in() ? 'yes' : 'no',
			'i18n'     => array(
				'levelUp'        => esc_html__( 'LEVEL UP!', 'xen-levelup' ),
				'questComplete'  => esc_html__( 'Quest Complete!', 'xen-levelup' ),
				'achievementUnlocked' => esc_html__( 'Achievement Unlocked!', 'xen-levelup' ),
				'confirm'        => esc_html__( 'Are you sure?', 'xen-levelup' ),
				'processing'     => esc_html__( 'Processing…', 'xen-levelup' ),
				'error'          => esc_html__( 'Something went wrong. Please try again.', 'xen-levelup' ),
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
