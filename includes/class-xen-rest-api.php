<?php
/**
 * REST API – all public and authenticated endpoints under xen/v1.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Rest_Api
 */
class Xen_Rest_Api {

	/** API namespace */
	const NS = 'xen/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all routes.
	 */
	public function register_routes() {
		// Profile
		register_rest_route( self::NS, '/profile', array(
			array( 'methods' => 'GET',   'callback' => array( $this, 'get_profile'    ), 'permission_callback' => array( $this, 'require_login' ) ),
			array( 'methods' => 'PATCH', 'callback' => array( $this, 'update_profile' ), 'permission_callback' => array( $this, 'require_login' ) ),
		) );

		// Stats
		register_rest_route( self::NS, '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_stats' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Quests
		register_rest_route( self::NS, '/quests', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_quests' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/quests/(?P<id>[\d]+)/complete', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'complete_quest' ),
			'permission_callback' => array( $this, 'require_login' ),
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// Tasks
		register_rest_route( self::NS, '/tasks', array(
			array( 'methods' => 'GET',  'callback' => array( $this, 'get_tasks'    ), 'permission_callback' => array( $this, 'require_login' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'create_task'  ), 'permission_callback' => array( $this, 'require_login' ) ),
		) );
		register_rest_route( self::NS, '/tasks/(?P<id>[\d]+)/complete', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'complete_task' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/tasks/(?P<id>[\d]+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_task' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Habits
		register_rest_route( self::NS, '/habits', array(
			array( 'methods' => 'GET',  'callback' => array( $this, 'get_habits'   ), 'permission_callback' => array( $this, 'require_login' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'create_habit' ), 'permission_callback' => array( $this, 'require_login' ) ),
		) );
		register_rest_route( self::NS, '/habits/(?P<id>[\d]+)/log', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'log_habit' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Achievements
		register_rest_route( self::NS, '/achievements', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_achievements' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Rankings (public)
		register_rest_route( self::NS, '/rankings', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_rankings' ),
			'permission_callback' => '__return_true',
		) );

		// Shop
		register_rest_route( self::NS, '/shop', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_shop' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NS, '/shop/(?P<id>[\d]+)/purchase', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'purchase_item' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/shop/(?P<id>[\d]+)/equip', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'equip_item' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Notifications
		register_rest_route( self::NS, '/notifications', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_notifications' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/notifications/read-all', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'mark_all_read' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/notifications/(?P<id>[\d]+)/read', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'mark_notification_read' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );

		// Onboarding
		register_rest_route( self::NS, '/onboarding/step', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_onboarding_step' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
		register_rest_route( self::NS, '/onboarding/complete', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'complete_onboarding' ),
			'permission_callback' => array( $this, 'require_login' ),
		) );
	}

	// ─── Permission Callbacks ────────────────────────────────────────────

	/**
	 * Verify the user is logged in and the REST nonce is valid.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function require_login( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You must be logged in.', 'xen-levelup' ), array( 'status' => 401 ) );
		}
		return true;
	}

	// ─── Response Helper ─────────────────────────────────────────────────

	/**
	 * Return a REST error response from a WP_Error.
	 *
	 * @param \WP_Error $error Error.
	 * @return \WP_REST_Response
	 */
	private function wp_error_response( WP_Error $error ) {
		return new WP_REST_Response( array( 'error' => $error->get_error_message() ), 400 );
	}

	// ─── Callbacks ───────────────────────────────────────────────────────

	public function get_profile( $request ) {
		$uid = get_current_user_id();
		return rest_ensure_response( xen_levelup()->user->get_full_data( $uid ) );
	}

	public function update_profile( $request ) {
		$uid    = get_current_user_id();
		$params = $request->get_json_params();
		$allow  = array( 'display_name', 'bio', 'timezone' );
		$data   = array();
		foreach ( $allow as $key ) {
			if ( isset( $params[ $key ] ) ) {
				$data[ $key ] = sanitize_text_field( $params[ $key ] );
			}
		}
		if ( ! empty( $data ) ) {
			xen_levelup()->user->update_profile( $uid, $data );
		}
		return rest_ensure_response( xen_levelup()->user->get_full_data( $uid ) );
	}

	public function get_stats( $request ) {
		return rest_ensure_response( xen_levelup()->stats->get_all_stats( get_current_user_id() ) );
	}

	public function get_quests( $request ) {
		$uid  = get_current_user_id();
		$type = sanitize_key( $request->get_param( 'type' ) ?? 'daily' );
		return rest_ensure_response( xen_levelup()->quests->get_user_quests( $uid, $type, 'active' ) );
	}

	public function complete_quest( $request ) {
		$result = xen_levelup()->quests->complete_quest( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( $result );
	}

	public function get_tasks( $request ) {
		$uid    = get_current_user_id();
		$status = sanitize_key( $request->get_param( 'status' ) ?? 'pending' );
		return rest_ensure_response( xen_levelup()->tasks->get_tasks( $uid, $status ) );
	}

	public function create_task( $request ) {
		$params = $request->get_json_params();
		$data   = array(
			'title'    => sanitize_text_field( $params['title']    ?? '' ),
			'notes'    => sanitize_textarea_field( $params['notes'] ?? '' ),
			'due_date' => sanitize_text_field( $params['due_date'] ?? '' ),
			'priority' => sanitize_key( $params['priority']        ?? 'medium' ),
			'category' => sanitize_key( $params['category']        ?? '' ),
		);
		$result = xen_levelup()->tasks->create( get_current_user_id(), $data );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( array( 'task_id' => $result ), 201 );
	}

	public function complete_task( $request ) {
		$result = xen_levelup()->tasks->complete( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( $result );
	}

	public function delete_task( $request ) {
		$result = xen_levelup()->tasks->delete( (int) $request['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public function get_habits( $request ) {
		return rest_ensure_response( xen_levelup()->habits->get_habits( get_current_user_id() ) );
	}

	public function create_habit( $request ) {
		$params = $request->get_json_params();
		$data   = array(
			'title'    => sanitize_text_field( $params['title']    ?? '' ),
			'category' => sanitize_key( $params['category']        ?? 'custom' ),
			'notes'    => sanitize_textarea_field( $params['notes'] ?? '' ),
		);
		$result = xen_levelup()->habits->create( get_current_user_id(), $data );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( array( 'habit_id' => $result ), 201 );
	}

	public function log_habit( $request ) {
		$params = $request->get_json_params();
		$notes  = sanitize_textarea_field( $params['notes'] ?? '' );
		$result = xen_levelup()->habits->log( (int) $request['id'], get_current_user_id(), $notes );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( $result );
	}

	public function get_achievements( $request ) {
		return rest_ensure_response( xen_levelup()->achievements->get_all( get_current_user_id() ) );
	}

	public function get_rankings( $request ) {
		$period = sanitize_key( $request->get_param( 'period' ) ?? 'global' );
		$limit  = max( 1, min( 100, (int) ( $request->get_param( 'limit' ) ?? 50 ) ) );
		return rest_ensure_response( xen_levelup()->rankings->get_leaderboard( $period, 'all', $limit ) );
	}

	public function get_shop( $request ) {
		$type = sanitize_key( $request->get_param( 'type' ) ?? 'all' );
		return rest_ensure_response( xen_levelup()->shop->get_items( $type ) );
	}

	public function purchase_item( $request ) {
		$result = xen_levelup()->shop->purchase( get_current_user_id(), (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( $result );
	}

	public function equip_item( $request ) {
		$params = $request->get_json_params();
		$equip  = isset( $params['equip'] ) ? (bool) $params['equip'] : true;
		$result = xen_levelup()->shop->equip( (int) $request['id'], get_current_user_id(), $equip );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( $result );
	}

	public function get_notifications( $request ) {
		$uid         = get_current_user_id();
		$unread_only = (bool) $request->get_param( 'unread_only' );
		$limit       = max( 1, min( 50, (int) ( $request->get_param( 'limit' ) ?? 20 ) ) );
		return rest_ensure_response( array(
			'notifications' => xen_levelup()->notifications->get( $uid, $unread_only, $limit ),
			'unread_count'  => xen_levelup()->notifications->unread_count( $uid ),
		) );
	}

	public function mark_all_read( $request ) {
		$uid = get_current_user_id();
		xen_levelup()->notifications->mark_all_read( $uid );
		return rest_ensure_response( array( 'unread_count' => 0 ) );
	}

	public function mark_notification_read( $request ) {
		$uid = get_current_user_id();
		xen_levelup()->notifications->mark_read( (int) $request['id'], $uid );
		return rest_ensure_response( array( 'unread_count' => xen_levelup()->notifications->unread_count( $uid ) ) );
	}

	public function save_onboarding_step( $request ) {
		$uid    = get_current_user_id();
		$params = $request->get_json_params();
		$step   = (int) ( $params['step'] ?? 0 );
		$data   = (array) ( $params['data'] ?? array() );

		// Walk-sanitize
		$clean = array();
		foreach ( $data as $k => $v ) {
			$clean[ sanitize_key( $k ) ] = is_array( $v )
				? array_map( 'sanitize_text_field', $v )
				: sanitize_text_field( (string) $v );
		}

		switch ( $step ) {
			case 1:
				xen_levelup()->onboarding->save_step_1( $uid, $clean );
				break;
			case 2:
				xen_levelup()->onboarding->save_step_2( $uid, $clean );
				break;
			case 3:
				xen_levelup()->onboarding->save_step_3( $uid, array_values( $clean ) );
				break;
			default:
				return new WP_REST_Response( array( 'error' => __( 'Invalid step.', 'xen-levelup' ) ), 400 );
		}

		return rest_ensure_response( array( 'step' => $step ) );
	}

	public function complete_onboarding( $request ) {
		$result = xen_levelup()->onboarding->complete( get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}
		return rest_ensure_response( array( 'stats' => $result ) );
	}
}
