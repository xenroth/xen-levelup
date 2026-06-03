<?php
/**
 * Admin view: Settings.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

settings_errors( 'xen_settings' );

// Helper to get page options list
$pages = get_pages();
$page_options = array( '' => __( '— Select page —', 'xen-levelup' ) );
foreach ( $pages as $p ) {
	$page_options[ $p->ID ] = $p->post_title;
}

$fields = array(
	'pages' => array(
		'xen_levelup_dashboard_page'  => __( 'Dashboard Page', 'xen-levelup' ),
		'xen_levelup_profile_page'    => __( 'Profile Page', 'xen-levelup' ),
		'xen_levelup_onboarding_page' => __( 'Onboarding Page', 'xen-levelup' ),
		'xen_levelup_shop_page'       => __( 'Shop Page', 'xen-levelup' ),
		'xen_levelup_rankings_page'   => __( 'Rankings Page', 'xen-levelup' ),
	),
);
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">⚙️ <?php esc_html_e( 'Settings', 'xen-levelup' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'xen_save_settings', 'xen_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Page Assignments', 'xen-levelup' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select the page that contains each shortcode.', 'xen-levelup' ); ?></p>
		<table class="form-table">
		<?php foreach ( $fields['pages'] as $opt => $label ) : ?>
			<tr>
				<th><label for="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td>
					<select name="<?php echo esc_attr( $opt ); ?>" id="<?php echo esc_attr( $opt ); ?>">
					<?php foreach ( $page_options as $id => $title ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( get_option( $opt ), $id ); ?>>
							<?php echo esc_html( $title ); ?>
						</option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Features', 'xen-levelup' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Notifications', 'xen-levelup' ); ?></th>
				<td><label><input type="checkbox" name="xen_levelup_enable_notifications" value="1" <?php checked( get_option( 'xen_levelup_enable_notifications', 1 ) ); ?>> <?php esc_html_e( 'Send in-plugin notifications', 'xen-levelup' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Enable Random Quests', 'xen-levelup' ); ?></th>
				<td><label><input type="checkbox" name="xen_levelup_enable_random_quests" value="1" <?php checked( get_option( 'xen_levelup_enable_random_quests', 1 ) ); ?>> <?php esc_html_e( 'Hourly random quest assignments', 'xen-levelup' ); ?></label></td>
			</tr>
			<tr>
				<th><label for="xen_levelup_legendary_count"><?php esc_html_e( 'Legendary Quest Recipients / Week', 'xen-levelup' ); ?></label></th>
				<td><input type="number" id="xen_levelup_legendary_count" name="xen_levelup_legendary_count" min="1" max="100" value="<?php echo esc_attr( get_option( 'xen_levelup_legendary_count', 10 ) ); ?>"></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Disable WP Dashboard for Non-Admins', 'xen-levelup' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="xen_disable_wp_dashboard" value="1" <?php checked( get_option( 'xen_disable_wp_dashboard', 0 ) ); ?>>
						<?php esc_html_e( 'Redirect non-administrator users away from the WP Admin dashboard to the front-end.', 'xen-levelup' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Pages (Social)', 'xen-levelup' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select the page that contains the activity feed shortcode.', 'xen-levelup' ); ?></p>
		<table class="form-table">
			<tr>
				<th><label for="xen_levelup_feed_page"><?php esc_html_e( 'Activity Feed Page', 'xen-levelup' ); ?></label></th>
				<td>
					<select name="xen_levelup_feed_page" id="xen_levelup_feed_page">
					<?php foreach ( $page_options as $id => $title ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( get_option( 'xen_levelup_feed_page' ), $id ); ?>>
							<?php echo esc_html( $title ); ?>
						</option>
					<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Place [gamified_feed] on this page.', 'xen-levelup' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Currency', 'xen-levelup' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Customise the name and symbol used for the in-game currency.', 'xen-levelup' ); ?></p>
		<table class="form-table">
			<tr>
				<th><label for="xen_levelup_currency_name"><?php esc_html_e( 'Currency Name', 'xen-levelup' ); ?></label></th>
				<td>
					<input type="text" id="xen_levelup_currency_name" name="xen_levelup_currency_name"
						value="<?php echo esc_attr( get_option( 'xen_levelup_currency_name', 'System Coins' ) ); ?>"
						class="regular-text">
					<p class="description"><?php esc_html_e( 'E.g. System Coins, Gold, Credits', 'xen-levelup' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="xen_levelup_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'xen-levelup' ); ?></label></th>
				<td>
					<input type="text" id="xen_levelup_currency_symbol" name="xen_levelup_currency_symbol"
						value="<?php echo esc_attr( get_option( 'xen_levelup_currency_symbol', '💎' ) ); ?>"
						class="small-text">
					<p class="description"><?php esc_html_e( 'An emoji or short text, e.g. 💎 🪙 G $', 'xen-levelup' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'xen-levelup' ) ); ?>
	</form>
</div>
