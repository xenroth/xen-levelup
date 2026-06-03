<?php
/**
 * Admin controller – menu, page routing, user list columns.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Admin
 */
class Xen_Admin {

	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'register_menu'         ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets'        ) );
		add_filter( 'manage_users_columns',           array( $this, 'add_user_columns'      ) );
		add_filter( 'manage_users_custom_column',     array( $this, 'user_column_content'   ), 10, 3 );
		add_action( 'wp_ajax_xen_admin_dismiss_whats_new', array( $this, 'ajax_dismiss_whats_new' ) );
		add_action( 'admin_post_xen_admin_save_user_stats', array( $this, 'handle_save_user_stats' ) );
	}

	// ─── Menu ─────────────────────────────────────────────────────────────

	public function register_menu() {
		add_menu_page(
			__( 'XEN LevelUp', 'xen-levelup' ),
			__( 'XEN LevelUp', 'xen-levelup' ),
			'manage_options',
			'xen-levelup',
			array( $this, 'page_dashboard' ),
			'dashicons-superhero',
			30
		);

		$sub = array(
			array( 'xen-levelup',          __( 'Dashboard', 'xen-levelup' ),       'page_dashboard'       ),
			array( 'xen-levelup-users',    __( 'Users',     'xen-levelup' ),       'page_users'           ),
			array( 'xen-levelup-quests',   __( 'Quests',    'xen-levelup' ),       'page_quests'          ),
			array( 'xen-levelup-legendary',__( 'Legendary', 'xen-levelup' ),       'page_legendary'       ),
			array( 'xen-levelup-achievements', __( 'Achievements', 'xen-levelup' ),'page_achievements'    ),
			array( 'xen-levelup-shop',     __( 'Shop',      'xen-levelup' ),       'page_shop'            ),
			array( 'xen-levelup-rankings', __( 'Rankings',  'xen-levelup' ),       'page_rankings'        ),
			array( 'xen-levelup-analytics',__( 'Analytics', 'xen-levelup' ),       'page_analytics'       ),
			array( 'xen-levelup-settings', __( 'Settings',  'xen-levelup' ),       'page_settings'        ),
		);

		foreach ( $sub as $item ) {
			add_submenu_page( 'xen-levelup', $item[1], $item[1], 'manage_options', $item[0], array( $this, $item[2] ) );
		}
	}

	// ─── Assets ───────────────────────────────────────────────────────────

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'xen-levelup' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'xen-admin',
			XEN_LEVELUP_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			XEN_LEVELUP_VERSION
		);
		wp_enqueue_script(
			'xen-admin-js',
			XEN_LEVELUP_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-util' ),
			XEN_LEVELUP_VERSION,
			true
		);
		wp_localize_script( 'xen-admin-js', 'xenAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'xen_admin_nonce' ),
			'i18n'    => array(
				'confirmReset'  => __( 'Are you sure? This cannot be undone.', 'xen-levelup' ),
				'saving'        => __( 'Saving…', 'xen-levelup' ),
				'saved'         => __( 'Saved!', 'xen-levelup' ),
			),
		) );
	}

	// ─── Page Callbacks ───────────────────────────────────────────────────

	public function page_dashboard()    { $this->load_view( 'dashboard'    ); }
	public function page_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'xen-levelup' ) );
		}
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : ''; // phpcs:ignore
		if ( 'edit' === $action && ! empty( $_GET['uid'] ) ) { // phpcs:ignore
			$edit_user_id = absint( $_GET['uid'] ); // phpcs:ignore
			$this->load_view( 'user-edit' );
			return;
		}
		$this->load_view( 'users' );
	}
	public function page_quests()       { $this->load_view( 'quests'       ); }
	public function page_legendary()    { $this->load_view( 'legendary'    ); }
	public function page_achievements() { $this->load_view( 'achievements' ); }
	public function page_shop() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'xen-levelup' ) );
		}
		if ( isset( $_POST['xen_shop_action'] ) ) {
			$this->handle_shop_post();
		}
		$this->load_view( 'shop' );
	}

	private function handle_shop_post() {
		$posted_action = sanitize_key( $_POST['xen_shop_action'] ?? '' ); // phpcs:ignore
		$nonce         = sanitize_key( $_POST['xen_shop_nonce'] ?? '' );  // phpcs:ignore

		if ( ! wp_verify_nonce( $nonce, 'xen_shop_' . $posted_action ) ) {
			add_settings_error( 'xen_shop', 'nonce', __( 'Security check failed.', 'xen-levelup' ), 'error' );
			return;
		}

		switch ( $posted_action ) {
			case 'create':
				$result = xen_levelup()->shop->create_item( $_POST ); // phpcs:ignore
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'xen_shop', 'error', $result->get_error_message(), 'error' );
				} else {
					wp_safe_redirect( add_query_arg( 'xen_created', '1', admin_url( 'admin.php?page=xen-levelup-shop' ) ) );
					exit;
				}
				break;

			case 'update':
				$item_id = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore
				$result  = xen_levelup()->shop->update_item( $item_id, $_POST ); // phpcs:ignore
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'xen_shop', 'error', $result->get_error_message(), 'error' );
				} else {
					wp_safe_redirect( add_query_arg( 'xen_updated', '1', admin_url( 'admin.php?page=xen-levelup-shop' ) ) );
					exit;
				}
				break;

			case 'delete':
				$item_id = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore
				$result  = xen_levelup()->shop->delete_item( $item_id );
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'xen_shop', 'error', $result->get_error_message(), 'error' );
				} else {
					wp_safe_redirect( add_query_arg( 'xen_deleted', '1', admin_url( 'admin.php?page=xen-levelup-shop' ) ) );
					exit;
				}
				break;

			case 'toggle':
				$item_id = absint( $_POST['item_id'] ?? 0 ); // phpcs:ignore
				xen_levelup()->shop->toggle_active( $item_id );
				wp_safe_redirect( add_query_arg( 'xen_toggled', '1', admin_url( 'admin.php?page=xen-levelup-shop' ) ) );
				exit;
		}
	}
	// ─── Admin: Save User Stats (admin-post handler) ─────────────────────

	public function handle_save_user_stats() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'xen-levelup' ) );
		}

		$uid   = absint( $_POST['uid'] ?? 0 ); // phpcs:ignore
		$nonce = sanitize_key( $_POST['xen_edit_nonce'] ?? '' ); // phpcs:ignore

		if ( ! $uid || ! wp_verify_nonce( $nonce, 'xen_edit_user_' . $uid ) ) {
			wp_die( esc_html__( 'Security check failed.', 'xen-levelup' ) );
		}

		$level       = max( 1, min( 100, absint( $_POST['xen_level']       ?? 1  ) ) ); // phpcs:ignore
		$xp          = max( 0, absint( $_POST['xen_xp']          ?? 0  ) ); // phpcs:ignore
		$coins       = max( 0, absint( $_POST['xen_coins']        ?? 0  ) ); // phpcs:ignore
		$bonus_xp    = max( 0, absint( $_POST['xen_bonus_xp']    ?? 0  ) ); // phpcs:ignore
		$bonus_coins = max( 0, absint( $_POST['xen_bonus_coins'] ?? 0  ) ); // phpcs:ignore

		$final_xp    = $xp    + $bonus_xp;
		$final_coins = $coins + $bonus_coins;
		$rank_title  = Xen_User::rank_title_for_level( $level );

		$result = xen_levelup()->user->update_profile( $uid, array(
			'level'      => $level,
			'experience' => $final_xp,
			'coins'      => $final_coins,
			'rank_title' => $rank_title,
		) );

		$redirect = add_query_arg(
			array(
				'page'           => 'xen-levelup-users',
				'action'         => 'edit',
				'uid'            => $uid,
				'xen_user_saved' => '1',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	// ─── Admin: Dismiss What's New (AJAX) ─────────────────────────────────

	public function ajax_dismiss_whats_new() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
		}
		check_ajax_referer( 'xen_admin_dismiss_whats_new', 'nonce' );
		update_option( 'xen_admin_whats_new_dismissed', XEN_LEVELUP_VERSION );
		wp_send_json_success();
	}

	public function page_rankings()     { $this->load_view( 'rankings'     ); }
	public function page_analytics()    { $this->load_view( 'analytics'    ); }
	public function page_settings()     {
		// Handle settings save
		if ( isset( $_POST['xen_settings_nonce'] )
			&& wp_verify_nonce( sanitize_key( $_POST['xen_settings_nonce'] ), 'xen_save_settings' ) ) {
			$this->save_settings();
		}
		$this->load_view( 'settings' );
	}

	// ─── Settings Save ────────────────────────────────────────────────────

	private function save_settings() {
		$fields = array(
			'xen_levelup_dashboard_page'   => 'absint',
			'xen_levelup_profile_page'     => 'absint',
			'xen_levelup_onboarding_page'  => 'absint',
			'xen_levelup_shop_page'        => 'absint',
			'xen_levelup_rankings_page'    => 'absint',
			'xen_levelup_feed_page'        => 'absint',
			'xen_levelup_enable_notifications' => 'absint',
			'xen_levelup_enable_random_quests' => 'absint',
			'xen_levelup_legendary_count'  => 'absint',
			'xen_levelup_currency_name'    => 'sanitize_text_field',
			'xen_levelup_currency_symbol'  => 'sanitize_text_field',
		);
		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, $sanitizer( $_POST[ $key ] ) ); // phpcs:ignore
			}
		}
		// Checkbox fields that default to 0 when unchecked
		update_option( 'xen_disable_wp_dashboard', isset( $_POST['xen_disable_wp_dashboard'] ) ? 1 : 0 ); // phpcs:ignore
		add_settings_error( 'xen_settings', 'saved', __( 'Settings saved.', 'xen-levelup' ), 'updated' );
	}

	// ─── User List ───────────────────────────────────────────────────────

	public function add_user_columns( $columns ) {
		$columns['xen_level']     = __( 'Level',  'xen-levelup' );
		$columns['xen_rank']      = __( 'Rank',   'xen-levelup' );
		$columns['xen_xp']        = __( 'XP',     'xen-levelup' );
		return $columns;
	}

	public function user_column_content( $output, $column_name, $user_id ) {
		$profile = xen_levelup()->user->get_profile( $user_id );
		if ( ! $profile ) {
			return '—';
		}
		switch ( $column_name ) {
			case 'xen_level':
				return esc_html( $profile->level );
			case 'xen_rank':
				return '<span class="xen-admin-rank">' . esc_html( $profile->rank_title ) . '</span>';
			case 'xen_xp':
				return number_format( (int) $profile->experience );
		}
		return $output;
	}

	// ─── View Loader ─────────────────────────────────────────────────────

	/**
	 * Include an admin view from admin/views/.
	 *
	 * @param string $view View filename (without .php).
	 */
	private function load_view( $view ) {
		$file = XEN_LEVELUP_PLUGIN_DIR . 'admin/views/' . sanitize_file_name( $view ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
}
