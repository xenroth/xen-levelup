<?php if ( ! defined( 'ABSPATH' ) ) exit;
$items = xen_levelup()->shop->get_items();
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">🛒 <?php esc_html_e( 'Shop Items', 'xen-levelup' ); ?></h1>
	<table class="wp-list-table widefat striped xen-admin-table">
		<thead><tr>
			<th>ID</th><th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Type', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Price', 'xen-levelup' ); ?></th>
			<th><?php esc_html_e( 'Active', 'xen-levelup' ); ?></th>
		</tr></thead>
		<tbody>
		<?php if ( $items ) : foreach ( $items as $i ) : ?>
			<tr>
				<td><?php echo esc_html( $i->id ); ?></td>
				<td><?php echo esc_html( $i->title ); ?></td>
				<td><?php echo esc_html( $i->item_type ); ?></td>
				<td><?php echo esc_html( number_format( $i->price ) ); ?> 🪙</td>
				<td><?php echo $i->is_active ? '✅' : '❌'; ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="5"><?php esc_html_e( 'No items found.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
