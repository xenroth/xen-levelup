<?php
/**
 * Admin view: Analytics.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix = $wpdb->prefix;

// XP gained last 7 days
$xp_trend = $wpdb->get_results( // phpcs:ignore
	"SELECT DATE(created_at) AS day, SUM(xp_amount) AS total
	 FROM {$prefix}xen_xp_log
	 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
	 GROUP BY day ORDER BY day ASC"
);

// Quests completed last 7 days
$quest_trend = $wpdb->get_results( // phpcs:ignore
	"SELECT DATE(completed_at) AS day, COUNT(*) AS total
	 FROM {$prefix}xen_user_quests
	 WHERE status='completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
	 GROUP BY day ORDER BY day ASC"
);
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">📊 <?php esc_html_e( 'Analytics (Last 7 Days)', 'xen-levelup' ); ?></h1>

	<div style="display:grid;grid-template-columns:1fr 1fr;gap:2em;">
		<div>
			<h2><?php esc_html_e( 'XP Gained per Day', 'xen-levelup' ); ?></h2>
			<table class="wp-list-table widefat striped">
				<thead><tr><th><?php esc_html_e( 'Date', 'xen-levelup' ); ?></th><th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $xp_trend as $row ) : ?>
					<tr><td><?php echo esc_html( $row->day ); ?></td><td><?php echo esc_html( number_format( (int) $row->total ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div>
			<h2><?php esc_html_e( 'Quests Completed per Day', 'xen-levelup' ); ?></h2>
			<table class="wp-list-table widefat striped">
				<thead><tr><th><?php esc_html_e( 'Date', 'xen-levelup' ); ?></th><th><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $quest_trend as $row ) : ?>
					<tr><td><?php echo esc_html( $row->day ); ?></td><td><?php echo esc_html( (int) $row->total ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
