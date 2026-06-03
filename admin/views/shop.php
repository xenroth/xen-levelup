<?php
/**
 * Admin view: Shop Item Management.
 *
 * Handles list, add (inline form), and edit (full-form) modes.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Helper: render shared item form fields ───────────────────────────────────
function xen_render_shop_item_fields( $item = null ) {
	$item_types = Xen_Shop::ITEM_TYPES;
	$hints      = array(
		'frame'      => '{"css_class":"frame-gold"}',
		'border'     => '{"css_class":"border-silver"}',
		'name_color' => '{"color":"#ff0000"}',
		'title'      => '{"text":"Shadow Monarch"}',
		'theme'      => '{"css_class":"theme-dark"}',
		'badge'      => '{"css_class":"badge-veteran","text":"Veteran"}',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label><?php esc_html_e( 'Title', 'xen-levelup' ); ?> <span style="color:#f55">*</span></label></th>
			<td><input type="text" name="title" class="regular-text" required
				value="<?php echo $item ? esc_attr( $item->title ) : ''; ?>"></td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Item Type', 'xen-levelup' ); ?> <span style="color:#f55">*</span></label></th>
			<td>
				<select name="item_type" required>
					<?php foreach ( $item_types as $type ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>"
						<?php selected( $item ? $item->item_type : '', $type ); ?>>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Description', 'xen-levelup' ); ?></label></th>
			<td><textarea name="description" rows="3" class="large-text"><?php echo $item ? esc_textarea( $item->description ) : ''; ?></textarea></td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Price (Coins)', 'xen-levelup' ); ?></label></th>
			<td><input type="number" name="price" min="0" class="small-text"
				value="<?php echo $item ? esc_attr( $item->price ) : '0'; ?>"></td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Item Image', 'xen-levelup' ); ?></label></th>
			<td>
				<div class="xen-image-upload-wrap">
					<?php $img_url = $item ? esc_attr( $item->image_url ) : ''; ?>
					<div class="xen-image-preview" id="xen-shop-img-preview">
						<?php if ( $img_url ) : ?>
						<img src="<?php echo esc_url( $item->image_url ); ?>" style="max-width:100px;max-height:100px;border-radius:6px;display:block;margin-bottom:6px">
						<?php endif; ?>
					</div>
					<input type="url" name="image_url" id="xen-shop-img-url" class="regular-text"
						value="<?php echo $img_url; ?>"
						placeholder="<?php esc_attr_e( 'https://… or use Upload button', 'xen-levelup' ); ?>">
					<button type="button" class="button xen-media-upload-btn" data-target="xen-shop-img-url" data-preview="xen-shop-img-preview">
						📁 <?php esc_html_e( 'Upload / Select Image', 'xen-levelup' ); ?>
					</button>
				</div>
				<p class="description" style="margin-top:8px">
					<strong><?php esc_html_e( 'Recommended PNG sizes by item type:', 'xen-levelup' ); ?></strong><br>
					<span style="font-family:monospace">
					🖼️ <strong>Frame</strong> — 420 × 420 px (avatar frame overlay, transparent PNG)<br>
					🔲 <strong>Border</strong> — 420 × 420 px (profile border ring, transparent PNG)<br>
					🎨 <strong>Name Color</strong> — 100 × 32 px (colour swatch preview)<br>
					🏷️ <strong>Title</strong> — 200 × 48 px (title badge banner)<br>
					🎭 <strong>Theme</strong> — 320 × 200 px (theme thumbnail preview)<br>
					🏅 <strong>Badge</strong> — 80 × 80 px (badge icon, transparent PNG)<br>
					</span>
					<?php esc_html_e( 'All images should be PNG format with transparent background where applicable. Max recommended file size: 200 KB.', 'xen-levelup' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Item Data (JSON)', 'xen-levelup' ); ?></label></th>
			<td>
				<textarea name="item_data" rows="3" class="large-text" style="font-family:monospace"><?php echo ( $item && $item->item_data ) ? esc_textarea( $item->item_data ) : ''; ?></textarea>
				<p class="description">
					<?php esc_html_e( 'JSON data controlling the item\'s visual effect. Examples:', 'xen-levelup' ); ?><br>
					<?php foreach ( $hints as $t => $hint ) : ?>
					<code><?php echo esc_html( ucwords( str_replace( '_', ' ', $t ) ) ); ?>: <?php echo esc_html( $hint ); ?></code><br>
					<?php endforeach; ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Sort Order', 'xen-levelup' ); ?></label></th>
			<td><input type="number" name="sort_order" class="small-text"
				value="<?php echo $item ? esc_attr( $item->sort_order ) : '0'; ?>"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Options', 'xen-levelup' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="is_premium" value="1"
						<?php checked( $item ? (int) $item->is_premium : 0 ); ?>>
					<?php esc_html_e( 'Premium item', 'xen-levelup' ); ?>
				</label><br>
				<label>
					<input type="checkbox" name="is_active" value="1"
						<?php checked( $item ? (int) $item->is_active : 1 ); ?>>
					<?php esc_html_e( 'Active (visible in shop)', 'xen-levelup' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php
}

// ─── Page-mode setup ─────────────────────────────────────────────────────────
$action    = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore
$item_id   = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0; // phpcs:ignore
$edit_item = null;

if ( 'edit' === $action && $item_id ) {
	$edit_item = xen_levelup()->shop->get_item_any( $item_id );
	if ( ! $edit_item ) {
		$action = 'list';
	}
}

// ─── List-mode: filter / pagination vars ─────────────────────────────────────
$per_page      = 20;
$current_page  = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore
$filter_type   = isset( $_GET['filter_type'] ) ? sanitize_key( $_GET['filter_type'] ) : 'all'; // phpcs:ignore
$filter_status = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : 'all'; // phpcs:ignore
$search        = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore

if ( 'active' === $filter_status ) {
	$active_only_param = true;
} elseif ( 'inactive' === $filter_status ) {
	$active_only_param = false;
} else {
	$active_only_param = null;
}

$item_count  = xen_levelup()->shop->count_items( $filter_type, $active_only_param, $search );
$items       = xen_levelup()->shop->get_items_paged( $filter_type, $current_page, $per_page, $search, $active_only_param );
$total_pages = max( 1, (int) ceil( $item_count / $per_page ) );
$item_types  = Xen_Shop::ITEM_TYPES;

$base_url = admin_url( 'admin.php?page=xen-levelup-shop' );

// ─── Flash messages ──────────────────────────────────────────────────────────
foreach ( array(
	'xen_created' => __( 'Shop item created.', 'xen-levelup' ),
	'xen_updated' => __( 'Shop item updated.', 'xen-levelup' ),
	'xen_deleted' => __( 'Shop item deleted.', 'xen-levelup' ),
	'xen_toggled' => __( 'Item status toggled.', 'xen-levelup' ),
) as $k => $msg ) {
	if ( isset( $_GET[ $k ] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
}
settings_errors( 'xen_shop' );
?>

<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">🛒 <?php esc_html_e( 'Shop Items', 'xen-levelup' ); ?>
		<?php if ( 'edit' !== $action ) : ?>
		<button type="button" class="page-title-action" id="xen-toggle-add-form">
			<?php esc_html_e( '+ Add New Item', 'xen-levelup' ); ?>
		</button>
		<?php else : ?>
		<a href="<?php echo esc_url( $base_url ); ?>" class="page-title-action">
			← <?php esc_html_e( 'Back to List', 'xen-levelup' ); ?>
		</a>
		<?php endif; ?>
	</h1>

<?php if ( 'edit' === $action && $edit_item ) : ?>
	<!-- ── Edit Form ──────────────────────────────────────────────────── -->
	<div class="xen-admin-card xen-shop-form-card">
		<h2 style="margin-top:0"><?php esc_html_e( 'Edit Item', 'xen-levelup' ); ?>
			— <em><?php echo esc_html( $edit_item->title ); ?></em>
		</h2>
		<form method="post" action="<?php echo esc_url( $base_url ); ?>">
			<?php wp_nonce_field( 'xen_shop_update', 'xen_shop_nonce' ); ?>
			<input type="hidden" name="xen_shop_action" value="update">
			<input type="hidden" name="item_id" value="<?php echo esc_attr( $edit_item->id ); ?>">
			<?php xen_render_shop_item_fields( $edit_item ); ?>
			<p>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Update Item', 'xen-levelup' ); ?>
				</button>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'xen-levelup' ); ?>
				</a>
			</p>
		</form>
	</div>

<?php else : ?>
	<!-- ── Add New Item Form (collapsible) ────────────────────────────── -->
	<div class="xen-admin-card xen-shop-form-card" id="xen-add-item-form"
		style="display:none;margin-bottom:20px">
		<h2 style="margin-top:0"><?php esc_html_e( 'Add New Item', 'xen-levelup' ); ?></h2>
		<form method="post" action="<?php echo esc_url( $base_url ); ?>">
			<?php wp_nonce_field( 'xen_shop_create', 'xen_shop_nonce' ); ?>
			<input type="hidden" name="xen_shop_action" value="create">
			<?php xen_render_shop_item_fields(); ?>
			<p>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Create Item', 'xen-levelup' ); ?>
				</button>
				<button type="button" class="button" id="xen-cancel-add-form">
					<?php esc_html_e( 'Cancel', 'xen-levelup' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- ── Filter Bar ─────────────────────────────────────────────────── -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="xen-levelup-shop">
		<div class="xen-admin-filter-bar">
			<select name="filter_type">
				<option value="all" <?php selected( $filter_type, 'all' ); ?>><?php esc_html_e( 'All Types', 'xen-levelup' ); ?></option>
				<?php foreach ( $item_types as $t ) : ?>
				<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $filter_type, $t ); ?>>
					<?php echo esc_html( ucwords( str_replace( '_', ' ', $t ) ) ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<select name="filter_status">
				<option value="all"      <?php selected( $filter_status, 'all' ); ?>><?php esc_html_e( 'All Status', 'xen-levelup' ); ?></option>
				<option value="active"   <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'xen-levelup' ); ?></option>
				<option value="inactive" <?php selected( $filter_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'xen-levelup' ); ?></option>
			</select>

			<input type="search" name="search"
				placeholder="<?php esc_attr_e( 'Search by title…', 'xen-levelup' ); ?>"
				value="<?php echo esc_attr( $search ); ?>" class="regular-text">

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'xen-levelup' ); ?></button>

			<?php if ( 'all' !== $filter_type || 'all' !== $filter_status || $search ) : ?>
			<a href="<?php echo esc_url( $base_url ); ?>" class="button">
				<?php esc_html_e( 'Clear', 'xen-levelup' ); ?>
			</a>
			<?php endif; ?>

			<span class="xen-item-count" style="line-height:30px;margin-left:8px">
				<?php echo esc_html( sprintf( _n( '%d item', '%d items', $item_count, 'xen-levelup' ), $item_count ) ); ?>
			</span>
		</div>
	</form>

	<!-- ── Items Table ────────────────────────────────────────────────── -->
	<table class="wp-list-table widefat striped xen-admin-table xen-shop-table">
		<thead>
			<tr>
				<th style="width:40px">ID</th>
				<th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Type', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Price', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Premium', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Status', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Created', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $items ) : foreach ( $items as $item ) :
			$edit_link   = add_query_arg( array( 'action' => 'edit', 'item_id' => $item->id ), $base_url );
			$toggle_text = $item->is_active
				? __( 'Deactivate', 'xen-levelup' )
				: __( 'Activate', 'xen-levelup' );
		?>
			<tr class="<?php echo $item->is_active ? '' : 'xen-row-inactive'; ?>">
				<td><?php echo esc_html( $item->id ); ?></td>
				<td>
					<strong>
						<a href="<?php echo esc_url( $edit_link ); ?>">
							<?php echo esc_html( $item->title ); ?>
						</a>
					</strong>
					<?php if ( $item->description ) : ?>
					<p class="description" style="margin:2px 0 0">
						<?php echo esc_html( wp_trim_words( $item->description, 12 ) ); ?>
					</p>
					<?php endif; ?>
				</td>
				<td>
					<span class="xen-type-badge">
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $item->item_type ) ) ); ?>
					</span>
				</td>
				<td>🪙 <?php echo esc_html( number_format( (int) $item->price ) ); ?></td>
				<td><?php echo $item->is_premium ? '⭐' : '—'; ?></td>
				<td>
					<?php if ( $item->is_active ) : ?>
					<span class="xen-status-badge xen-status-active"><?php esc_html_e( 'Active', 'xen-levelup' ); ?></span>
					<?php else : ?>
					<span class="xen-status-badge xen-status-inactive"><?php esc_html_e( 'Inactive', 'xen-levelup' ); ?></span>
					<?php endif; ?>
				</td>
				<td style="white-space:nowrap">
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) ) ); ?>
				</td>
				<td class="xen-row-actions" style="white-space:nowrap">
					<a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small">
						✏️ <?php esc_html_e( 'Edit', 'xen-levelup' ); ?>
					</a>

					<form method="post" action="<?php echo esc_url( $base_url ); ?>"
						style="display:inline">
						<?php wp_nonce_field( 'xen_shop_toggle', 'xen_shop_nonce' ); ?>
						<input type="hidden" name="xen_shop_action" value="toggle">
						<input type="hidden" name="item_id" value="<?php echo esc_attr( $item->id ); ?>">
						<button type="submit" class="button button-small">
							<?php echo $item->is_active ? '⏸ ' : '▶ '; ?>
							<?php echo esc_html( $toggle_text ); ?>
						</button>
					</form>

					<form method="post" action="<?php echo esc_url( $base_url ); ?>"
						style="display:inline"
						onsubmit="return confirm('<?php echo esc_js( sprintf( __( 'Delete "%s"? This cannot be undone.', 'xen-levelup' ), $item->title ) ); ?>')">
						<?php wp_nonce_field( 'xen_shop_delete', 'xen_shop_nonce' ); ?>
						<input type="hidden" name="xen_shop_action" value="delete">
						<input type="hidden" name="item_id" value="<?php echo esc_attr( $item->id ); ?>">
						<button type="submit" class="button button-small xen-btn-danger">
							🗑 <?php esc_html_e( 'Delete', 'xen-levelup' ); ?>
						</button>
					</form>
				</td>
			</tr>
		<?php endforeach; else : ?>
			<tr>
				<td colspan="8" style="text-align:center;padding:20px">
					<?php esc_html_e( 'No items found.', 'xen-levelup' ); ?>
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<!-- ── Pagination ─────────────────────────────────────────────────── -->
	<?php if ( $total_pages > 1 ) :
		echo paginate_links( array( // phpcs:ignore
			'base'      => add_query_arg( 'paged', '%#%', $base_url ),
			'format'    => '',
			'current'   => $current_page,
			'total'     => $total_pages,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'type'      => 'plain',
			'add_args'  => array_filter( array(
				'filter_type'   => ( 'all' !== $filter_type ) ? $filter_type : false,
				'filter_status' => ( 'all' !== $filter_status ) ? $filter_status : false,
				'search'        => $search ?: false,
			) ),
		) );
	endif; ?>

<?php endif; ?>
</div><!-- .xen-admin-wrap -->

<script>
(function () {
	var btn    = document.getElementById('xen-toggle-add-form');
	var form   = document.getElementById('xen-add-item-form');
	var cancel = document.getElementById('xen-cancel-add-form');
	if ( btn && form ) {
		btn.addEventListener('click', function () {
			var hidden = form.style.display === 'none' || form.style.display === '';
			form.style.display = hidden ? 'block' : 'none';
			if ( hidden ) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
		});
	}
	if ( cancel && form ) {
		cancel.addEventListener('click', function () { form.style.display = 'none'; });
	}
}());
</script>
