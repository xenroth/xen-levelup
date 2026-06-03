<?php
/**
 * XEN LevelUp — Uninstall Script
 *
 * Runs only when the plugin is deleted through the WordPress admin.
 * Drops all custom tables, removes all options, and removes user meta.
 *
 * @package XEN_LevelUp
 */

// Safety check — must be triggered by WP uninstall mechanism.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// -----------------------------------------------------------------------
// 1. Drop all custom tables (in dependency order — children first).
// -----------------------------------------------------------------------
$tables = array(
	'xen_checkins',
	'xen_xp_log',
	'xen_notifications',
	'xen_user_inventory',
	'xen_shop_items',
	'xen_currency_transactions',
	'xen_user_achievements',
	'xen_achievements',
	'xen_habit_logs',
	'xen_habits',
	'xen_user_tasks',
	'xen_user_quests',
	'xen_quest_templates',
	'xen_rankings',
	'xen_onboarding',
	'xen_user_life_trees',
	'xen_user_stats',
	'xen_user_profiles',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . $table . '`' );
}

// -----------------------------------------------------------------------
// 2. Delete all plugin options.
// -----------------------------------------------------------------------
$options = array(
	'xen_levelup_db_version',
	'xen_levelup_version',
	'xen_levelup_dashboard_page',
	'xen_levelup_profile_page',
	'xen_levelup_quests_page',
	'xen_levelup_shop_page',
	'xen_levelup_rankings_page',
	'xen_levelup_achievements_page',
	'xen_levelup_onboarding_page',
	'xen_levelup_tasks_page',
	'xen_levelup_habits_page',
	'xen_levelup_enable_notifications',
	'xen_levelup_enable_legendary',
	'xen_levelup_enable_random_quests',
	'xen_levelup_max_daily_tasks',
	'xen_levelup_max_level',
	'xen_levelup_xp_multiplier',
	'xen_levelup_coins_multiplier',
	// Seeded flag
	'xen_levelup_seeded',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// -----------------------------------------------------------------------
// 3. Remove all user-level meta added by the plugin.
// -----------------------------------------------------------------------
$wpdb->query(
	"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'xen_levelup_%'"
);

// -----------------------------------------------------------------------
// 4. Clear scheduled cron events.
// -----------------------------------------------------------------------
$cron_hooks = array(
	'xen_daily_quest_generation',
	'xen_random_quest_generation',
	'xen_weekly_tasks',
	'xen_rankings_update',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
}
