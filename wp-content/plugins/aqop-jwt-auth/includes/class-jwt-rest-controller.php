<?php
/**
 * JWT REST API Controller
 *
 * Handles all JWT authentication endpoints.
 *
 * === JWT AUTHENTICATION SYSTEM (Hour 1) ===
 * Generated: 2025-11-17
 * Security Level: Enterprise Grade
 * Algorithm: HS256 (HMAC-SHA256)
 * Token Expiry: Access 15min, Refresh 7days
 * === END JWT AUTHENTICATION ===
 *
 * @package AQOP_JWT_Auth
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_JWT_REST_Controller class.
 *
 * Manages JWT REST API endpoints.
 *
 * @since 1.0.0
 */
class AQOP_JWT_REST_Controller
{

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'aqop-jwt/v1';

	/**
	 * Allowed roles for authentication.
	 *
	 * @var array
	 */
	private static $allowed_roles = array(
		'administrator',
		'operation_admin',
		'operation_manager',
		'aq_country_manager',
		'aq_supervisor',
		'aq_agent',
	);

	/**
	 * Initialize REST controller.
	 *
	 * @since 1.0.0
	 */
	public static function init()
	{
		add_action('rest_api_init', array(__CLASS__, 'register_routes'));
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_routes()
	{
		// Login endpoint.
		register_rest_route(
			self::NAMESPACE ,
			'/login',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array(__CLASS__, 'login'),
				'permission_callback' => '__return_true',
				'args' => array(
					'username' => array(
						'required' => true,
						'type' => 'string',
						'sanitize_callback' => 'sanitize_user',
					),
					'password' => array(
						'required' => true,
						'type' => 'string',
					),
				),
			)
		);

		// Refresh token endpoint.
		register_rest_route(
			self::NAMESPACE ,
			'/refresh',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array(__CLASS__, 'refresh'),
				'permission_callback' => '__return_true',
				'args' => array(
					'refresh_token' => array(
						'required' => true,
						'type' => 'string',
					),
				),
			)
		);

		// Logout endpoint.
		register_rest_route(
			self::NAMESPACE ,
			'/logout',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array(__CLASS__, 'logout'),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Validate token endpoint.
		register_rest_route(
			self::NAMESPACE ,
			'/validate',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array(__CLASS__, 'validate'),
				'permission_callback' => '__return_true',
				'args' => array(
					'token' => array(
						'required' => true,
						'type' => 'string',
					),
				),
			)
		);
	}

	/**
	 * Login endpoint.
	 *
	 * Authenticates user and returns JWT tokens.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function login($request)
	{
		$username = $request->get_param('username');
		$password = $request->get_param('password');

		// Authenticate user.
		$user = wp_authenticate($username, $password);

		if (is_wp_error($user)) {
			return new WP_Error(
				'authentication_failed',
				__('Invalid username or password.', 'aqop-jwt-auth'),
				array('status' => 401)
			);
		}

		// Check if user has allowed role.
		if (!self::user_has_allowed_role($user)) {
			return new WP_Error(
				'forbidden_role',
				__('You do not have permission to access this system.', 'aqop-jwt-auth'),
				array('status' => 403)
			);
		}

		// Generate tokens.
		$access_token = AQOP_JWT_Handler::create_token($user->ID, 'access');
		$refresh_token = AQOP_JWT_Handler::create_token($user->ID, 'refresh');

		if (is_wp_error($access_token)) {
			return $access_token;
		}

		if (is_wp_error($refresh_token)) {
			return $refresh_token;
		}

		// Get user data.
		$user_data = self::get_user_data($user);

		// Create session for activity tracking
		$session_token = null;
		if (class_exists('AQOP_Session_Manager')) {
			$session_token = AQOP_Session_Manager::start_session($user->ID, array(
				'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			));
		}

		// Return response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'access_token' => $access_token,
					'refresh_token' => $refresh_token,
					'expires_in' => AQOP_JWT_ACCESS_EXPIRY,
					'token_type' => 'Bearer',
					'user' => $user_data,
					'session_token' => $session_token,
				),
			),
			200
		);
	}

	/**
	 * Refresh token endpoint.
	 *
	 * Generates new access token from refresh token.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function refresh($request)
	{
		$refresh_token = $request->get_param('refresh_token');

		// Generate new access token.
		$access_token = AQOP_JWT_Handler::refresh_access_token($refresh_token);

		if (is_wp_error($access_token)) {
			return $access_token;
		}

		// Decode token to get user data.
		$payload = AQOP_JWT_Handler::decode($access_token, 'access');

		if (is_wp_error($payload)) {
			return $payload;
		}

		$user_data = isset($payload['data']['user']) ? $payload['data']['user'] : array();

		// Return response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'access_token' => $access_token,
					'expires_in' => AQOP_JWT_ACCESS_EXPIRY,
					'token_type' => 'Bearer',
					'user' => $user_data,
				),
			),
			200
		);
	}

	/**
	 * Logout endpoint.
	 *
	 * Blacklists the current access token.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function logout($request)
	{
		// Get token from request.
		$token = AQOP_JWT_Handler::get_token_from_request();

		if (!$token) {
			return new WP_Error(
				'no_token',
				__('No token provided.', 'aqop-jwt-auth'),
				array('status' => 400)
			);
		}

		// Blacklist token.
		$blacklisted = AQOP_JWT_Handler::blacklist_token($token);

		if (!$blacklisted) {
			return new WP_Error(
				'blacklist_failed',
				__('Failed to blacklist token.', 'aqop-jwt-auth'),
				array('status' => 500)
			);
		}

		// Return response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __('Logged out successfully.', 'aqop-jwt-auth'),
			),
			200
		);
	}

	/**
	 * Validate token endpoint.
	 *
	 * Validates a JWT token and returns user data.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public static function validate($request)
	{
		$token = $request->get_param('token');

		// Decode and validate token.
		$payload = AQOP_JWT_Handler::decode($token, 'access');

		if (is_wp_error($payload)) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'valid' => false,
					'error' => $payload->get_error_message(),
				),
				200
			);
		}

		$user_data = isset($payload['data']['user']) ? $payload['data']['user'] : array();

		// Return response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'valid' => true,
				'user' => $user_data,
			),
			200
		);
	}

	/**
	 * Check if user has allowed role.
	 *
	 * @since  1.0.0
	 * @param  WP_User $user User object.
	 * @return bool True if user has allowed role.
	 */
	private static function user_has_allowed_role($user)
	{
		$user_roles = (array) $user->roles;

		// Get allowed roles from options.
		$allowed_roles = get_option('aqop_jwt_allowed_roles', array());

		// Fallback to defaults if empty (first run).
		if (empty($allowed_roles)) {
			$allowed_roles = array(
				'administrator',
				'operation_admin',
				'operation_manager',
				'aq_country_manager',
				'aq_supervisor',
				'aq_agent',
			);
		}

		return !empty(array_intersect($user_roles, $allowed_roles));
	}

	/**
	 * Get formatted user data.
	 *
	 * @since  1.0.0
	 * @param  WP_User $user User object.
	 * @return array User data.
	 */
	private static function get_user_data($user)
	{
		$roles = $user->roles;
		$primary_role = !empty($roles) ? $roles[0] : 'subscriber';

		// Get assigned countries
		$country_ids = array();
		$countries_meta = get_user_meta($user->ID, 'aq_assigned_countries', true);
		if (!empty($countries_meta) && is_array($countries_meta)) {
			$country_ids = array_map('absint', $countries_meta);
		} else {
			$single = get_user_meta($user->ID, 'aq_assigned_country', true);
			if (!empty($single)) {
				$country_ids = array(absint($single));
			}
		}

		return array(
			'id' => $user->ID,
			'username' => $user->user_login,
			'email' => $user->user_email,
			'display_name' => $user->display_name,
			'role' => $primary_role,
			'capabilities' => array_keys($user->allcaps),
			'country_ids' => $country_ids,
		);
	}
}

