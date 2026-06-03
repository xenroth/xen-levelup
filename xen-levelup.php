<?php
/**
 * Plugin Name: XEN LevelUp
 * Plugin URI:  https://github.com/xenroth/xen-levelup
 * Description: Solo-Leveling Inspired Personal Development & Gamification System. Level up your real life through quests, habits, and achievements.
 * Version:     1.2.1
 * Author:      Richard C. Cupal, LPT (Xenroth)
 * Author URI:  https://xenroth.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xen-levelup
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Plugin Constants ───────────────────────────────────────────────────────
define( 'XEN_LEVELUP_VERSION',    '1.2.1' );
define( 'XEN_LEVELUP_PLUGIN_FILE', __FILE__ );
define( 'XEN_LEVELUP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'XEN_LEVELUP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'XEN_LEVELUP_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'XEN_LEVELUP_DB_VERSION',  '1.1.0' );
define( 'XEN_MAX_LEVEL',           100 );
define( 'XEN_MAX_DAILY_TASKS',     10 );

// ─── Class Autoloader ────────────────────────────────────────────────────────
spl_autoload_register( function ( $class_name ) {
	if ( 0 !== strpos( $class_name, 'Xen_' ) ) {
		return;
	}

	// Convert Xen_Foo_Bar → class-xen-foo-bar.php
	$file = strtolower( str_replace( '_', '-', $class_name ) );
	$file = 'class-' . $file . '.php';

	$candidates = array(
		XEN_LEVELUP_PLUGIN_DIR . 'includes/' . $file,
		XEN_LEVELUP_PLUGIN_DIR . 'admin/'    . $file,
	);

	foreach ( $candidates as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

// ─── Activation / Deactivation / Uninstall ───────────────────────────────────
register_activation_hook( __FILE__, array( 'Xen_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Xen_Installer', 'deactivate' ) );

// ─── GitHub Auto-Updater ─────────────────────────────────────────────────────
// Instantiated immediately (not on plugins_loaded) so the
// `pre_set_site_transient_update_plugins` filter is registered early enough.
if ( is_admin() ) {
	require_once XEN_LEVELUP_PLUGIN_DIR . 'includes/class-xen-github-updater.php';
	new Xen_GitHub_Updater( __FILE__ );
}

// ─── Bootstrap ───────────────────────────────────────────────────────────────
/**
 * Returns the main plugin instance (singleton).
 *
 * @return Xen_LevelUp
 */
function xen_levelup() {
	return Xen_LevelUp::get_instance();
}
add_action( 'plugins_loaded', 'xen_levelup' );
