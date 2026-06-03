<?php
/**
 * Public view: Shop.
 * Loaded by [gamified_shop]
 *
 * Variables: $user_id, $items, $inventory, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$balance    = is_user_logged_in() ? xen_levelup()->currency->get_balance( $user_id ) : 0;
$owned_ids  = array_column( $inventory, 'id' );
$equipped   = array();
foreach ( $inventory as $inv ) {
	if ( $inv->is_equipped ) {
		$equipped[] = (int) $inv->id;
	}
}
?>
<div class="xen-wrap xen-shop-wrap" id="xen-shop">

	<div class="xen-shop-header">
		<h3 class="xen-section-title">🛒 <?php esc_html_e( 'Hunter Shop', 'xen-levelup' ); ?></h3>
		<?php if ( is_user_logged_in() ) : ?>
		<div class="xen-shop-balance">🪙 <strong id="xen-coin-balance"><?php echo esc_html( number_format( $balance ) ); ?></strong></div>
		<?php endif; ?>
	</div>

	<!-- Type filters -->
	<div class="xen-shop-filters">
		<button class="xen-filter-btn xen-filter-active" data-type="all"><?php esc_html_e( 'All', 'xen-levelup' ); ?></button>
		<?php foreach ( Xen_Shop::ITEM_TYPES as $type ) : ?>
		<button class="xen-filter-btn" data-type="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?></button>
		<?php endforeach; ?>
	</div>

	<div class="xen-shop-grid" id="xen-shop-grid">
	<?php if ( $items ) : foreach ( $items as $item ) :
		$is_owned    = in_array( (int) $item->id, $owned_ids, true );
		$is_equipped = in_array( (int) $item->id, $equipped, true );
	?>
		<div class="xen-shop-item" data-type="<?php echo esc_attr( $item->item_type ); ?>" id="xen-item-<?php echo esc_attr( $item->id ); ?>">
			<div class="xen-item-icon">
				<?php if ( $item->image_url ) : ?>
					<img src="<?php echo esc_url( $item->image_url ); ?>" alt="<?php echo esc_attr( $item->title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="xen-item-placeholder">🎁</span>
				<?php endif; ?>
			</div>
			<div class="xen-item-info">
				<div class="xen-item-title"><?php echo esc_html( $item->title ); ?></div>
				<div class="xen-item-type"><?php echo esc_html( ucwords( str_replace( '_', ' ', $item->item_type ) ) ); ?></div>
				<?php if ( $item->description ) : ?>
				<div class="xen-item-desc"><?php echo esc_html( $item->description ); ?></div>
				<?php endif; ?>
			</div>
			<div class="xen-item-footer">
				<span class="xen-item-price">🪙 <?php echo esc_html( number_format( $item->price ) ); ?></span>
				<?php if ( ! is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="xen-btn xen-btn-secondary"><?php esc_html_e( 'Login to Buy', 'xen-levelup' ); ?></a>
				<?php elseif ( $is_equipped ) : ?>
					<button class="xen-btn xen-btn-equipped xen-equip-item" data-id="<?php echo esc_attr( $item->id ); ?>" data-equip="0" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>"><?php esc_html_e( '✓ Equipped', 'xen-levelup' ); ?></button>
				<?php elseif ( $is_owned ) : ?>
					<button class="xen-btn xen-btn-owned xen-equip-item" data-id="<?php echo esc_attr( $item->id ); ?>" data-equip="1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>"><?php esc_html_e( 'Equip', 'xen-levelup' ); ?></button>
				<?php else : ?>
					<button class="xen-btn xen-btn-buy xen-purchase-item" data-id="<?php echo esc_attr( $item->id ); ?>" data-price="<?php echo esc_attr( $item->price ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>"><?php esc_html_e( 'Buy', 'xen-levelup' ); ?></button>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty"><?php esc_html_e( 'Shop is empty.', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>

</div><!-- .xen-shop-wrap -->
