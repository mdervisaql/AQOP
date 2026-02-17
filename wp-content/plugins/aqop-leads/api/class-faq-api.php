<?php
/**
 * FAQ API - Manages FAQ questions and answers per country.
 *
 * Permissions:
 * - GET (read): All authenticated users can read FAQs
 * - POST/PUT/DELETE (write): Admin, Operation Admin, Operation Manager, Country Manager (own country only)
 *
 * @package AQOP_Leads
 */

if (!defined('ABSPATH')) {
	exit;
}

class AQOP_FAQ_API
{
	private $namespace = 'aqop/v1';

	public function register_routes()
	{
		// Get FAQs (with optional country filter)
		register_rest_route(
			$this->namespace,
			'/faqs',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array($this, 'get_faqs'),
					'permission_callback' => array($this, 'check_read_permission'),
					'args' => array(
						'country_id' => array(
							'type' => 'integer',
							'sanitize_callback' => 'absint',
						),
						'category' => array(
							'type' => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'search' => array(
							'type' => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array($this, 'create_faq'),
					'permission_callback' => array($this, 'check_write_permission'),
				),
			)
		);

		// Update/Delete single FAQ
		register_rest_route(
			$this->namespace,
			'/faqs/(?P<id>\d+)',
			array(
				array(
					'methods' => WP_REST_Server::EDITABLE,
					'callback' => array($this, 'update_faq'),
					'permission_callback' => array($this, 'check_write_permission'),
				),
				array(
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => array($this, 'delete_faq'),
					'permission_callback' => array($this, 'check_write_permission'),
				),
			)
		);

		// Get FAQ categories
		register_rest_route(
			$this->namespace,
			'/faqs/categories',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_categories'),
				'permission_callback' => array($this, 'check_read_permission'),
			)
		);
	}

	/**
	 * Get FAQs - filtered by country and role.
	 */
	public function get_faqs($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'aq_faq';

		// Check if table exists
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
		if (!$exists) {
			return new WP_REST_Response(array('success' => true, 'data' => array()));
		}

		$where = array('f.is_active = 1');
		$values = array();

		// Country filter
		$country_id = $request->get_param('country_id');

		// For country managers: auto-filter to their countries
		if ($this->is_country_manager() && !$this->is_admin_or_above()) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
			$user_countries = AQOP_Leads_Users_API::get_user_countries(get_current_user_id());
			if (!empty($user_countries)) {
				$placeholders = implode(',', array_fill(0, count($user_countries), '%d'));
				$where[] = "(f.country_id IN ({$placeholders}) OR f.country_id IS NULL)";
				foreach ($user_countries as $cid) {
					$values[] = absint($cid);
				}
			}
		} elseif ($country_id) {
			// Explicit filter: show country-specific + global
			$where[] = '(f.country_id = %d OR f.country_id IS NULL)';
			$values[] = absint($country_id);
		}
		// Admins see all if no filter

		// Category filter
		$category = $request->get_param('category');
		if ($category) {
			$where[] = 'f.category = %s';
			$values[] = $category;
		}

		// Search filter
		$search = $request->get_param('search');
		if ($search) {
			$where[] = '(f.question LIKE %s OR f.answer LIKE %s)';
			$search_like = '%' . $wpdb->esc_like($search) . '%';
			$values[] = $search_like;
			$values[] = $search_like;
		}

		$where_clause = implode(' AND ', $where);

		$sql = "SELECT f.*, c.country_name_ar, c.country_name_en, c.country_code
				FROM {$table} f
				LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON f.country_id = c.id
				WHERE {$where_clause}
				ORDER BY f.country_id ASC, f.category ASC, f.display_order ASC, f.id ASC";

		if (!empty($values)) {
			$sql = $wpdb->prepare($sql, $values);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$faqs = $wpdb->get_results($sql);

		return new WP_REST_Response(array('success' => true, 'data' => $faqs));
	}

	/**
	 * Create FAQ.
	 */
	public function create_faq($request)
	{
		global $wpdb;
		$params = $request->get_params();

		if (empty($params['question']) || empty($params['answer'])) {
			return new WP_Error('missing_fields', 'question and answer are required', array('status' => 400));
		}

		// Country managers can only create for their own countries
		$country_id = isset($params['country_id']) ? absint($params['country_id']) : null;
		if ($this->is_country_manager() && !$this->is_admin_or_above()) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
			$user_countries = AQOP_Leads_Users_API::get_user_countries(get_current_user_id());
			if (empty($user_countries)) {
				return new WP_Error('no_country', 'No country assigned to your account', array('status' => 403));
			}
			// If provided country_id must be in their list, else default to first
			if ($country_id && !in_array($country_id, $user_countries, true)) {
				$country_id = $user_countries[0];
			} elseif (!$country_id) {
				$country_id = $user_countries[0];
			}
		}

		$data = array(
			'country_id' => $country_id,
			'category' => isset($params['category']) ? sanitize_text_field($params['category']) : null,
			'question' => sanitize_textarea_field($params['question']),
			'answer' => sanitize_textarea_field($params['answer']),
			'display_order' => isset($params['display_order']) ? absint($params['display_order']) : 0,
			'is_active' => 1,
			'created_by' => get_current_user_id(),
		);

		$formats = array('%d', '%s', '%s', '%s', '%d', '%d', '%d');
		if ($data['country_id'] === null) {
			$formats[0] = null; // Handle NULL
			unset($data['country_id']);
		}

		$inserted = $wpdb->insert($wpdb->prefix . 'aq_faq', $data, $formats);

		if (!$inserted) {
			return new WP_Error('db_error', 'Failed to create FAQ', array('status' => 500));
		}

		return new WP_REST_Response(array(
			'success' => true,
			'data' => array('id' => $wpdb->insert_id),
			'message' => 'FAQ created successfully',
		));
	}

	/**
	 * Update FAQ.
	 */
	public function update_faq($request)
	{
		global $wpdb;
		$id = absint($request['id']);
		$params = $request->get_params();
		$table = $wpdb->prefix . 'aq_faq';

		// Get existing FAQ
		$faq = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
		if (!$faq) {
			return new WP_Error('not_found', 'FAQ not found', array('status' => 404));
		}

		// Country managers can only edit FAQs in their countries
		if ($this->is_country_manager() && !$this->is_admin_or_above()) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
			$user_countries = AQOP_Leads_Users_API::get_user_countries(get_current_user_id());
			if ($faq->country_id && !in_array((int) $faq->country_id, $user_countries, true)) {
				return new WP_Error('forbidden', 'You can only edit FAQs for your countries', array('status' => 403));
			}
		}

		$update_data = array();
		if (isset($params['question'])) $update_data['question'] = sanitize_textarea_field($params['question']);
		if (isset($params['answer'])) $update_data['answer'] = sanitize_textarea_field($params['answer']);
		if (isset($params['category'])) $update_data['category'] = sanitize_text_field($params['category']);
		if (isset($params['display_order'])) $update_data['display_order'] = absint($params['display_order']);
		if (isset($params['is_active'])) $update_data['is_active'] = absint($params['is_active']);

		// Only admins can change country_id
		if (isset($params['country_id']) && $this->is_admin_or_above()) {
			$update_data['country_id'] = $params['country_id'] ? absint($params['country_id']) : null;
		}

		if (empty($update_data)) {
			return new WP_Error('no_fields', 'No fields to update', array('status' => 400));
		}

		$updated = $wpdb->update($table, $update_data, array('id' => $id));

		return new WP_REST_Response(array('success' => true, 'message' => 'FAQ updated'));
	}

	/**
	 * Delete FAQ (soft delete).
	 */
	public function delete_faq($request)
	{
		global $wpdb;
		$id = absint($request['id']);
		$table = $wpdb->prefix . 'aq_faq';

		// Get existing FAQ
		$faq = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
		if (!$faq) {
			return new WP_Error('not_found', 'FAQ not found', array('status' => 404));
		}

		// Country managers can only delete FAQs in their countries
		if ($this->is_country_manager() && !$this->is_admin_or_above()) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
			$user_countries = AQOP_Leads_Users_API::get_user_countries(get_current_user_id());
			if ($faq->country_id && !in_array((int) $faq->country_id, $user_countries, true)) {
				return new WP_Error('forbidden', 'You can only delete FAQs for your countries', array('status' => 403));
			}
		}

		$wpdb->update($table, array('is_active' => 0), array('id' => $id));

		return new WP_REST_Response(array('success' => true, 'message' => 'FAQ deleted'));
	}

	/**
	 * Get distinct categories.
	 */
	public function get_categories()
	{
		global $wpdb;
		$table = $wpdb->prefix . 'aq_faq';

		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
		if (!$exists) {
			return new WP_REST_Response(array('success' => true, 'data' => array()));
		}

		$categories = $wpdb->get_col(
			"SELECT DISTINCT category FROM {$table} WHERE is_active = 1 AND category IS NOT NULL AND category != '' ORDER BY category ASC"
		);

		return new WP_REST_Response(array('success' => true, 'data' => $categories));
	}

	/**
	 * Read permission: any authenticated user.
	 */
	public function check_read_permission()
	{
		return is_user_logged_in();
	}

	/**
	 * Write permission: admin, operation_admin, operation_manager, aq_country_manager.
	 */
	public function check_write_permission()
	{
		if (!is_user_logged_in()) {
			return new WP_Error('rest_not_logged_in', 'You must be logged in.', array('status' => 401));
		}
		$user = wp_get_current_user();
		$allowed = array('administrator', 'operation_admin', 'operation_manager', 'aq_country_manager');
		return (bool) array_intersect($allowed, $user->roles);
	}

	/**
	 * Check if current user is a country manager.
	 */
	private function is_country_manager()
	{
		$user = wp_get_current_user();
		return in_array('aq_country_manager', $user->roles, true);
	}

	/**
	 * Check if current user is admin or above (can see/edit all).
	 */
	private function is_admin_or_above()
	{
		$user = wp_get_current_user();
		$admin_roles = array('administrator', 'operation_admin', 'operation_manager');
		return (bool) array_intersect($admin_roles, $user->roles);
	}
}
