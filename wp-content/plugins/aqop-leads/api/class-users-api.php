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
		$aqop_roles = array('aq_agent', 'aq_supervisor', 'aq_country_manager', 'operation_manager', 'operation_admin', 'administrator');

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

			// Get assigned countries (supports both old single and new multi format)
			$country_ids = self::get_user_countries($user->ID);
			$country_names = self::get_country_names($country_ids);

			$can_see_unassigned = (bool) get_user_meta($user->ID, 'aq_can_see_unassigned_countries', true);

			$users_data[] = array(
				'id' => $user->ID,
				'username' => $user->user_login,
				'email' => $user->user_email,
				'display_name' => $user->display_name,
				'role' => $aqop_role,
				'registered' => $user->user_registered,
				'country_id' => !empty($country_ids) ? $country_ids[0] : null, // backward compat
				'country_ids' => $country_ids,
				'country_name' => !empty($country_names) ? implode(', ', $country_names) : '',
				'country_names' => $country_names,
				'can_see_unassigned_countries' => $can_see_unassigned,
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

		$country_ids = self::get_user_countries($user->ID);
		$country_names = self::get_country_names($country_ids);
		$can_see_unassigned = (bool) get_user_meta($user->ID, 'aq_can_see_unassigned_countries', true);

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
					'country_id' => !empty($country_ids) ? $country_ids[0] : null,
					'country_ids' => $country_ids,
					'country_name' => !empty($country_names) ? implode(', ', $country_names) : '',
					'country_names' => $country_names,
					'can_see_unassigned_countries' => $can_see_unassigned,
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

		// Save countries
		if (!empty($params['country_ids']) && is_array($params['country_ids'])) {
			$clean_ids = array_filter(array_map('absint', $params['country_ids']));
			if (!empty($clean_ids)) {
				update_user_meta($user_id, 'aq_assigned_countries', $clean_ids);
				update_user_meta($user_id, 'aq_assigned_country', $clean_ids[0]);
			}
		} elseif (!empty($params['country_id'])) {
			$cid = absint($params['country_id']);
			update_user_meta($user_id, 'aq_assigned_country', $cid);
			update_user_meta($user_id, 'aq_assigned_countries', array($cid));
		}

		// Save "can see unassigned countries" flag
		if (isset($params['can_see_unassigned_countries'])) {
			update_user_meta($user_id, 'aq_can_see_unassigned_countries', (bool) $params['can_see_unassigned_countries']);
		}

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
			$clean_email = sanitize_email($params['email']);
			if (!is_email($clean_email)) {
				return new WP_Error('invalid_email', __('Invalid email address.', 'aqop-leads'), array('status' => 400));
			}
			// Check if email is taken by another user
			$existing = email_exists($clean_email);
			if ($existing && (int) $existing !== (int) $user_id) {
				return new WP_Error('email_exists', __('This email is already in use.', 'aqop-leads'), array('status' => 400));
			}
			$update_data['user_email'] = $clean_email;
		}

		if (!empty($params['display_name'])) {
			$update_data['display_name'] = sanitize_text_field($params['display_name']);
		}

		if (!empty($params['password'])) {
			$update_data['user_pass'] = $params['password'];
		}

		error_log('AQOP: Updating user ' . $user_id . ' with fields: ' . implode(', ', array_keys($update_data)));

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

		// Update countries if provided (supports both single and multi)
		if (isset($params['country_ids'])) {
			// Multi-country support
			delete_user_meta($user_id, 'aq_assigned_country');
			delete_user_meta($user_id, 'aq_assigned_countries');
			$ids = is_array($params['country_ids']) ? $params['country_ids'] : array();
			$clean_ids = array_filter(array_map('absint', $ids));
			if (!empty($clean_ids)) {
				update_user_meta($user_id, 'aq_assigned_countries', $clean_ids);
				// Keep backward compat: first country in old meta
				update_user_meta($user_id, 'aq_assigned_country', $clean_ids[0]);
			}
		} elseif (isset($params['country_id'])) {
			// Single country (backward compat)
			delete_user_meta($user_id, 'aq_assigned_country');
			delete_user_meta($user_id, 'aq_assigned_countries');
			if (!empty($params['country_id'])) {
				$cid = absint($params['country_id']);
				update_user_meta($user_id, 'aq_assigned_country', $cid);
				update_user_meta($user_id, 'aq_assigned_countries', array($cid));
			}
		}

		// Update "can see unassigned countries" flag
		if (isset($params['can_see_unassigned_countries'])) {
			update_user_meta($user_id, 'aq_can_see_unassigned_countries', (bool) $params['can_see_unassigned_countries']);
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
				'enum' => array('aq_agent', 'aq_supervisor', 'aq_country_manager', 'operation_manager', 'operation_admin'),
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'aq_agent',
			),
			'country_ids' => array(
				'type' => 'array',
				'items' => array('type' => 'integer'),
			),
			'country_id' => array(
				'type' => array('integer', 'string'),
				'sanitize_callback' => 'absint',
			),
			'can_see_unassigned_countries' => array(
				'type' => 'boolean',
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
				'sanitize_callback' => 'sanitize_email',
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
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country_id' => array(
				'type' => array('integer', 'string'),
				'sanitize_callback' => 'absint',
			),
			'country_ids' => array(
				'type' => 'array',
				'items' => array('type' => 'integer'),
			),
			'can_see_unassigned_countries' => array(
				'type' => 'boolean',
			),
		);
	}

	/**
	 * Get user's assigned countries (supports old single + new multi format).
	 * Also includes unassigned countries if user has that permission.
	 */
	public static function get_user_countries($user_id)
	{
		$assigned_countries = array();

		// Try new multi format first
		$countries = get_user_meta($user_id, 'aq_assigned_countries', true);
		if (!empty($countries) && is_array($countries)) {
			$assigned_countries = array_map('absint', $countries);
		} else {
			// Fallback to old single format
			$single = get_user_meta($user_id, 'aq_assigned_country', true);
			if (!empty($single)) {
				$assigned_countries = array(absint($single));
			}
		}

		// If user can see unassigned countries, add them
		$can_see_unassigned = (bool) get_user_meta($user_id, 'aq_can_see_unassigned_countries', true);
		if ($can_see_unassigned) {
			global $wpdb;
			
			// Get all active countries
			$all_countries = $wpdb->get_col(
				"SELECT id FROM {$wpdb->prefix}aq_dim_countries WHERE is_active = 1"
			);
			$all_countries = array_map('absint', $all_countries);

			// Get countries assigned to ANY user
			$assigned_to_users = $wpdb->get_col(
				"SELECT DISTINCT meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = 'aq_assigned_countries'"
			);
			
			$assigned_ids = array();
			foreach ($assigned_to_users as $meta_value) {
				$countries_array = maybe_unserialize($meta_value);
				if (is_array($countries_array)) {
					$assigned_ids = array_merge($assigned_ids, array_map('absint', $countries_array));
				}
			}
			$assigned_ids = array_unique($assigned_ids);

			// Unassigned countries = all countries - assigned countries
			$unassigned_countries = array_diff($all_countries, $assigned_ids);

			// Merge user's assigned countries with unassigned countries
			$assigned_countries = array_unique(array_merge($assigned_countries, $unassigned_countries));
			sort($assigned_countries);
		}

		return $assigned_countries;
	}

	/**
	 * Get country names from IDs.
	 */
	private static function get_country_names($country_ids)
	{
		if (empty($country_ids)) return array();

		global $wpdb;
		$placeholders = implode(',', array_fill(0, count($country_ids), '%d'));

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT country_name_en FROM {$wpdb->prefix}aq_dim_countries WHERE id IN ({$placeholders})",
				$country_ids
			)
		);

		return $names ?: array();
	}
}

