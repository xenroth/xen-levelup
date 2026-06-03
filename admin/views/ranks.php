<?php
/**
 * Admin View: Rank Definitions
 *
 * @package XEN_LevelUp
 * @since   1.5.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap xen-admin-wrap">
	<h1 class="wp-heading-inline">⚔️ <?php esc_html_e( 'Rank Definitions', 'xen-levelup' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $saved )   : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rank saved successfully.', 'xen-levelup' ); ?></p></div><?php endif; ?>
	<?php if ( $deleted ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Rank deleted.', 'xen-levelup' ); ?></p></div><?php endif; ?>
	<?php if ( $toggled ) : ?><div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Rank status toggled.', 'xen-levelup' ); ?></p></div><?php endif; ?>
	<?php if ( $error )   : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>

	<?php
	// ── Edit Form ────────────────────────────────────────────────────────
	if ( $edit_rank ) :
	?>
	<div class="xen-card xen-admin-card" style="max-width:640px;margin-bottom:24px">
		<h2><?php esc_html_e( 'Edit Rank', 'xen-levelup' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'xen_rank_action', 'xen_rank_nonce' ); ?>
			<input type="hidden" name="action"      value="xen_admin_rank_action">
			<input type="hidden" name="rank_action" value="update">
			<input type="hidden" name="rank_id"     value="<?php echo esc_attr( $edit_rank->id ); ?>">
			<?php xen_admin_rank_fields( $edit_rank ); ?>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Update Rank', 'xen-levelup' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=xen-levelup-ranks' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'xen-levelup' ); ?></a>
			</p>
		</form>
	</div>
	<?php endif; ?>

	<?php
	// ── Add New Form ─────────────────────────────────────────────────────
	if ( ! $edit_rank ) :
	?>
	<details class="xen-card xen-admin-card" style="max-width:640px;margin-bottom:24px">
		<summary style="cursor:pointer;font-weight:600;padding:12px 0">➕ <?php esc_html_e( 'Add New Rank', 'xen-levelup' ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
			<?php wp_nonce_field( 'xen_rank_action', 'xen_rank_nonce' ); ?>
			<input type="hidden" name="action"      value="xen_admin_rank_action">
			<input type="hidden" name="rank_action" value="create">
			<?php xen_admin_rank_fields(); ?>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Create Rank', 'xen-levelup' ); ?></button></p>
		</form>
	</details>
	<?php endif; ?>

	<?php
	// ── Ranks Table ──────────────────────────────────────────────────────
	if ( empty( $ranks ) ) :
	?>
	<p><?php esc_html_e( 'No ranks defined yet. Add one above.', 'xen-levelup' ); ?></p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:40px"><?php esc_html_e( 'Icon', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
				<th style="width:80px"><?php esc_html_e( 'Color', 'xen-levelup' ); ?></th>
				<th style="width:90px"><?php esc_html_e( 'Rebirths', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Description', 'xen-levelup' ); ?></th>
				<th style="width:70px"><?php esc_html_e( 'Order', 'xen-levelup' ); ?></th>
				<th style="width:70px"><?php esc_html_e( 'Status', 'xen-levelup' ); ?></th>
				<th style="width:160px"><?php esc_html_e( 'Actions', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $ranks as $rank ) :
			$edit_url   = add_query_arg( array( 'page' => 'xen-levelup-ranks', 'action' => 'edit', 'rank_id' => $rank->id ), admin_url( 'admin.php' ) );
			$toggle_url = wp_nonce_url( add_query_arg( array( 'page' => 'xen-levelup-ranks', 'rank_action' => 'toggle', 'rank_id' => $rank->id ), admin_url( 'admin-post.php?action=xen_admin_rank_action' ) ), 'xen_rank_action', 'xen_rank_nonce' );
			$delete_url = wp_nonce_url( add_query_arg( array( 'page' => 'xen-levelup-ranks', 'rank_action' => 'delete', 'rank_id' => $rank->id ), admin_url( 'admin-post.php?action=xen_admin_rank_action' ) ), 'xen_rank_action', 'xen_rank_nonce' );
		?>
			<tr>
				<td style="font-size:1.4em;text-align:center"><?php echo esc_html( $rank->icon ); ?></td>
				<td><strong><?php echo esc_html( $rank->title ); ?></strong></td>
				<td>
					<span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:<?php echo esc_attr( $rank->color ); ?>;border:1px solid #ccc;vertical-align:middle"></span>
					<small><?php echo esc_html( $rank->color ); ?></small>
				</td>
				<td style="text-align:center"><?php echo esc_html( $rank->rebirth_required ); ?></td>
				<td><?php echo esc_html( $rank->description ); ?></td>
				<td style="text-align:center"><?php echo esc_html( $rank->sort_order ); ?></td>
				<td>
					<?php if ( $rank->is_active ) : ?>
						<span class="xen-badge xen-badge-success"><?php esc_html_e( 'Active', 'xen-levelup' ); ?></span>
					<?php else : ?>
						<span class="xen-badge xen-badge-muted"><?php esc_html_e( 'Inactive', 'xen-levelup' ); ?></span>
					<?php endif; ?>
				</td>
				<td>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'xen-levelup' ); ?></a>

					<!-- Toggle Active — POST form -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'xen_rank_action', 'xen_rank_nonce' ); ?>
						<input type="hidden" name="action"      value="xen_admin_rank_action">
						<input type="hidden" name="rank_action" value="toggle">
						<input type="hidden" name="rank_id"     value="<?php echo esc_attr( $rank->id ); ?>">
						<button type="submit" class="button button-small"><?php echo $rank->is_active ? esc_html__( 'Disable', 'xen-levelup' ) : esc_html__( 'Enable', 'xen-levelup' ); ?></button>
					</form>

					<!-- Delete — POST form with confirm -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php esc_attr_e( 'Delete this rank? This cannot be undone.', 'xen-levelup' ); ?>')">
						<?php wp_nonce_field( 'xen_rank_action', 'xen_rank_nonce' ); ?>
						<input type="hidden" name="action"      value="xen_admin_rank_action">
						<input type="hidden" name="rank_action" value="delete">
						<input type="hidden" name="rank_id"     value="<?php echo esc_attr( $rank->id ); ?>">
						<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'xen-levelup' ); ?></button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>

<?php
/**
 * Render the shared rank form fields (used for both Create and Edit forms).
 *
 * @param stdClass|null $rank Existing rank object, or null for blank form.
 */
function xen_admin_rank_fields( $rank = null ) {
	$v = function( $field, $default = '' ) use ( $rank ) {
		return $rank ? esc_attr( $rank->$field ) : esc_attr( $default );
	};
	?>
	<table class="form-table">
		<tr>
			<th><label for="rank_title"><?php esc_html_e( 'Title', 'xen-levelup' ); ?> <span style="color:red">*</span></label></th>
			<td><input type="text" id="rank_title" name="title" value="<?php echo $v( 'title' ); ?>" class="regular-text" required></td>
		</tr>
		<tr>
			<th><label for="rank_icon"><?php esc_html_e( 'Icon (emoji)', 'xen-levelup' ); ?></label></th>
			<td>
				<input type="text" id="rank_icon" name="icon" value="<?php echo $v( 'icon' ); ?>" class="small-text" maxlength="10" placeholder="🔴">
				<p class="description"><?php esc_html_e( 'Single emoji character displayed beside the rank.', 'xen-levelup' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="rank_color"><?php esc_html_e( 'Color', 'xen-levelup' ); ?></label></th>
			<td>
				<input type="color" id="rank_color" name="color" value="<?php echo $v( 'color', '#6b7280' ); ?>">
				<p class="description"><?php esc_html_e( 'Badge color used in the UI.', 'xen-levelup' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="rank_rebirth"><?php esc_html_e( 'Rebirths Required', 'xen-levelup' ); ?> <span style="color:red">*</span></label></th>
			<td>
				<input type="number" id="rank_rebirth" name="rebirth_required" value="<?php echo $v( 'rebirth_required', 0 ); ?>" min="0" class="small-text" required>
				<p class="description"><?php esc_html_e( 'Minimum number of rebirths a player needs to earn this rank.', 'xen-levelup' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="rank_description"><?php esc_html_e( 'Description', 'xen-levelup' ); ?></label></th>
			<td><textarea id="rank_description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $rank ? $rank->description : '' ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="rank_sort_order"><?php esc_html_e( 'Sort Order', 'xen-levelup' ); ?></label></th>
			<td><input type="number" id="rank_sort_order" name="sort_order" value="<?php echo $v( 'sort_order', 0 ); ?>" min="0" class="small-text"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Active', 'xen-levelup' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="is_active" value="1" <?php checked( $rank ? $rank->is_active : 1, 1 ); ?>>
					<?php esc_html_e( 'Enable this rank', 'xen-levelup' ); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php
}
