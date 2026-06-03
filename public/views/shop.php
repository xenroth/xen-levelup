<?php
/**
 * Public view: Shop.
 * Loaded by [gamified_shop]
 *
 * Variables: $user_id, $items, $inventory, $atts, $type, $page, $per_page, $total, $pages
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
$currency_symbol = Xen_Currency::symbol();
?>
<div class="xen-wrap xen-shop-wrap xen-shop" id="xen-shop"
	data-type="<?php echo esc_attr( $type ?? 'all' ); ?>"
	data-page="<?php echo esc_attr( $page ?? 1 ); ?>"
	data-pages="<?php echo esc_attr( $pages ?? 1 ); ?>"
	data-per-page="<?php echo esc_attr( $per_page ?? 12 ); ?>"
	data-total="<?php echo esc_attr( $total ?? count( $items ) ); ?>">

	<div class="xen-shop-header">
		<h3 class="xen-section-title">🛒 <?php esc_html_e( 'Hunter Shop', 'xen-levelup' ); ?></h3>
		<?php if ( is_user_logged_in() ) : ?>
		<div class="xen-shop-balance">
			<?php echo esc_html( $currency_symbol ); ?>
			<strong id="xen-coin-balance"><?php echo esc_html( number_format( $balance ) ); ?></strong>
		</div>
		<?php endif; ?>
	</div>

	<!-- Type filter buttons -->
	<div class="xen-shop-filters">
		<button class="xen-filter-btn <?php echo ( ( $type ?? 'all' ) === 'all' ) ? 'xen-filter-active' : ''; ?>"
			data-type="all"><?php esc_html_e( 'All', 'xen-levelup' ); ?></button>
		<?php foreach ( Xen_Shop::ITEM_TYPES as $filter_type ) : ?>
		<button class="xen-filter-btn <?php echo ( ( $type ?? '' ) === $filter_type ) ? 'xen-filter-active' : ''; ?>"
			data-type="<?php echo esc_attr( $filter_type ); ?>">
			<?php echo esc_html( ucwords( str_replace( '_', ' ', $filter_type ) ) ); ?>
		</button>
		<?php endforeach; ?>
	</div>

	<!-- Items grid (replaced on AJAX filter/page change) -->
	<div class="xen-shop-grid" id="xen-shop-grid">
	<?php if ( $items ) : foreach ( $items as $item ) :
		$is_owned    = in_array( (int) $item->id, $owned_ids, true );
		$is_equipped = in_array( (int) $item->id, $equipped, true );
	?>
		<div class="xen-shop-item" data-item-type="<?php echo esc_attr( $item->item_type ); ?>"
			id="xen-item-<?php echo esc_attr( $item->id ); ?>">
			<div class="xen-item-icon">
				<?php if ( $item->image_url ) : ?>
					<img src="<?php echo esc_url( $item->image_url ); ?>"
						alt="<?php echo esc_attr( $item->title ); ?>" loading="lazy">
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
				<span class="xen-item-price"><?php echo esc_html( $currency_symbol ); ?> <?php echo esc_html( number_format( $item->price ) ); ?></span>
				<?php if ( ! is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
						class="xen-btn xen-btn-secondary"><?php esc_html_e( 'Login to Buy', 'xen-levelup' ); ?></a>
				<?php elseif ( $is_equipped ) : ?>
					<button class="xen-btn xen-btn-equipped xen-equip-item"
						data-item-id="<?php echo esc_attr( $item->id ); ?>"
						data-equip="0">
						<?php esc_html_e( '✓ Equipped', 'xen-levelup' ); ?>
					</button>
				<?php elseif ( $is_owned ) : ?>
					<button class="xen-btn xen-btn-owned xen-equip-item"
						data-item-id="<?php echo esc_attr( $item->id ); ?>"
						data-equip="1">
						<?php esc_html_e( 'Equip', 'xen-levelup' ); ?>
					</button>
				<?php else : ?>
					<button class="xen-btn xen-btn-buy xen-purchase-item"
						data-item-id="<?php echo esc_attr( $item->id ); ?>"
						data-price="<?php echo esc_attr( $item->price ); ?>">
						<?php esc_html_e( 'Buy', 'xen-levelup' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty"><?php esc_html_e( 'Shop is empty.', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div><!-- .xen-shop-grid -->

	<!-- Pagination (updated via AJAX) -->
	<div class="xen-shop-pagination" id="xen-shop-pagination"></div>

</div><!-- .xen-shop-wrap -->
