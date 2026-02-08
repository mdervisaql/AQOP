<?php
/**
 * Users REST API Controller
 *
 * Provides REST API endpoints for user management.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Users_API class.
 *
 * REST API controller for user management.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Users_API
{

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'aqop/v1';

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes()
	{
		// List AQOP users (GET /aqop/v1/users)
		register_rest_route(
			$this->namespace,
			'/users',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_users'),
				'permission_callback' => array($this, 'check_admin_permission'),
				'args' => array(
					'role' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get single user (GET /aqop/v1/users/{id})
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_user'),
				'permission_callback' => array($this, 'check_admin_permission'),
			)
		);

		// Create user (POST /aqop/v1/users)
		register_rest_route(
			$this->namespace,
			'/users',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_user'),
				'permission_callback' => array($this, 'check_admin_permission'),
				'args' => $this->get_user_schema(),
			)
		);

		// Update user (PUT /aqop/v1/users/{id})
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_user'),
				'permission_callback' => array($this, 'check_admin_permission'),
				'args' => $this->get_update_schema(),
			)
		);

		// Delete user (DELETE /aqop/v1/users/{id})
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_user'),
				'permission_callback' => array($this, 'check_admin_permission'),
			)
		);
	}

	/**
	 * Get users list.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_users($request)
	{
		$params = $request->get_params();

		// AQOP roles
		$aqop_roles = array('aq_agent', 'aq_supervisor', 'operation_manager', 'operation_admin', 'administrator');

		// Build query args
		$args = array(
			'role__in' => $aqop_roles,
			'orderby' => 'registered',
			'order' => 'DESC',
		);

		// Filter by specific role
		if (!empty($params['role'])) {
			$roles = explode(',', $params['role']);
			$args['role__in'] = array_intersect($roles, $aqop_roles);
		}

		// Search
		if (!empty($params['search'])) {
			$args['search'] = '*' . sanitize_text_field($params['search']) . '*';
			$args['search_columns'] = array('user_login', 'user_email', 'display_name');
		}

		// Get users
		$user_query = new WP_User_Query($args);
		$users_data = array();

		foreach ($user_query->get_results() as $user) {
			$user_roles = $user->roles;
			$aqop_role = '';

			// Get primary AQOP role
			foreach ($aqop_roles as $role) {
				if (in_array($role, $user_roles, true)) {
					$aqop_role = $role;
					break;
				}
			}

			$users_data[] = array(
				'id' => $user->ID,
				'username' => $user->user_login,
				'email' => $user->user_email,
				'display_name' => $user->display_name,
				'role' => $aqop_role,
				'registered' => $user->user_registered,
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $users_data,
			)
		);
	}

	/**
	 * Get single user.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_user($request)
	{
		$user_id = $request['id'];
		$user = get_userdata($user_id);

		if (!$user) {
			return new WP_Error(
				'user_not_found',
				__('User not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'id' => $user->ID,
					'username' => $user->user_login,
					'email' => $user->user_email,
					'display_name' => $user->display_name,
					'role' => !empty($user->roles) ? $user->roles[0] : '',
					'registered' => $user->user_registered,
				),
			)
		);
	}

	/**
	 * Create user.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function create_user($request)
	{
		$params = $request->get_params();

		// Validate required fields
		if (empty($params['username']) || empty($params['email']) || empty($params['password'])) {
			return new WP_Error(
				'missing_required_fields',
				__('Username, email, and password are required.', 'aqop-leads'),
				array('status' => 400)
			);
		}

		// Create user
		$user_id = wp_create_user(
			sanitize_user($params['username']),
			$params['password'],
			sanitize_email($params['email'])
		);

		if (is_wp_error($user_id)) {
			return new WP_Error(
				'user_creation_failed',
				$user_id->get_error_message(),
				array('status' => 400)
			);
		}

		// Update display name
		if (!empty($params['display_name'])) {
			wp_update_user(
				array(
					'ID' => $user_id,
					'display_name' => sanitize_text_field($params['display_name']),
				)
			);
		}

		// Set role
		$user = new WP_User($user_id);
		$role = !empty($params['role']) ? sanitize_text_field($params['role']) : 'aq_agent';
		$user->set_role($role);

		// Get created user data
		$created_user = get_userdata($user_id);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'id' => $created_user->ID,
					'username' => $created_user->user_login,
					'email' => $created_user->user_email,
					'display_name' => $created_user->display_name,
					'role' => $role,
				),
				'message' => __('User created successfully.', 'aqop-leads'),
			),
			201
		);
	}

	/**
	 * Update user.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_user($request)
	{
		$user_id = $request['id'];
		$params = $request->get_params();

		// Check if user exists
		$user = get_userdata($user_id);
		if (!$user) {
			return new WP_Error(
				'user_not_found',
				__('User not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		// Build update array
		$update_data = array('ID' => $user_id);

		if (!empty($params['email'])) {
			$update_data['user_email'] = sanitize_email($params['email']);
		}

		if (!empty($params['display_name'])) {
			$update_data['display_name'] = sanitize_text_field($params['display_name']);
		}

		if (!empty($params['password'])) {
			$update_data['user_pass'] = $params['password'];
		}

		// Update user
		$updated = wp_update_user($update_data);

		if (is_wp_error($updated)) {
			return new WP_Error(
				'user_update_failed',
				$updated->get_error_message(),
				array('status' => 400)
			);
		}

		// Update role if provided
		if (!empty($params['role'])) {
			$user_obj = new WP_User($user_id);
			$user_obj->set_role(sanitize_text_field($params['role']));
		}

		// Get updated user data
		$updated_user = get_userdata($user_id);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'id' => $updated_user->ID,
					'username' => $updated_user->user_login,
					'email' => $updated_user->user_email,
					'display_name' => $updated_user->display_name,
					'role' => !empty($updated_user->roles) ? $updated_user->roles[0] : '',
				),
				'message' => __('User updated successfully.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Delete user.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function delete_user($request)
	{
		$user_id = $request['id'];

		// Check if user exists
		$user = get_userdata($user_id);
		if (!$user) {
			return new WP_Error(
				'user_not_found',
				__('User not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		// Prevent deleting self
		if ($user_id === get_current_user_id()) {
			return new WP_Error(
				'cannot_delete_self',
				__('You cannot delete your own account.', 'aqop-leads'),
				array('status' => 400)
			);
		}

		// Delete user
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$deleted = wp_delete_user($user_id);

		if (!$deleted) {
			return new WP_Error(
				'user_deletion_failed',
				__('Failed to delete user.', 'aqop-leads'),
				array('status' => 500)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'deleted' => true,
					'id' => $user_id,
				),
				'message' => __('User deleted successfully.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Check admin permission.
	 *
	 * Only operation_admin and administrator can manage users.
	 *
	 * @since  1.0.0
	 * @return bool|WP_Error Permission result.
	 */
	public function check_admin_permission()
	{
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to access this endpoint.', 'aqop-leads'),
				array('status' => 401)
			);
		}

		// Only admins can manage users
		$user = wp_get_current_user();
		$admin_roles = array('administrator', 'operation_admin');

		if (!array_intersect($admin_roles, $user->roles)) {
			return new WP_Error(
				'rest_forbidden',
				__('Only administrators can manage users.', 'aqop-leads'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Get user schema for validation.
	 *
	 * @since  1.0.0
	 * @return array User schema.
	 */
	private function get_user_schema()
	{
		return array(
			'username' => array(
				'required' => true,
				'type' => 'string',
				'sanitize_callback' => 'sanitize_user',
			),
			'email' => array(
				'required' => true,
				'type' => 'string',
				'format' => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'is_email',
			),
			'password' => array(
				'required' => true,
				'type' => 'string',
			),
			'display_name' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'role' => array(
				'type' => 'string',
				'enum' => array('aq_agent', 'aq_supervisor', 'operation_manager', 'operation_admin'),
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'aq_agent',
			),
		);
	}

	/**
	 * Get update schema for validation.
	 *
	 * @since  1.0.0
	 * @return array Update schema.
	 */
	private function get_update_schema()
	{
		return array(
			'email' => array(
				'type' => 'string',
				'format' => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'is_email',
			),
			'password' => array(
				'type' => 'string',
			),
			'display_name' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'role' => array(
				'type' => 'string',
				'enum' => array('aq_agent', 'aq_supervisor', 'operation_manager', 'operation_admin'),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}

