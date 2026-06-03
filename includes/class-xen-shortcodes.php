<?php
/**
 * Shortcodes – registers all 15 public shortcodes and renders view templates.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Shortcodes
 */
class Xen_Shortcodes {

	public function __construct() {
		$tags = array(
			'gamified_dashboard'        => 'render_dashboard',
			'gamified_profile'          => 'render_profile',
			'gamified_quests'           => 'render_quests',
			'gamified_tasks'            => 'render_tasks',
			'gamified_habits'           => 'render_habits',
			'gamified_rankings'         => 'render_rankings',
			'gamified_shop'             => 'render_shop',
			'gamified_achievements'     => 'render_achievements',
			'gamified_stats'            => 'render_stats',
			'gamified_character'        => 'render_character',
			'gamified_level'            => 'render_level',
			'gamified_daily_quests'     => 'render_daily_quests',
			'gamified_special_quests'   => 'render_special_quests',
			'gamified_legendary_quests' => 'render_legendary_quests',
			'gamified_leaderboard'      => 'render_leaderboard',
		);

		foreach ( $tags as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	// ─── Helpers ─────────────────────────────────────────────────────────

	/**
	 * Load a public view template and return its output.
	 *
	 * @param string $template  Template filename (without .php).
	 * @param array  $atts      Shortcode attributes.
	 * @param array  $data      Variables to extract into template scope.
	 * @param bool   $login_req Whether login is required.
	 * @return string
	 */
	private function render( $template, array $atts = array(), array $data = array(), $login_req = true ) {
		if ( $login_req && ! is_user_logged_in() ) {
			return $this->login_prompt();
		}

		$file = XEN_LEVELUP_PLUGIN_DIR . 'public/views/' . $template . '.php';
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// Make safe shortcode atts available in template
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( array_merge( $data, array( 'atts' => $atts ) ), EXTR_SKIP );

		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * HTML prompt encouraging the visitor to log in.
	 *
	 * @return string
	 */
	private function login_prompt() {
		$login_url = wp_login_url( get_permalink() );
		return sprintf(
			'<div class="xen-login-prompt"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'You need to be logged in to access this content.', 'xen-levelup' ),
			esc_url( $login_url ),
			esc_html__( 'Log in here', 'xen-levelup' )
		);
	}

	// ─── Renderers ───────────────────────────────────────────────────────

	/** @param array $atts @return string */
	public function render_dashboard( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_dashboard' );
		$user_id = get_current_user_id();
		return $this->render( 'dashboard', $atts, array(
			'user_data' => xen_levelup()->user->get_full_data( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_profile( $atts ) {
		$atts = shortcode_atts( array( 'user_id' => 0 ), $atts, 'gamified_profile' );
		$uid  = $atts['user_id'] ? (int) $atts['user_id'] : get_current_user_id();
		return $this->render( 'profile', $atts, array(
			'user_data' => xen_levelup()->user->get_full_data( $uid ),
		) );
	}

	/** @param array $atts @return string */
	public function render_quests( $atts ) {
		$atts    = shortcode_atts( array( 'type' => 'daily' ), $atts, 'gamified_quests' );
		$user_id = get_current_user_id();
		return $this->render( 'quests', $atts, array(
			'user_id' => $user_id,
			'quests'  => xen_levelup()->quests->get_user_quests( $user_id, sanitize_key( $atts['type'] ), 'active' ),
		) );
	}

	/** @param array $atts @return string */
	public function render_daily_quests( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_daily_quests' );
		$user_id = get_current_user_id();
		return $this->render( 'daily-quests', $atts, array(
			'user_id' => $user_id,
			'quests'  => xen_levelup()->daily_quests->get_today( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_special_quests( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_special_quests' );
		$user_id = get_current_user_id();
		return $this->render( 'special-quests', $atts, array(
			'user_id' => $user_id,
			'quests'  => xen_levelup()->special_quests->get_active( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_legendary_quests( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_legendary_quests' );
		$user_id = get_current_user_id();
		return $this->render( 'legendary-quests', $atts, array(
			'user_id' => $user_id,
			'quests'  => xen_levelup()->legendary_quests->get_active( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_tasks( $atts ) {
		$atts    = shortcode_atts( array( 'status' => 'pending' ), $atts, 'gamified_tasks' );
		$user_id = get_current_user_id();
		return $this->render( 'tasks', $atts, array(
			'user_id' => $user_id,
			'tasks'   => xen_levelup()->tasks->get_tasks( $user_id, sanitize_key( $atts['status'] ) ),
		) );
	}

	/** @param array $atts @return string */
	public function render_habits( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_habits' );
		$user_id = get_current_user_id();
		return $this->render( 'habits', $atts, array(
			'user_id' => $user_id,
			'habits'  => xen_levelup()->habits->get_habits( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_rankings( $atts ) {
		$atts = shortcode_atts( array( 'period' => 'global', 'limit' => 50 ), $atts, 'gamified_rankings' );
		return $this->render( 'rankings', $atts, array(
			'period'  => sanitize_key( $atts['period'] ),
			'entries' => xen_levelup()->rankings->get_leaderboard( sanitize_key( $atts['period'] ), 'all', (int) $atts['limit'] ),
		), false );
	}

	/** @param array $atts @return string */
	public function render_leaderboard( $atts ) {
		return $this->render_rankings( $atts );
	}

	/** @param array $atts @return string */
	public function render_shop( $atts ) {
		$atts     = shortcode_atts( array( 'type' => 'all', 'per_page' => 12 ), $atts, 'gamified_shop' );
		$user_id  = get_current_user_id();
		$type     = sanitize_key( $atts['type'] );
		$per_page = min( 48, max( 4, (int) $atts['per_page'] ) );
		$total    = xen_levelup()->shop->count_items( $type, true );
		$pages    = max( 1, (int) ceil( $total / $per_page ) );

		return $this->render( 'shop', $atts, array(
			'user_id'   => $user_id,
			'items'     => xen_levelup()->shop->get_items_paged( $type, 1, $per_page ),
			'inventory' => is_user_logged_in() ? xen_levelup()->shop->get_inventory( $user_id ) : array(),
			'type'      => $type,
			'page'      => 1,
			'per_page'  => $per_page,
			'total'     => $total,
			'pages'     => $pages,
		) );
	}

	/** @param array $atts @return string */
	public function render_achievements( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_achievements' );
		$user_id = get_current_user_id();
		return $this->render( 'achievements', $atts, array(
			'user_id'      => $user_id,
			'achievements' => xen_levelup()->achievements->get_all( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_stats( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_stats' );
		$user_id = get_current_user_id();
		return $this->render( 'stats', $atts, array(
			'user_id' => $user_id,
			'stats'   => xen_levelup()->stats->get_all_stats( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_character( $atts ) {
		$atts    = shortcode_atts( array(), $atts, 'gamified_character' );
		$user_id = get_current_user_id();
		return $this->render( 'character', $atts, array(
			'user_data' => xen_levelup()->user->get_full_data( $user_id ),
			'stats'     => xen_levelup()->stats->get_all_stats( $user_id ),
		) );
	}

	/** @param array $atts @return string */
	public function render_level( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$user_id = get_current_user_id();
		$profile = xen_levelup()->user->get_profile( $user_id );
		if ( ! $profile ) {
			return '';
		}
		$atts = shortcode_atts( array( 'show_bar' => 1 ), $atts, 'gamified_level' );

		ob_start();
		?>
		<span class="xen-level-badge xen-rank-<?php echo esc_attr( sanitize_key( $profile->rank_title ) ); ?>">
			<?php echo esc_html( sprintf( __( 'Level %d', 'xen-levelup' ), $profile->level ) ); ?>
		</span>
		<?php if ( $atts['show_bar'] ) : ?>
		<div class="xen-xp-bar-mini">
			<div class="xen-xp-fill" style="width:<?php echo esc_attr( xen_levelup()->leveling->level_progress_percent( $user_id ) ); ?>%"></div>
		</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
}
