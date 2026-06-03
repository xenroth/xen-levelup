<?php
/**
 * Admin view: Dashboard overview.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$total_users    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_profiles" );
$active_quests  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_quests WHERE status='active'" );
$quests_done    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_quests WHERE status='completed'" );
$tasks_done     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_tasks WHERE status='completed'" );
$habits_logged  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_habit_logs" );
$total_coins    = (int) $wpdb->get_var( "SELECT SUM(coins) FROM {$wpdb->prefix}xen_user_profiles" );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">⚔️ <?php esc_html_e( 'XEN LevelUp — Dashboard', 'xen-levelup' ); ?></h1>

	<div class="xen-admin-cards">
		<div class="xen-admin-card">
			<span class="xen-card-icon">👤</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $total_users ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Total Hunters', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">📋</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $active_quests ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Active Quests', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">✅</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $quests_done ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Quests Completed', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">⚡</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $tasks_done ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Tasks Done', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">🔥</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $habits_logged ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Habit Entries', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">🪙</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $total_coins ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Total Coins in Circulation', 'xen-levelup' ); ?></div>
		</div>
	</div>

	<h2><?php esc_html_e( 'Top 10 Hunters', 'xen-levelup' ); ?></h2>
	<?php
	$top = xen_levelup()->rankings->get_leaderboard( 'global', 'all', 10 );
	if ( $top ) :
	?>
	<table class="wp-list-table widefat striped xen-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Rank', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Hunter', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Level', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $top as $row ) : ?>
			<tr>
				<td><strong>#<?php echo esc_html( $row->rank_position ); ?></strong></td>
				<td><?php echo esc_html( $row->display_name ); ?></td>
				<td><?php echo esc_html( $row->level ); ?></td>
				<td><?php echo esc_html( $row->rank_title ); ?></td>
				<td><?php echo esc_html( number_format( $row->score ) ); ?></td>
				<td><?php echo esc_html( $row->quests_completed ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
	<p><?php esc_html_e( 'No rankings data yet. Rankings update twice daily.', 'xen-levelup' ); ?></p>
	<?php endif; ?>
</div>
