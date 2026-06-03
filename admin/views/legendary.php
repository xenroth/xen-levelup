<?php
/**
 * Admin view: Legendary quests.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Manual trigger
if ( isset( $_POST['xen_trigger_legendary'] )
	&& check_admin_referer( 'xen_trigger_legendary' ) ) {
	xen_levelup()->legendary_quests->run_weekly_assignment();
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Legendary quest assignment triggered.', 'xen-levelup' ) . '</p></div>';
}

$active = xen_levelup()->legendary_quests->get_all();
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">⭐ <?php esc_html_e( 'Legendary Quests', 'xen-levelup' ); ?></h1>

	<form method="post" style="margin-bottom:1.5em;">
		<?php wp_nonce_field( 'xen_trigger_legendary' ); ?>
		<input type="hidden" name="xen_trigger_legendary" value="1">
		<button class="button button-primary"><?php esc_html_e( 'Manually Trigger Assignment', 'xen-levelup' ); ?></button>
		<span class="description">&nbsp;<?php esc_html_e( 'Selects up to 10 random hunters for a legendary quest.', 'xen-levelup' ); ?></span>
	</form>

	<table class="wp-list-table widefat striped xen-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Hunter', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Quest Title', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Status', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $active ) : foreach ( $active as $q ) :
			$user = get_userdata( $q->user_id );
		?>
			<tr>
				<td><?php echo esc_html( $user ? $user->display_name : '#' . $q->user_id ); ?></td>
				<td><?php echo esc_html( $q->title ); ?></td>
				<td><?php echo esc_html( $q->status ); ?></td>
				<td><?php echo esc_html( $q->expires_at ?? '—' ); ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="4"><?php esc_html_e( 'No active legendary quests.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
