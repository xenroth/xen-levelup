<?php
/**
 * XEN LevelUp — GitHub Updater
 *
 * Hooks into WordPress's plugin update system to deliver updates
 * directly from GitHub Releases whenever a new version tag is pushed
 * to https://github.com/xenroth/xen-levelup
 *
 * How it works:
 *  1. On the `pre_set_site_transient_update_plugins` filter, we call the
 *     GitHub Releases API and compare the latest tag with the installed
 *     version. If a newer version exists, WordPress's native update UI
 *     lights up exactly as it would for a .org plugin.
 *  2. The `plugins_api` filter supplies the info pop-up content.
 *  3. `upgrader_post_install` renames the extracted folder from
 *     GitHub's `xenroth-xen-levelup-{hash}` back to `xen-levelup`.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xen_GitHub_Updater {

	/* ---------------------------------------------------------------
	   Configuration
	   --------------------------------------------------------------- */
	const GITHUB_USER = 'xenroth';
	const GITHUB_REPO = 'xen-levelup';
	const TRANSIENT    = 'xen_github_update_response';
	const CACHE_TTL    = 43200; // 12 hours

	/** @var string  Path to the main plugin file. */
	private $plugin_file;

	/** @var string  plugin_basename() result — used as the transient key. */
	private $slug;

	/** @var array|null  Decoded GitHub API response body (latest release). */
	private $release;

	/* ---------------------------------------------------------------
	   Constructor
	   --------------------------------------------------------------- */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->slug        = plugin_basename( $plugin_file );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'clear_transient_on_force_check' ) );
	}

	/* ---------------------------------------------------------------
	   1. Check for update
	   --------------------------------------------------------------- */

	/**
	 * Hooked to `pre_set_site_transient_update_plugins`.
	 * Inserts an update object if GitHub has a newer tag.
	 *
	 * @param  object $transient  WordPress update transient.
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version  = $this->parse_version( $release->tag_name );
		$current_version = defined( 'XEN_LEVELUP_VERSION' ) ? XEN_LEVELUP_VERSION : '0.0.0';

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$transient->response[ $this->slug ] = $this->build_update_object( $release, $remote_version );
		} else {
			/* Tell WordPress the plugin is up to date (suppresses false notices). */
			if ( ! isset( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$transient->no_update[ $this->slug ] = $this->build_update_object( $release, $remote_version );
		}

		return $transient;
	}

	/* ---------------------------------------------------------------
	   2. Plugin info popup
	   --------------------------------------------------------------- */

	/**
	 * Hooked to `plugins_api`.
	 * Fills the "View version details" popup with data from GitHub.
	 *
	 * @param  false|object $result  Default false.
	 * @param  string       $action  API action.
	 * @param  object       $args    Request args.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		// Match by slug (directory name, not full basename).
		$dir_slug = dirname( $this->slug );
		if ( empty( $args->slug ) || $args->slug !== $dir_slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = $this->parse_version( $release->tag_name );

		$info = new stdClass();
		$info->name          = 'XEN LevelUp';
		$info->slug          = $dir_slug;
		$info->version       = $remote_version;
		$info->author        = '<a href="https://github.com/' . self::GITHUB_USER . '">XEN Coders</a>';
		$info->homepage      = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
		$info->requires      = '5.8';
		$info->requires_php  = '7.4';
		$info->last_updated  = ! empty( $release->published_at ) ? date( 'Y-m-d', strtotime( $release->published_at ) ) : '';
		$info->download_link = $this->get_download_url( $release );
		$info->sections      = array(
			'description' => 'A Solo Leveling-inspired personal development system. Level up your real life through quests, habits, tasks, and a 10-tree Life Development System.',
			'changelog'   => ! empty( $release->body ) ? nl2br( esc_html( $release->body ) ) : 'See GitHub release notes.',
		);

		return $info;
	}

	/* ---------------------------------------------------------------
	   3. Post-install folder rename
	   --------------------------------------------------------------- */

	/**
	 * Hooked to `upgrader_post_install`.
	 * GitHub zips extract to `xenroth-xen-levelup-{hash}/` — rename it
	 * back to `xen-levelup/` so WordPress can find the plugin.
	 *
	 * @param  bool  $response    Upgrader success flag.
	 * @param  array $hook_extra  Extra hook data.
	 * @param  array $result      Upgrader result data.
	 * @return array  $result with corrected destination.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->slug ) {
			return $result;
		}

		$plugin_dir  = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
		$source      = $result['destination'];

		if ( $source !== $plugin_dir ) {
			$wp_filesystem->move( $source, $plugin_dir, true );
			$result['destination'] = $plugin_dir;
		}

		/* Re-activate the plugin so users don't land on a deactivated screen. */
		activate_plugin( $this->slug );

		return $result;
	}

	/* ---------------------------------------------------------------
	   4. Clear cache when admin forces a recheck
	   --------------------------------------------------------------- */

	/**
	 * Deletes the cached GitHub response if the admin clicks
	 * "Check Again" on the Updates screen.
	 */
	public function clear_transient_on_force_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] ) {
			delete_transient( self::TRANSIENT );
		}
	}

	/* ---------------------------------------------------------------
	   Private helpers
	   --------------------------------------------------------------- */

	/**
	 * Fetch and cache the latest GitHub release.
	 *
	 * @return object|null  Decoded release object, or null on failure.
	 */
	private function get_release() {
		if ( null !== $this->release ) {
			return $this->release;
		}

		$cached = get_transient( self::TRANSIENT );
		if ( false !== $cached ) {
			$this->release = $cached;
			return $this->release;
		}

		$api_url  = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; XEN-LevelUp-Updater/' . ( defined( 'XEN_LEVELUP_VERSION' ) ? XEN_LEVELUP_VERSION : '0' ),
				'headers'    => $this->get_auth_headers(),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->tag_name ) ) {
			return null;
		}

		$this->release = $body;
		set_transient( self::TRANSIENT, $this->release, self::CACHE_TTL );

		return $this->release;
	}

	/**
	 * Return optional Bearer auth headers if a token option is set.
	 * Set via WP option `xen_levelup_github_token` or the constant
	 * XEN_LEVELUP_GITHUB_TOKEN.
	 *
	 * @return array
	 */
	private function get_auth_headers() {
		$token = '';

		if ( defined( 'XEN_LEVELUP_GITHUB_TOKEN' ) && XEN_LEVELUP_GITHUB_TOKEN ) {
			$token = XEN_LEVELUP_GITHUB_TOKEN;
		} elseif ( $opt = get_option( 'xen_levelup_github_token' ) ) {
			$token = $opt;
		}

		if ( $token ) {
			return array( 'Authorization' => 'Bearer ' . sanitize_text_field( $token ) );
		}

		return array();
	}

	/**
	 * Pick the best download URL from a release:
	 * prefer a release asset named `xen-levelup.zip` if present,
	 * otherwise fall back to the auto-generated zipball.
	 *
	 * @param  object $release  GitHub release object.
	 * @return string
	 */
	private function get_download_url( $release ) {
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( ! empty( $asset->browser_download_url ) &&
				     preg_match( '/\.zip$/i', $asset->browser_download_url ) ) {
					return $asset->browser_download_url;
				}
			}
		}

		return ! empty( $release->zipball_url ) ? $release->zipball_url : '';
	}

	/**
	 * Strip a leading "v" from a tag name.
	 *
	 * @param  string $tag  e.g. "v1.2.0" or "1.2.0".
	 * @return string
	 */
	private function parse_version( $tag ) {
		return ltrim( trim( (string) $tag ), 'vV' );
	}

	/**
	 * Build the stdClass update object WordPress expects.
	 *
	 * @param  object $release        GitHub release.
	 * @param  string $remote_version Parsed version string.
	 * @return object
	 */
	private function build_update_object( $release, $remote_version ) {
		$obj                = new stdClass();
		$obj->slug          = dirname( $this->slug );
		$obj->plugin        = $this->slug;
		$obj->new_version   = $remote_version;
		$obj->tested        = '6.6';
		$obj->package       = $this->get_download_url( $release );
		$obj->url           = 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO;
		$obj->icons         = array();
		$obj->banners       = array();
		$obj->requires      = '5.8';
		$obj->requires_php  = '7.4';

		return $obj;
	}
}
