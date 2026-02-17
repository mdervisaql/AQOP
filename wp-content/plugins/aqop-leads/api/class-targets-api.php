<?php
/**
 * Conversion Targets REST API Controller
 *
 * Manages conversion targets (global and per-country).
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

class AQOP_Targets_API
{
	private $namespace = 'aqop/v1';

	/**
	 * Register REST API routes.
	 */
	public function register_routes()
	{
		// Get targets (GET /aqop/v1/targets or /aqop/v1/targets?country_id=5)
		register_rest_route(
			$this->namespace,
			'/targets',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_targets'),
				'permission_callback' => array($this, 'check_read_permission'),
			)
		);

		// Create/Update targets (POST /aqop/v1/targets)
		register_rest_route(
			$this->namespace,
			'/targets',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'save_targets'),
				'permission_callback' => array($this, 'check_write_permission'),
				'args' => $this->get_schema(),
			)
		);

		// Delete country-specific targets (DELETE /aqop/v1/targets/{country_id})
		register_rest_route(
			$this->namespace,
			'/targets/(?P<country_id>\d+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_targets'),
				'permission_callback' => array($this, 'check_write_permission'),
			)
		);
	}

	/**
	 * Get conversion targets.
	 * 
	 * Returns global targets or country-specific if country_id provided.
	 */
	public function get_targets($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'aq_conversion_targets';
		$country_id = $request->get_param('country_id');

		if ($country_id !== null) {
			// Get country-specific targets or fall back to global
			$targets = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE country_id = %d",
					absint($country_id)
				),
				ARRAY_A
			);

			// If no country-specific targets, get global
			if (!$targets) {
				$targets = $wpdb->get_row(
					"SELECT * FROM {$table} WHERE country_id IS NULL",
					ARRAY_A
				);
				if ($targets) {
					$targets['is_global_fallback'] = true;
				}
			}
		} else {
			// Get global targets
			$targets = $wpdb->get_row(
				"SELECT * FROM {$table} WHERE country_id IS NULL",
				ARRAY_A
			);
		}

		if (!$targets) {
			// Return default hardcoded values if nothing in DB
			$targets = array(
				'lead_to_response_target' => 30.0,
				'response_to_qualified_target' => 25.0,
				'qualified_to_converted_target' => 40.0,
				'overall_target' => 5.0,
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $targets,
			)
		);
	}

	/**
	 * Save (create or update) conversion targets.
	 */
	public function save_targets($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'aq_conversion_targets';
		$params = $request->get_params();

		$country_id = isset($params['country_id']) ? absint($params['country_id']) : null;

		// Check permission: admins can set global, country managers can set for their countries
		if ($country_id !== null && !$this->can_manage_country($country_id)) {
			return new WP_Error(
				'forbidden',
				__('You do not have permission to set targets for this country.', 'aqop-leads'),
				array('status' => 403)
			);
		}

		$data = array(
			'lead_to_response_target' => isset($params['lead_to_response_target']) ? floatval($params['lead_to_response_target']) : 30.0,
			'response_to_qualified_target' => isset($params['response_to_qualified_target']) ? floatval($params['response_to_qualified_target']) : 25.0,
			'qualified_to_converted_target' => isset($params['qualified_to_converted_target']) ? floatval($params['qualified_to_converted_target']) : 40.0,
			'overall_target' => isset($params['overall_target']) ? floatval($params['overall_target']) : 5.0,
			'created_by' => get_current_user_id(),
		);

		// Check if targets already exist for this country
		$existing = $wpdb->get_var(
			$country_id !== null
				? $wpdb->prepare("SELECT id FROM {$table} WHERE country_id = %d", $country_id)
				: "SELECT id FROM {$table} WHERE country_id IS NULL"
		);

		if ($existing) {
			// Update
			$wpdb->update(
				$table,
				$data,
				$country_id !== null ? array('country_id' => $country_id) : array('country_id' => null),
				array('%f', '%f', '%f', '%f', '%d'),
				array('%d')
			);
		} else {
			// Insert
			$data['country_id'] = $country_id;
			$wpdb->insert(
				$table,
				$data,
				array('%d', '%f', '%f', '%f', '%f', '%d')
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __('Targets saved successfully.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Delete country-specific targets (revert to global).
	 */
	public function delete_targets($request)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'aq_conversion_targets';
		$country_id = absint($request['country_id']);

		if (!$this->can_manage_country($country_id)) {
			return new WP_Error(
				'forbidden',
				__('You do not have permission to delete targets for this country.', 'aqop-leads'),
				array('status' => 403)
			);
		}

		$wpdb->delete($table, array('country_id' => $country_id), array('%d'));

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __('Country-specific targets deleted. Global targets will be used.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Check read permission (all authenticated users).
	 */
	public function check_read_permission()
	{
		return is_user_logged_in();
	}

	/**
	 * Check write permission.
	 * 
	 * Admins can edit global + all countries.
	 * Country managers can edit their assigned countries only.
	 */
	public function check_write_permission()
	{
		if (!is_user_logged_in()) {
			return new WP_Error('rest_forbidden', __('You must be logged in.', 'aqop-leads'), array('status' => 401));
		}

		// Admins can always edit
		if (current_user_can('manage_options')) {
			return true;
		}

		// Country managers can edit their countries
		if (current_user_can('manage_country_leads')) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__('You do not have permission to manage targets.', 'aqop-leads'),
			array('status' => 403)
		);
	}

	/**
	 * Check if user can manage a specific country.
	 */
	private function can_manage_country($country_id)
	{
		// Admins can manage any country
		if (current_user_can('manage_options')) {
			return true;
		}

		// Country managers can manage their assigned countries
		if (current_user_can('manage_country_leads')) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
			$user_countries = AQOP_Leads_Users_API::get_user_countries(get_current_user_id());
			return in_array($country_id, $user_countries, true);
		}

		return false;
	}

	/**
	 * Get schema for validation.
	 */
	private function get_schema()
	{
		return array(
			'country_id' => array(
				'type' => array('integer', 'null'),
				'sanitize_callback' => 'absint',
			),
			'lead_to_response_target' => array(
				'type' => 'number',
				'minimum' => 0,
				'maximum' => 100,
			),
			'response_to_qualified_target' => array(
				'type' => 'number',
				'minimum' => 0,
				'maximum' => 100,
			),
			'qualified_to_converted_target' => array(
				'type' => 'number',
				'minimum' => 0,
				'maximum' => 100,
			),
			'overall_target' => array(
				'type' => 'number',
				'minimum' => 0,
				'maximum' => 100,
			),
		);
	}
}
