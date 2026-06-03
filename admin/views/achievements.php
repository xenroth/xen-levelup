<?php if ( ! defined( 'ABSPATH' ) ) exit;
$achievements = xen_levelup()->achievements->get_all();
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">🏆 <?php esc_html_e( 'Achievements', 'xen-levelup' ); ?></h1>
	<table class="wp-list-table widefat striped xen-admin-table">
		<thead><tr>
			<th>ID</th><th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Type', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Req.', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Coins', 'xen-levelup' ); ?></th>
		</tr></thead>
		<tbody>
		<?php if ( $achievements ) : foreach ( $achievements as $a ) : ?>
			<tr>
				<td><?php echo esc_html( $a->id ); ?></td>
				<td><?php echo esc_html( $a->icon . ' ' . $a->title ); ?></td>
				<td><?php echo esc_html( $a->requirement_type ); ?></td>
				<td><?php echo esc_html( $a->requirement_value ); ?></td>
				<td><?php echo esc_html( $a->xp_reward ); ?></td>
				<td><?php echo esc_html( $a->coin_reward ); ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="6"><?php esc_html_e( 'None found.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
