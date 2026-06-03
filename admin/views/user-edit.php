<?php
/**
 * Admin view: Edit a single user's XEN stats.
 *
 * Expects $edit_user_id (int) to be set by the caller.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$uid       = absint( $edit_user_id );
$wp_user   = get_userdata( $uid );

if ( ! $wp_user ) {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'User not found.', 'xen-levelup' ) . '</p></div>';
	return;
}

$profile   = xen_levelup()->user->get_profile( $uid );
$level     = $profile ? (int) $profile->level      : 1;
$xp        = $profile ? (int) $profile->experience : 0;
$coins     = $profile ? (int) $profile->coins       : 0;
$rank      = $profile ? $profile->rank_title        : Xen_User::rank_title_for_level( $level );

// Saved notice
$saved  = isset( $_GET['xen_user_saved'] );   // phpcs:ignore
$error  = isset( $_GET['xen_user_error'] ) ? sanitize_text_field( wp_unslash( $_GET['xen_user_error'] ) ) : ''; // phpcs:ignore

$back_url = add_query_arg( 'page', 'xen-levelup-users', admin_url( 'admin.php' ) );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">
		✏️ <?php esc_html_e( 'Edit Hunter Stats', 'xen-levelup' ); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
			← <?php esc_html_e( 'Back to Users', 'xen-levelup' ); ?>
		</a>
	</h1>

	<?php if ( $saved ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Stats updated successfully.', 'xen-levelup' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $error ) : ?>
	<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<!-- User card header -->
	<div class="xen-user-edit-header">
		<?php echo get_avatar( $uid, 64, '', '', array( 'class' => 'xen-user-edit-avatar' ) ); ?>
		<div class="xen-user-edit-info">
			<strong class="xen-user-edit-name"><?php echo esc_html( $wp_user->display_name ); ?></strong>
			<span class="xen-user-edit-email"><?php echo esc_html( $wp_user->user_email ); ?></span>
			<span class="xen-user-edit-rank"><?php echo esc_html( $rank ); ?></span>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="xen-user-edit-form">
		<input type="hidden" name="action"         value="xen_admin_save_user_stats">
		<input type="hidden" name="uid"            value="<?php echo esc_attr( $uid ); ?>">
		<input type="hidden" name="xen_edit_nonce" value="<?php echo esc_attr( wp_create_nonce( 'xen_edit_user_' . $uid ) ); ?>">
		<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore ?>">

		<table class="form-table xen-edit-table">
			<tbody>

				<tr>
					<th scope="row">
						<label for="xen_edit_level"><?php esc_html_e( 'Level', 'xen-levelup' ); ?></label>
					</th>
					<td>
						<input type="number" id="xen_edit_level" name="xen_level"
							   min="1" max="100" step="1"
							   value="<?php echo esc_attr( $level ); ?>"
							   class="small-text">
						<p class="description"><?php esc_html_e( 'Range 1–100. Rank title updates automatically.', 'xen-levelup' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="xen_edit_xp"><?php esc_html_e( 'Total XP (Experience)', 'xen-levelup' ); ?></label>
					</th>
					<td>
						<input type="number" id="xen_edit_xp" name="xen_xp"
							   min="0" step="1"
							   value="<?php echo esc_attr( $xp ); ?>"
							   class="regular-text">
						<p class="description"><?php esc_html_e( 'Cumulative XP earned by this hunter.', 'xen-levelup' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="xen_edit_coins"><?php esc_html_e( 'Coins (Currency Balance)', 'xen-levelup' ); ?></label>
					</th>
					<td>
						<input type="number" id="xen_edit_coins" name="xen_coins"
							   min="0" step="1"
							   value="<?php echo esc_attr( $coins ); ?>"
							   class="regular-text">
						<p class="description"><?php
							/* translators: %s = currency name */
							printf( esc_html__( 'Current %s balance. Setting this directly overwrites the balance.', 'xen-levelup' ), esc_html( Xen_Currency::name() ) );
						?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Award Bonus XP', 'xen-levelup' ); ?></th>
					<td>
						<input type="number" id="xen_edit_bonus_xp" name="xen_bonus_xp"
							   min="0" step="1" value="0"
							   class="regular-text">
						<p class="description"><?php esc_html_e( 'Enter a positive number to add XP on top of the Total XP above. Leave 0 to ignore.', 'xen-levelup' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Award Bonus Coins', 'xen-levelup' ); ?></th>
					<td>
						<input type="number" id="xen_edit_bonus_coins" name="xen_bonus_coins"
							   min="0" step="1" value="0"
							   class="regular-text">
						<p class="description"><?php esc_html_e( 'Enter a positive number to add coins on top of the balance above. Leave 0 to ignore.', 'xen-levelup' ); ?></p>
					</td>
				</tr>

			</tbody>
		</table>

		<?php submit_button( __( '💾 Save Stats', 'xen-levelup' ), 'primary', 'submit', true ); ?>
	</form>
</div><!-- .xen-admin-wrap -->
