<?php if ( ! defined( 'ABSPATH' ) ) exit;
$period   = sanitize_key( $_GET['period'] ?? 'global' ); // phpcs:ignore
$rankings = xen_levelup()->rankings->get_leaderboard( $period );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">🏅 <?php esc_html_e( 'Rankings', 'xen-levelup' ); ?></h1>
	<form method="get" style="margin-bottom:1em;">
		<input type="hidden" name="page" value="xen-levelup-rankings">
		<select name="period">
			<option value="global"  <?php selected( $period, 'global' ); ?>><?php esc_html_e( 'All-time', 'xen-levelup' ); ?></option>
			<option value="weekly"  <?php selected( $period, 'weekly' ); ?>><?php esc_html_e( 'This Week', 'xen-levelup' ); ?></option>
			<option value="monthly" <?php selected( $period, 'monthly' ); ?>><?php esc_html_e( 'This Month', 'xen-levelup' ); ?></option>
		</select>
		<button class="button"><?php esc_html_e( 'View', 'xen-levelup' ); ?></button>
	</form>
	<table class="wp-list-table widefat striped xen-admin-table">
		<thead><tr>
			<th>#</th><th><?php esc_html_e( 'Hunter', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Level', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Rank', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Score (XP)', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></th>
		</tr></thead>
		<tbody>
		<?php if ( $rankings ) : foreach ( $rankings as $r ) : ?>
			<tr>
				<td><strong>#<?php echo esc_html( $r->rank_position ); ?></strong></td>
				<td><?php echo esc_html( $r->display_name ); ?></td>
				<td><?php echo esc_html( $r->level ); ?></td>
				<td><?php echo esc_html( $r->rank_title ); ?></td>
				<td><?php echo esc_html( number_format( $r->score ) ); ?></td>
				<td><?php echo esc_html( $r->quests_completed ); ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No ranking data yet.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
