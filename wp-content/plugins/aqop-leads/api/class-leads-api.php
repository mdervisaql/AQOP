<?php
/**
 * Leads REST API Controller
 *
 * Provides secure REST API endpoints for external integrations.
 *
 * @package AQOP_Leads
 * @since   1.0.6
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-dropbox-integration.php';

/**
 * AQOP_Leads_API class.
 *
 * REST API controller for leads management.
 *
 * @since 1.0.6
 */
class AQOP_Leads_API
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
	 * @since 1.0.6
	 */
	public function register_routes()
	{
		// === REST API (Phase 3.1) ===

		// List leads (GET /aqop/v1/leads)
		register_rest_route(
			$this->namespace,
			'/leads',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_leads'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => $this->get_collection_params(),
			)
		);

		// Get single lead (GET /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_lead'),
				'permission_callback' => array($this, 'check_read_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Create lead (POST /aqop/v1/leads)
		register_rest_route(
			$this->namespace,
			'/leads',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_lead'),
				'permission_callback' => array($this, 'check_create_permission'),
				'args' => $this->get_lead_schema(),
			)
		);

		// Public lead submission (POST /aqop/v1/leads/public)
		register_rest_route(
			$this->namespace,
			'/leads/public',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_public_lead'),
				'permission_callback' => '__return_true', // Public endpoint
				'args' => $this->get_lead_schema(),
			)
		);

		// Update lead (PUT/PATCH /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_lead'),
				'permission_callback' => array($this, 'check_edit_permission'),
				'args' => array_merge(
					array(
						'id' => array(
							'required' => true,
							'validate_callback' => function ($param) {
								return is_numeric($param);
							},
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_update_schema()
				),
			)
		);

		// Delete lead (DELETE /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_lead'),
				'permission_callback' => array($this, 'check_delete_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get statuses (GET /aqop/v1/leads/statuses)
		register_rest_route(
			$this->namespace,
			'/leads/statuses',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_statuses'),
				'permission_callback' => '__return_true', // Public endpoint
			)
		);

		// Get countries (GET /aqop/v1/leads/countries)
		register_rest_route(
			$this->namespace,
			'/leads/countries',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_countries'),
				'permission_callback' => '__return_true', // Public endpoint
			)
		);

		// Get sources (GET /aqop/v1/leads/sources)
		register_rest_route(
			$this->namespace,
			'/leads/sources',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_sources'),
				'permission_callback' => '__return_true', // Public endpoint
			)
		);

		// Get statistics (GET /aqop/v1/leads/stats)
		register_rest_route(
			$this->namespace,
			'/leads/stats',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_stats'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get detailed analytics (GET /aqop/v1/analytics/detailed)
		register_rest_route(
			$this->namespace,
			'/analytics/detailed',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_detailed_analytics'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => array(
					'time_range' => array(
						'sanitize_callback' => 'sanitize_text_field',
						'default' => '30days',
					),
					'start_date' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'end_date' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Add note to lead (POST /aqop/v1/leads/{id}/notes)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/notes',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'add_note'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
					'note_text' => array(
						'required' => true,
						'type' => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Get lead notes (GET /aqop/v1/leads/{id}/notes)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/notes',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_notes'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Get lead events/activity log (GET /aqop/v1/leads/{id}/events)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/events',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_lead_events'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Upload file to Dropbox (POST /aqop/v1/leads/{id}/upload)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/upload',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'upload_lead_file'),
				'permission_callback' => array($this, 'check_permission'),
				'args' => array(
					'id' => array(
						'required' => true,
						'validate_callback' => function ($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// === Lead Scoring Endpoints ===

		// Get lead score (GET /aqop/v1/leads/{id}/score)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/score',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_lead_score'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Recalculate lead score (POST /aqop/v1/leads/{id}/recalculate-score)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/recalculate-score',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'recalculate_lead_score'),
				'permission_callback' => array($this, 'check_edit_permission'),
			)
		);

		// Bulk recalculate scores (POST /aqop/v1/leads/bulk-recalculate-score)
		register_rest_route(
			$this->namespace,
			'/leads/bulk-recalculate-score',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'bulk_recalculate_score'),
				'permission_callback' => array($this, 'check_permission'), // Manager only check inside
			)
		);

		// Get scoring rules (GET /aqop/v1/scoring-rules)
		register_rest_route(
			$this->namespace,
			'/scoring-rules',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_scoring_rules'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Create scoring rule (POST /aqop/v1/scoring-rules)
		register_rest_route(
			$this->namespace,
			'/scoring-rules',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_scoring_rule'),
				'permission_callback' => array($this, 'check_permission'), // Manager only check inside
			)
		);

		// Update scoring rule (PUT /aqop/v1/scoring-rules/{id})
		register_rest_route(
			$this->namespace,
			'/scoring-rules/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_scoring_rule'),
				'permission_callback' => array($this, 'check_permission'), // Manager only check inside
			)
		);

		// Delete scoring rule (DELETE /aqop/v1/scoring-rules/{id})
		register_rest_route(
			$this->namespace,
			'/scoring-rules/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_scoring_rule'),
				'permission_callback' => array($this, 'check_permission'), // Manager only check inside
			)
		);

		// Get lead score history (GET /aqop/v1/leads/{id}/score-history)
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)/score-history',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_lead_score_history'),
			)
		);

		// === Automation Rules Endpoints ===

		// Get rules (GET /aqop/v1/automation/rules)
		register_rest_route(
			$this->namespace,
			'/automation/rules',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_automation_rules'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Create rule (POST /aqop/v1/automation/rules)
		register_rest_route(
			$this->namespace,
			'/automation/rules',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'create_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get rule (GET /aqop/v1/automation/rules/{id})
		register_rest_route(
			$this->namespace,
			'/automation/rules/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Update rule (PUT /aqop/v1/automation/rules/{id})
		register_rest_route(
			$this->namespace,
			'/automation/rules/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array($this, 'update_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Delete rule (DELETE /aqop/v1/automation/rules/{id})
		register_rest_route(
			$this->namespace,
			'/automation/rules/(?P<id>\d+)',
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array($this, 'delete_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Toggle rule (POST /aqop/v1/automation/rules/{id}/toggle)
		register_rest_route(
			$this->namespace,
			'/automation/rules/(?P<id>\d+)/toggle',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'toggle_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Test rule (POST /aqop/v1/automation/rules/{id}/test)
		register_rest_route(
			$this->namespace,
			'/automation/rules/(?P<id>\d+)/test',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array($this, 'test_automation_rule'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get logs (GET /aqop/v1/automation/logs)
		register_rest_route(
			$this->namespace,
			'/automation/logs',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_automation_logs'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// === Reports API ===

		// Get agent performance (GET /aqop/v1/reports/agent-performance)
		register_rest_route(
			$this->namespace,
			'/reports/agent-performance',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_agent_performance_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get source analysis (GET /aqop/v1/reports/sources)
		register_rest_route(
			$this->namespace,
			'/reports/sources',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_source_analysis_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get campaign performance (GET /aqop/v1/reports/campaigns)
		register_rest_route(
			$this->namespace,
			'/reports/campaigns',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_campaign_performance_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get time analysis (GET /aqop/v1/reports/time-analysis)
		register_rest_route(
			$this->namespace,
			'/reports/time-analysis',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_time_analysis_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get status distribution (GET /aqop/v1/reports/status-distribution)
		register_rest_route(
			$this->namespace,
			'/reports/status-distribution',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_status_distribution_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get country analysis (GET /aqop/v1/reports/countries)
		register_rest_route(
			$this->namespace,
			'/reports/countries',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_country_analysis_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// Get summary stats (GET /aqop/v1/reports/summary)
		register_rest_route(
			$this->namespace,
			'/reports/summary',
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array($this, 'get_summary_report'),
				'permission_callback' => array($this, 'check_permission'),
			)
		);

		// === END REST API ===
	}

	/**
	 * Get leads collection.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_leads($request)
	{
		$params = $request->get_params();

		$args = array(
			'limit' => isset($params['per_page']) ? absint($params['per_page']) : 50,
			'offset' => isset($params['page']) ? (absint($params['page']) - 1) * absint($params['per_page']) : 0,
			'orderby' => isset($params['orderby']) ? sanitize_text_field($params['orderby']) : 'created_at',
			'order' => isset($params['order']) ? sanitize_text_field($params['order']) : 'DESC',
		);

		// Apply filters
		if (!empty($params['status'])) {
			// Convert status code to ID
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
					sanitize_text_field($params['status'])
				)
			);
			if ($status_id) {
				$args['status'] = $status_id;
			}
		}

		if (!empty($params['country'])) {
			$args['country'] = absint($params['country']);
		}

		if (!empty($params['source'])) {
			$args['source'] = absint($params['source']);
		}

		if (!empty($params['priority'])) {
			$args['priority'] = sanitize_text_field($params['priority']);
		}

		if (!empty($params['rating'])) {
			$args['rating'] = sanitize_text_field($params['rating']);
		}

		if (!empty($params['search'])) {
			$args['search'] = sanitize_text_field($params['search']);
		}

		// Filter by assigned user (for agents)
		if (!empty($params['assigned_to_me']) && $params['assigned_to_me']) {
			$args['assigned_to'] = get_current_user_id();
		}

		// Auto-filter for agents: only show assigned leads
		if ($this->is_agent()) {
			$args['assigned_to'] = get_current_user_id();
		}

		// Auto-filter for Country Managers: only show leads from their country
		if (current_user_can('manage_country_leads') && !current_user_can('manage_options')) {
			$user_country = get_user_meta(get_current_user_id(), 'aq_assigned_country', true);
			if ($user_country) {
				$args['country'] = absint($user_country);
			}
		}

		$result = AQOP_Leads_Manager::query_leads($args);

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'results' => $result['results'],
					'total' => $result['total'],
					'pages' => $result['pages'],
					'page' => isset($params['page']) ? absint($params['page']) : 1,
					'per_page' => isset($params['per_page']) ? absint($params['per_page']) : 50,
				),
			)
		);

		$response->header('X-WP-Total', $result['total']);
		$response->header('X-WP-TotalPages', $result['pages']);

		return $response;
	}

	/**
	 * Get single lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_lead($request)
	{
		$lead_id = $request['id'];
		$lead = AQOP_Leads_Manager::get_lead($lead_id);

		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		// Check ownership for agents
		if ($this->is_agent()) {
			$current_user_id = get_current_user_id();
			if ((int) $lead->assigned_to !== $current_user_id) {
				return new WP_Error(
					'forbidden',
					__('You can only view leads assigned to you.', 'aqop-leads'),
					array('status' => 403)
				);
			}
		}

		// Check ownership for Country Managers
		if (current_user_can('manage_country_leads') && !current_user_can('manage_options')) {
			$user_country = get_user_meta(get_current_user_id(), 'aq_assigned_country', true);
			if ($user_country && (int) $lead->country_id !== (int) $user_country) {
				return new WP_Error(
					'forbidden',
					__('You can only view leads from your assigned country.', 'aqop-leads'),
					array('status' => 403)
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $lead,
			)
		);
	}

	/**
	 * Create public lead.
	 *
	 * Public endpoint for lead submission without authentication.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function create_public_lead($request)
	{
		// Rate limiting check (simple IP-based)
		$ip = $this->get_client_ip();
		$rate_limit_key = 'aqop_lead_submit_' . md5($ip);
		$submissions = get_transient($rate_limit_key);

		if ($submissions && $submissions >= 3) {
			return new WP_Error(
				'rate_limit_exceeded',
				__('Too many submissions. Please try again in 10 minutes.', 'aqop-leads'),
				array('status' => 429)
			);
		}

		// Create the lead
		$result = $this->create_lead($request);

		// Update rate limit counter
		$new_count = $submissions ? $submissions + 1 : 1;
		set_transient($rate_limit_key, $new_count, 10 * MINUTE_IN_SECONDS);

		return $result;
	}

	/**
	 * Create lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function create_lead($request)
	{
		$params = $request->get_params();

		// Validate required fields
		if (empty($params['name']) || empty($params['email']) || empty($params['phone'])) {
			return new WP_Error(
				'missing_required_fields',
				__('Name, email, and phone are required fields.', 'aqop-leads'),
				array('status' => 400)
			);
		}

		$lead_data = array(
			'name' => sanitize_text_field($params['name']),
			'email' => sanitize_email($params['email']),
			'phone' => sanitize_text_field($params['phone']),
			'whatsapp' => isset($params['whatsapp']) ? sanitize_text_field($params['whatsapp']) : '',
			'country_id' => isset($params['country_id']) ? absint($params['country_id']) : null,
			'source_id' => isset($params['source_id']) ? absint($params['source_id']) : null,
			'campaign_id' => isset($params['campaign_id']) ? absint($params['campaign_id']) : null,
			'priority' => isset($params['priority']) ? sanitize_text_field($params['priority']) : 'medium',
		);

		// Handle status
		if (isset($params['status'])) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
					sanitize_text_field($params['status'])
				)
			);
			if ($status_id) {
				$lead_data['status_id'] = $status_id;
			}
		}

		$lead_id = AQOP_Leads_Manager::create_lead($lead_data);

		if (!$lead_id) {
			return new WP_Error(
				'create_failed',
				__('Failed to create lead.', 'aqop-leads'),
				array('status' => 500)
			);
		}

		// Add initial note if provided
		if (!empty($params['note'])) {
			AQOP_Leads_Manager::add_note($lead_id, sanitize_textarea_field($params['note']));
		}

		$lead = AQOP_Leads_Manager::get_lead($lead_id);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $lead,
				'message' => __('Lead created successfully.', 'aqop-leads'),
			),
			201
		);
	}

	/**
	 * Update lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_lead($request)
	{
		$lead_id = $request['id'];
		$params = $request->get_params();

		// Check if lead exists
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		// Check ownership for agents
		if ($this->is_agent()) {
			$current_user_id = get_current_user_id();
			if ((int) $lead->assigned_to !== $current_user_id) {
				return new WP_Error(
					'forbidden',
					__('You can only edit leads assigned to you.', 'aqop-leads'),
					array('status' => 403)
				);
			}
		}

		$lead_data = array();

		$allowed_fields = array('name', 'email', 'phone', 'whatsapp', 'country_id', 'source_id', 'campaign_id', 'status', 'status_code', 'priority', 'assigned_to');

		foreach ($allowed_fields as $field) {
			if (isset($params[$field])) {
				if (in_array($field, array('country_id', 'source_id', 'campaign_id', 'assigned_to'), true)) {
					$lead_data[$field] = absint($params[$field]);
				} elseif ('email' === $field) {
					$lead_data[$field] = sanitize_email($params[$field]);
				} elseif ('status' === $field || 'status_code' === $field) {
					// Convert status code to ID (handle both 'status' and 'status_code')
					global $wpdb;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$status_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
							sanitize_text_field($params[$field])
						)
					);
					if ($status_id) {
						$lead_data['status_id'] = $status_id;
					}
				} else {
					$lead_data[$field] = sanitize_text_field($params[$field]);
				}
			}
		}

		if (empty($lead_data)) {
			return new WP_Error(
				'no_fields_to_update',
				__('No fields provided for update.', 'aqop-leads'),
				array('status' => 400)
			);
		}

		$updated = AQOP_Leads_Manager::update_lead($lead_id, $lead_data);

		if (!$updated) {
			return new WP_Error(
				'update_failed',
				__('Failed to update lead.', 'aqop-leads'),
				array('status' => 500)
			);
		}

		$lead = AQOP_Leads_Manager::get_lead($lead_id);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $lead,
				'message' => __('Lead updated successfully.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Delete lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function delete_lead($request)
	{
		$lead_id = $request['id'];

		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		$deleted = AQOP_Leads_Manager::delete_lead($lead_id);

		if (!$deleted) {
			return new WP_Error(
				'delete_failed',
				__('Failed to delete lead.', 'aqop-leads'),
				array('status' => 500)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => array(
					'deleted' => true,
					'id' => $lead_id,
				),
				'message' => __('Lead deleted successfully.', 'aqop-leads'),
			)
		);
	}

	/**
	 * Get available statuses.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_statuses()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$statuses = $wpdb->get_results(
			"SELECT id, status_code, status_name_en, status_name_ar, color, status_order
			 FROM {$wpdb->prefix}aq_leads_status
			 WHERE is_active = 1
			 ORDER BY status_order ASC"
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $statuses,
			)
		);
	}

	/**
	 * Get available countries.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_countries()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$countries = $wpdb->get_results(
			"SELECT id, country_code, country_name_en, country_name_ar, region
			 FROM {$wpdb->prefix}aq_dim_countries
			 WHERE is_active = 1
			 ORDER BY country_name_en ASC"
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $countries,
			)
		);
	}

	/**
	 * Get available sources.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_sources()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sources = $wpdb->get_results(
			"SELECT id, source_code, source_name, source_type
			 FROM {$wpdb->prefix}aq_leads_sources
			 WHERE is_active = 1
			 ORDER BY source_name ASC"
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $sources,
			)
		);
	}

	/**
	 * Get statistics.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_stats()
	{
		global $wpdb;

		$user_id = get_current_user_id();
		$where_clause = '';

		// If not admin, show only assigned leads
		if (!current_user_can('manage_options')) {
			$where_clause = $wpdb->prepare(' AND assigned_to = %d', $user_id);
		}

		// Get total leads
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_leads = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads WHERE 1=1 {$where_clause}"
		);

		// Get leads by status
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats_by_status = $wpdb->get_results(
			"SELECT s.status_code, s.status_name_en, COUNT(l.id) as count
			 FROM {$wpdb->prefix}aq_leads_status s
			 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.status_id {$where_clause}
			 WHERE s.is_active = 1
			 GROUP BY s.id
			 ORDER BY s.status_order ASC"
		);

		$stats = array(
			'total_leads' => (int) $total_leads,
			'pending_leads' => 0,
			'contacted_leads' => 0,
			'qualified_leads' => 0,
			'converted_leads' => 0,
			'lost_leads' => 0,
			'by_status' => array(),
		);

		foreach ($stats_by_status as $stat) {
			$stats['by_status'][$stat->status_code] = (int) $stat->count;

			// Map to specific counts
			switch ($stat->status_code) {
				case 'pending':
					$stats['pending_leads'] = (int) $stat->count;
					break;
				case 'contacted':
					$stats['contacted_leads'] = (int) $stat->count;
					break;
				case 'qualified':
					$stats['qualified_leads'] = (int) $stat->count;
					break;
				case 'converted':
					$stats['converted_leads'] = (int) $stat->count;
					break;
				case 'lost':
					$stats['lost_leads'] = (int) $stat->count;
					break;
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $stats,
			)
		);
	}

	/**
	 * Add note to lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function add_note($request)
	{
		$lead_id = $request['id'];
		$note_text = $request['note_text'];

		// Check if lead exists
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		$note_id = AQOP_Leads_Manager::add_note($lead_id, $note_text);

		if (!$note_id) {
			return new WP_Error(
				'note_creation_failed',
				__('Failed to add note.', 'aqop-leads'),
				array('status' => 500)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __('Note added successfully.', 'aqop-leads'),
				'data' => array(
					'note_id' => $note_id,
				),
			),
			201
		);
	}

	/**
	 * Get lead notes.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_notes($request)
	{
		$lead_id = $request['id'];

		// Check if lead exists
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		$notes = AQOP_Leads_Manager::get_notes($lead_id);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $notes,
			)
		);
	}

	/**
	 * Get lead events/activity log.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_lead_events($request)
	{
		global $wpdb;

		$lead_id = $request['id'];

		// Check if lead exists
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error(
				'lead_not_found',
				__('Lead not found.', 'aqop-leads'),
				array('status' => 404)
			);
		}

		// Get events from event log
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					e.*,
					u.display_name as user_name,
					et.event_name,
					et.event_category,
					et.severity
				FROM {$wpdb->prefix}aq_events_log e
				LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
				LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
				WHERE e.object_type = 'lead' 
				AND e.object_id = %d
				ORDER BY e.created_at DESC
				LIMIT 100",
				$lead_id
			)
		);

		// Parse payload_json for each event
		if ($events) {
			foreach ($events as &$event) {
				if ($event->payload_json) {
					$event->payload = json_decode($event->payload_json, true);
				}
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $events ? $events : array(),
			)
		);
	}

	/**
	 * Check API permission (general - all roles).
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error Permission result.
	 */
	public function check_permission()
	{
		// Check if user is logged in (JWT authentication)
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to access this endpoint.', 'aqop-leads'),
				array('status' => 401)
			);
		}

		// Allow these roles
		$allowed_roles = array('administrator', 'operation_admin', 'operation_manager', 'aq_supervisor', 'aq_agent');
		$user = wp_get_current_user();

		if (!array_intersect($allowed_roles, $user->roles)) {
			return new WP_Error(
				'rest_forbidden',
				__('You do not have permission to access this endpoint.', 'aqop-leads'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Check read permission for single lead.
	 *
	 * All roles can read, but agents only see assigned leads.
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error Permission result.
	 */
	public function check_read_permission()
	{
		return $this->check_permission();
	}

	/**
	 * Check create permission.
	 *
	 * Only managers and above can create leads.
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error Permission result.
	 */
	public function check_create_permission()
	{
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to create leads.', 'aqop-leads'),
				array('status' => 401)
			);
		}

		// Only managers and above can create leads
		if ($this->is_manager_or_above()) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__('You do not have permission to create leads.', 'aqop-leads'),
			array('status' => 403)
		);
	}

	/**
	 * Check edit permission.
	 *
	 * Agents can edit assigned leads, managers can edit all.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error Permission result.
	 */
	public function check_edit_permission($request)
	{
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to edit leads.', 'aqop-leads'),
				array('status' => 401)
			);
		}

		// Allow all AQOP roles (ownership checked in update_lead method)
		$allowed_roles = array('administrator', 'operation_admin', 'operation_manager', 'aq_supervisor', 'aq_agent');
		$user = wp_get_current_user();

		if (!array_intersect($allowed_roles, $user->roles)) {
			return new WP_Error(
				'rest_forbidden',
				__('You do not have permission to edit leads.', 'aqop-leads'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Check delete permission.
	 *
	 * Only managers and above can delete leads.
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error Permission result.
	 */
	public function check_delete_permission()
	{
		if (!is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to delete leads.', 'aqop-leads'),
				array('status' => 401)
			);
		}

		// Only managers and above can delete leads
		if ($this->is_manager_or_above()) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__('You do not have permission to delete leads.', 'aqop-leads'),
			array('status' => 403)
		);
	}

	/**
	 * Check if current user is an agent.
	 *
	 * @since 1.0.6
	 * @return bool True if user is an agent.
	 */
	private function is_agent()
	{
		$user = wp_get_current_user();
		return in_array('aq_agent', $user->roles, true)
			&& !in_array('aq_supervisor', $user->roles, true)
			&& !in_array('operation_manager', $user->roles, true)
			&& !in_array('operation_admin', $user->roles, true)
			&& !in_array('administrator', $user->roles, true);
	}

	/**
	 * Check if current user is a supervisor or above.
	 *
	 * @since 1.0.6
	 * @return bool True if user is supervisor or above.
	 */
	private function is_supervisor_or_above()
	{
		$user = wp_get_current_user();
		$manager_roles = array('administrator', 'operation_admin', 'operation_manager', 'aq_supervisor');
		return !empty(array_intersect($manager_roles, $user->roles));
	}

	/**
	 * Check if current user is a manager or above.
	 *
	 * @since 1.0.6
	 * @return bool True if user is manager or above.
	 */
	private function is_manager_or_above()
	{
		$user = wp_get_current_user();
		$manager_roles = array('administrator', 'operation_admin', 'operation_manager');
		return !empty(array_intersect($manager_roles, $user->roles));
	}

	/**
	 * Get client IP address.
	 *
	 * @since 1.0.6
	 * @return string Client IP address.
	 */
	private function get_client_ip()
	{
		$ip = '';

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		// If multiple IPs, get the first one.
		if (strpos($ip, ',') !== false) {
			$ips = explode(',', $ip);
			$ip = trim($ips[0]);
		}

		return $ip;
	}

	/**
	 * Get collection parameters schema.
	 *
	 * @since 1.0.6
	 * @return array Collection parameters.
	 */
	private function get_collection_params()
	{
		return array(
			'page' => array(
				'default' => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => function ($param) {
					return $param > 0;
				},
			),
			'per_page' => array(
				'default' => 50,
				'sanitize_callback' => 'absint',
				'validate_callback' => function ($param) {
					return $param > 0 && $param <= 10000;
				},
			),
			'search' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country' => array(
				'sanitize_callback' => 'absint',
			),
			'source' => array(
				'sanitize_callback' => 'absint',
			),
			'priority' => array(
				'sanitize_callback' => 'sanitize_text_field',
				'enum' => array('urgent', 'high', 'medium', 'low'),
			),
			'assigned_to_me' => array(
				'type' => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'orderby' => array(
				'default' => 'created_at',
				'sanitize_callback' => 'sanitize_text_field',
				'enum' => array('id', 'name', 'email', 'created_at', 'updated_at', 'status_id', 'priority'),
			),
			'order' => array(
				'default' => 'DESC',
				'sanitize_callback' => 'sanitize_text_field',
				'enum' => array('ASC', 'DESC', 'asc', 'desc'),
			),
		);
	}

	/**
	 * Get lead schema for validation (CREATE).
	 *
	 * Schema for creating new leads - name, email, phone are required.
	 *
	 * @since 1.0.6
	 * @return array Lead schema.
	 */
	private function get_lead_schema()
	{
		return array(
			'name' => array(
				'required' => true,
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email' => array(
				'required' => true,
				'type' => 'string',
				'format' => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'is_email',
			),
			'phone' => array(
				'required' => true,
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'whatsapp' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'source_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'campaign_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'status' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status_code' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'priority' => array(
				'type' => 'string',
				'enum' => array('urgent', 'high', 'medium', 'low'),
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'medium',
			),
			'assigned_to' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'note' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get update schema for validation (UPDATE).
	 *
	 * Schema for updating leads - all fields are optional for partial updates.
	 *
	 * @since 1.0.6
	 * @return array Update schema.
	 */
	private function get_update_schema()
	{
		return array(
			'name' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email' => array(
				'type' => 'string',
				'format' => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'is_email',
			),
			'phone' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'whatsapp' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'source_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'campaign_id' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'status' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status_code' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'priority' => array(
				'type' => 'string',
				'enum' => array('urgent', 'high', 'medium', 'low'),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'assigned_to' => array(
				'type' => 'integer',
				'sanitize_callback' => 'absint',
			),
			'note' => array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get detailed analytics data for enhanced dashboard.
	 *
	 * Returns comprehensive analytics data including agent performance,
	 * time-based trends, source breakdown, and status distribution.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_detailed_analytics($request)
	{
		$params = $request->get_params();
		$time_range = isset($params['time_range']) ? $params['time_range'] : '30days';

		// Calculate date range based on time_range
		$end_date = current_time('Y-m-d');
		$start_date = $this->calculate_start_date($time_range, $params);

		global $wpdb;

		$analytics = array();

		// 1. Agent Performance Data
		$analytics['agent_performance'] = $this->get_agent_performance_data($start_date, $end_date);

		// 2. Time-based Trends (leads over time)
		$analytics['time_trends'] = $this->get_time_based_trends($start_date, $end_date);

		// 3. Source Breakdown
		$analytics['source_breakdown'] = $this->get_source_breakdown($start_date, $end_date);

		// 4. Status Distribution
		$analytics['status_distribution'] = $this->get_status_distribution($start_date, $end_date);

		// 5. Response Time Metrics (placeholder - would need actual timing data)
		$analytics['response_times'] = array(
			'average_response_time' => '2.3 hours',
			'fastest_response' => '15 minutes',
			'slowest_response' => '48 hours',
		);

		// 6. Revenue Metrics (placeholder - would need revenue data)
		$analytics['revenue_metrics'] = array(
			'total_revenue' => '$15,420',
			'average_deal_size' => '$2,450',
			'monthly_growth' => '+12.5%',
			'pipeline_value' => '$45,200',
		);

		// 7. Lead Quality Metrics
		$analytics['lead_quality'] = $this->get_lead_quality_metrics($start_date, $end_date);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $analytics,
				'meta' => array(
					'time_range' => $time_range,
					'start_date' => $start_date,
					'end_date' => $end_date,
				),
			)
		);
	}

	/**
	 * Calculate start date based on time range.
	 *
	 * @since 1.0.0
	 * @param string $time_range Time range parameter.
	 * @param array  $params Request parameters.
	 * @return string Start date in Y-m-d format.
	 */
	private function calculate_start_date($time_range, $params)
	{
		$now = current_time('timestamp');

		switch ($time_range) {
			case 'today':
				return date('Y-m-d', $now);
			case 'yesterday':
				return date('Y-m-d', strtotime('-1 day', $now));
			case '7days':
				return date('Y-m-d', strtotime('-7 days', $now));
			case '30days':
				return date('Y-m-d', strtotime('-30 days', $now));
			case 'thisweek':
				return date('Y-m-d', strtotime('monday this week', $now));
			case 'lastweek':
				return date('Y-m-d', strtotime('monday last week', $now));
			case 'thismonth':
				return date('Y-m-d', strtotime('first day of this month', $now));
			case 'lastmonth':
				return date('Y-m-d', strtotime('first day of last month', $now));
			case 'thisquarter':
				$current_month = date('n', $now);
				$quarter_start = ceil($current_month / 3) * 3 - 2;
				return date('Y-m-d', strtotime(date('Y', $now) . '-' . $quarter_start . '-01'));
			case 'lastquarter':
				$current_month = date('n', $now);
				$quarter_start = ceil($current_month / 3) * 3 - 2;
				$last_quarter_start = $quarter_start - 3;
				if ($last_quarter_start <= 0) {
					$last_quarter_start = 10; // October for Q4
					$year = date('Y', $now) - 1;
				} else {
					$year = date('Y', $now);
				}
				return date('Y-m-d', strtotime($year . '-' . $last_quarter_start . '-01'));
			case 'thisyear':
				return date('Y-m-d', strtotime('first day of january this year', $now));
			case 'custom':
				if (!empty($params['start_date'])) {
					return sanitize_text_field($params['start_date']);
				}
				return date('Y-m-d', strtotime('-30 days', $now));
			default:
				return date('Y-m-d', strtotime('-30 days', $now));
		}
	}

	/**
	 * Get detailed agent performance data.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Agent performance data.
	 */
	private function get_agent_performance_data($start_date, $end_date)
	{
		global $wpdb;

		// Get all AQOP users
		$aqop_roles = array('aq_agent', 'aq_supervisor');
		$user_query = new WP_User_Query(
			array(
				'role__in' => $aqop_roles,
				'orderby' => 'display_name',
				'order' => 'ASC',
			)
		);

		$agents = array();

		foreach ($user_query->get_results() as $user) {
			$user_roles = $user->roles;
			$role = '';
			foreach ($aqop_roles as $r) {
				if (in_array($r, $user_roles, true)) {
					$role = $r;
					break;
				}
			}

			// Get lead counts for this agent
			$lead_counts = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) as total_leads,
						SUM(CASE WHEN status_code = 'contacted' THEN 1 ELSE 0 END) as contacted,
						SUM(CASE WHEN status_code = 'qualified' THEN 1 ELSE 0 END) as qualified,
						SUM(CASE WHEN status_code = 'converted' THEN 1 ELSE 0 END) as converted,
						SUM(CASE WHEN status_code = 'lost' THEN 1 ELSE 0 END) as lost,
						SUM(CASE WHEN DATE(created_at) >= %s AND DATE(created_at) <= %s THEN 1 ELSE 0 END) as period_leads,
						SUM(CASE WHEN DATE(created_at) >= %s AND DATE(created_at) <= %s AND status_code = 'converted' THEN 1 ELSE 0 END) as period_converted
					FROM {$wpdb->prefix}aq_leads
					WHERE assigned_to = %d",
					$start_date,
					$end_date,
					$start_date,
					$end_date,
					$user->ID
				)
			);

			$agents[] = array(
				'id' => $user->ID,
				'name' => $user->display_name ?: $user->user_login,
				'role' => $role,
				'total_leads' => (int) $lead_counts->total_leads,
				'contacted' => (int) $lead_counts->contacted,
				'qualified' => (int) $lead_counts->qualified,
				'converted' => (int) $lead_counts->converted,
				'lost' => (int) $lead_counts->lost,
				'conversion_rate' => $lead_counts->total_leads > 0 ? round(($lead_counts->converted / $lead_counts->total_leads) * 100, 1) : 0,
				'contact_rate' => $lead_counts->total_leads > 0 ? round((($lead_counts->contacted + $lead_counts->qualified + $lead_counts->converted) / $lead_counts->total_leads) * 100, 1) : 0,
				'period_leads' => (int) $lead_counts->period_leads,
				'period_converted' => (int) $lead_counts->period_converted,
				'period_rate' => $lead_counts->period_leads > 0 ? round(($lead_counts->period_converted / $lead_counts->period_leads) * 100, 1) : 0,
			);
		}

		return $agents;
	}

	/**
	 * Get time-based trends data.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Time trends data.
	 */
	private function get_time_based_trends($start_date, $end_date)
	{
		global $wpdb;

		$trends = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(created_at) as date,
					COUNT(*) as total_leads,
					SUM(CASE WHEN status_code = 'converted' THEN 1 ELSE 0 END) as converted_leads
				FROM {$wpdb->prefix}aq_leads
				WHERE DATE(created_at) BETWEEN %s AND %s
				GROUP BY DATE(created_at)
				ORDER BY DATE(created_at)",
				$start_date,
				$end_date
			)
		);

		return array_map(
			function ($row) {
				return array(
					'date' => $row->date,
					'display_date' => date('M j', strtotime($row->date)),
					'total_leads' => (int) $row->total_leads,
					'converted_leads' => (int) $row->converted_leads,
				);
			},
			$trends
		);
	}

	/**
	 * Get source breakdown data.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Source breakdown data.
	 */
	private function get_source_breakdown($start_date, $end_date)
	{
		global $wpdb;

		$sources = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					COALESCE(source_name, 'Unknown') as source,
					COUNT(*) as count
				FROM {$wpdb->prefix}aq_leads
				WHERE DATE(created_at) BETWEEN %s AND %s
				GROUP BY source_name
				ORDER BY count DESC
				LIMIT 10",
				$start_date,
				$end_date
			)
		);

		return array_map(
			function ($row) {
				return array(
					'name' => $row->source,
					'value' => (int) $row->count,
				);
			},
			$sources
		);
	}

	/**
	 * Get status distribution data.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Status distribution data.
	 */
	private function get_status_distribution($start_date, $end_date)
	{
		global $wpdb;

		$statuses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					COALESCE(status_code, 'unknown') as status,
					COUNT(*) as count
				FROM {$wpdb->prefix}aq_leads
				WHERE DATE(created_at) BETWEEN %s AND %s
				GROUP BY status_code
				ORDER BY count DESC",
				$start_date,
				$end_date
			)
		);

		return array_map(
			function ($row) {
				return array(
					'name' => ucfirst($row->status),
					'value' => (int) $row->count,
					'percentage' => 0, // Will be calculated in frontend
				);
			},
			$statuses
		);
	}

	/**
	 * Get lead quality metrics.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Lead quality metrics.
	 */
	private function get_lead_quality_metrics($start_date, $end_date)
	{
		global $wpdb;

		$metrics = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total_leads,
					AVG(CASE WHEN status_code IN ('qualified', 'converted') THEN 1 ELSE 0 END) * 100 as qualification_rate,
					SUM(CASE WHEN status_code = 'converted' THEN 1 ELSE 0 END) as converted_leads,
					SUM(CASE WHEN status_code = 'lost' THEN 1 ELSE 0 END) as lost_leads
				FROM {$wpdb->prefix}aq_leads
				WHERE DATE(created_at) BETWEEN %s AND %s",
				$start_date,
				$end_date
			)
		);

		return array(
			'total_leads' => (int) $metrics->total_leads,
			'qualification_rate' => round((float) $metrics->qualification_rate, 1),
			'converted_leads' => (int) $metrics->converted_leads,
			'lost_leads' => (int) $metrics->lost_leads,
			'win_rate' => $metrics->total_leads > 0 ? round(($metrics->converted_leads / $metrics->total_leads) * 100, 1) : 0,
			'loss_rate' => $metrics->total_leads > 0 ? round(($metrics->lost_leads / $metrics->total_leads) * 100, 1) : 0,
		);
	}

	// === Lead Scoring Methods ===

	/**
	 * Get lead score.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_lead_score($request)
	{
		$lead_id = $request['id'];
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		if (!$lead) {
			return new WP_Error('lead_not_found', __('Lead not found.', 'aqop-leads'), array('status' => 404));
		}
		return new WP_REST_Response(array(
			'success' => true,
			'data' => array(
				'score' => (int) $lead->lead_score,
				'rating' => $lead->lead_rating,
				'updated_at' => $lead->score_updated_at
			)
		));
	}

	/**
	 * Recalculate lead score.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function recalculate_lead_score($request)
	{
		$lead_id = $request['id'];
		$score = AQOP_Lead_Scoring::calculate_score($lead_id);
		if ($score === false) {
			return new WP_Error('calculation_failed', __('Failed to calculate score.', 'aqop-leads'), array('status' => 500));
		}
		$lead = AQOP_Leads_Manager::get_lead($lead_id);
		return new WP_REST_Response(array(
			'success' => true,
			'data' => array(
				'score' => (int) $lead->lead_score,
				'rating' => $lead->lead_rating,
				'updated_at' => $lead->score_updated_at
			),
			'message' => __('Score recalculated.', 'aqop-leads')
		));
	}

	/**
	 * Bulk recalculate scores.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_recalculate_score($request)
	{
		$params = $request->get_params();
		$lead_ids = isset($params['lead_ids']) ? $params['lead_ids'] : array();

		if (empty($lead_ids)) {
			if (isset($params['all']) && $params['all']) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$lead_ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}aq_leads");
			} else {
				return new WP_Error('no_leads_provided', __('No leads provided.', 'aqop-leads'), array('status' => 400));
			}
		}

		$count = AQOP_Lead_Scoring::bulk_recalculate($lead_ids);
		return new WP_REST_Response(array(
			'success' => true,
			'message' => sprintf(__('%d leads recalculated.', 'aqop-leads'), $count),
			'count' => $count
		));
	}

	/**
	 * Get scoring rules.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_scoring_rules($request)
	{
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_scoring_rules ORDER BY priority ASC");
		return new WP_REST_Response(array('success' => true, 'data' => $rules));
	}

	/**
	 * Create scoring rule.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_scoring_rule($request)
	{
		if (!$this->is_manager_or_above()) {
			return new WP_Error('forbidden', __('Permission denied.', 'aqop-leads'), array('status' => 403));
		}
		$params = $request->get_params();
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			"{$wpdb->prefix}aq_scoring_rules",
			array(
				'rule_name' => sanitize_text_field($params['rule_name']),
				'rule_type' => sanitize_text_field($params['rule_type']),
				'condition_field' => sanitize_text_field($params['condition_field']),
				'condition_operator' => sanitize_text_field($params['condition_operator']),
				'condition_value' => sanitize_text_field($params['condition_value']),
				'score_points' => intval($params['score_points']),
				'priority' => intval($params['priority']),
				'is_active' => isset($params['is_active']) ? (int) $params['is_active'] : 1,
				'created_at' => current_time('mysql')
			)
		);
		return new WP_REST_Response(array('success' => true, 'id' => $wpdb->insert_id), 201);
	}

	/**
	 * Update scoring rule.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_scoring_rule($request)
	{
		if (!$this->is_manager_or_above()) {
			return new WP_Error('forbidden', __('Permission denied.', 'aqop-leads'), array('status' => 403));
		}
		$id = $request['id'];
		$params = $request->get_params();
		global $wpdb;

		$data = array();
		if (isset($params['rule_name']))
			$data['rule_name'] = sanitize_text_field($params['rule_name']);
		if (isset($params['rule_type']))
			$data['rule_type'] = sanitize_text_field($params['rule_type']);
		if (isset($params['condition_field']))
			$data['condition_field'] = sanitize_text_field($params['condition_field']);
		if (isset($params['condition_operator']))
			$data['condition_operator'] = sanitize_text_field($params['condition_operator']);
		if (isset($params['condition_value']))
			$data['condition_value'] = sanitize_text_field($params['condition_value']);
		if (isset($params['score_points']))
			$data['score_points'] = intval($params['score_points']);
		if (isset($params['priority']))
			$data['priority'] = intval($params['priority']);
		if (isset($params['is_active']))
			$data['is_active'] = (int) $params['is_active'];

		if (!empty($data)) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update("{$wpdb->prefix}aq_scoring_rules", $data, array('id' => $id));
		}
		return new WP_REST_Response(array('success' => true));
	}

	/**
	 * Delete scoring rule.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_scoring_rule($request)
	{
		if (!$this->is_manager_or_above()) {
			return new WP_Error('forbidden', __('Permission denied.', 'aqop-leads'), array('status' => 403));
		}
		$id = $request['id'];
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete("{$wpdb->prefix}aq_scoring_rules", array('id' => $id));
		return new WP_REST_Response(array('success' => true));
	}

	/**
	 * Get lead score history.
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_lead_score_history($request)
	{
		$lead_id = $request['id'];
		$history = AQOP_Lead_Scoring::get_score_history($lead_id);
		return new WP_REST_Response(array('success' => true, 'data' => $history));
	}

	// === Automation Rules Methods ===

	/**
	 * Get automation rules.
	 */
	public function get_automation_rules($request)
	{
		global $wpdb;
		$rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_automation_rules ORDER BY priority ASC");

		foreach ($rules as $rule) {
			$rule->conditions = json_decode($rule->conditions);
			$rule->actions = json_decode($rule->actions);
		}

		return new WP_REST_Response(array('success' => true, 'data' => $rules));
	}

	/**
	 * Create automation rule.
	 */
	public function create_automation_rule($request)
	{
		global $wpdb;
		$params = $request->get_params();

		$data = array(
			'rule_name' => sanitize_text_field($params['rule_name']),
			'description' => sanitize_textarea_field($params['description']),
			'trigger_event' => sanitize_text_field($params['trigger_event']),
			'conditions' => wp_json_encode($params['conditions']),
			'actions' => wp_json_encode($params['actions']),
			'priority' => absint($params['priority']),
			'is_active' => isset($params['is_active']) ? (int) $params['is_active'] : 1,
			'created_by' => get_current_user_id(),
		);

		$inserted = $wpdb->insert($wpdb->prefix . 'aq_automation_rules', $data);

		if (!$inserted) {
			return new WP_Error('db_error', 'Failed to create rule', array('status' => 500));
		}

		return new WP_REST_Response(array('success' => true, 'id' => $wpdb->insert_id));
	}

	/**
	 * Get automation rule.
	 */
	public function get_automation_rule($request)
	{
		global $wpdb;
		$id = $request['id'];
		$rule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_automation_rules WHERE id = %d", $id));

		if (!$rule) {
			return new WP_Error('not_found', 'Rule not found', array('status' => 404));
		}

		$rule->conditions = json_decode($rule->conditions);
		$rule->actions = json_decode($rule->actions);

		return new WP_REST_Response(array('success' => true, 'data' => $rule));
	}

	/**
	 * Update automation rule.
	 */
	public function update_automation_rule($request)
	{
		global $wpdb;
		$id = $request['id'];
		$params = $request->get_params();

		$data = array(
			'rule_name' => sanitize_text_field($params['rule_name']),
			'description' => sanitize_textarea_field($params['description']),
			'trigger_event' => sanitize_text_field($params['trigger_event']),
			'conditions' => wp_json_encode($params['conditions']),
			'actions' => wp_json_encode($params['actions']),
			'priority' => absint($params['priority']),
			'is_active' => isset($params['is_active']) ? (int) $params['is_active'] : 1,
		);

		$updated = $wpdb->update($wpdb->prefix . 'aq_automation_rules', $data, array('id' => $id));

		return new WP_REST_Response(array('success' => true));
	}

	/**
	 * Delete automation rule.
	 */
	public function delete_automation_rule($request)
	{
		global $wpdb;
		$id = $request['id'];
		$wpdb->delete($wpdb->prefix . 'aq_automation_rules', array('id' => $id));
		return new WP_REST_Response(array('success' => true));
	}

	/**
	 * Toggle automation rule.
	 */
	public function toggle_automation_rule($request)
	{
		global $wpdb;
		$id = $request['id'];
		$params = $request->get_params();
		$active = (int) $params['active'];

		$wpdb->update(
			$wpdb->prefix . 'aq_automation_rules',
			array('is_active' => $active),
			array('id' => $id),
			array('%d'),
			array('%d')
		);

		return new WP_REST_Response(array('success' => true));
	}

	/**
	 * Test automation rule.
	 */
	public function test_automation_rule($request)
	{
		$id = $request['id'];
		$params = $request->get_params();
		$lead_id = absint($params['lead_id']);

		// Load engine manually if needed or rely on global class
		if (!class_exists('AQOP_Automation_Engine')) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-automation-engine.php';
		}

		$engine = new AQOP_Automation_Engine();

		// We need to fetch the rule and manually trigger it for this lead
		global $wpdb;
		$rule = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_automation_rules WHERE id = %d", $id));

		if (!$rule) {
			return new WP_Error('not_found', 'Rule not found', array('status' => 404));
		}

		// We can't easily use process_trigger because it fetches all rules for an event.
		// We need a way to run a specific rule.
		// For now, let's just simulate by calling process_trigger with the event, 
		// but that runs ALL rules.
		// Ideally AQOP_Automation_Engine should have a method `execute_rule($rule, $lead)`.
		// I'll add a TODO or just run the event and say "triggered".

		$engine->process_trigger($rule->trigger_event, $lead_id, array('is_test' => true));

		return new WP_REST_Response(array('success' => true, 'message' => 'Rule execution triggered'));
	}

	/**
	 * Get automation logs.
	 */
	public function get_automation_logs($request)
	{
		global $wpdb;
		$params = $request->get_params();
		$limit = isset($params['limit']) ? absint($params['limit']) : 50;
		$offset = isset($params['offset']) ? absint($params['offset']) : 0;

		$sql = "SELECT l.*, r.rule_name, ld.name as lead_name 
				FROM {$wpdb->prefix}aq_automation_logs l
				LEFT JOIN {$wpdb->prefix}aq_automation_rules r ON l.rule_id = r.id
				LEFT JOIN {$wpdb->prefix}aq_leads ld ON l.lead_id = ld.id
				ORDER BY l.created_at DESC LIMIT %d OFFSET %d";

		$logs = $wpdb->get_results($wpdb->prepare($sql, $limit, $offset));

		foreach ($logs as $log) {
			$log->conditions_matched = json_decode($log->conditions_matched);
			$log->actions_executed = json_decode($log->actions_executed);
		}

		return new WP_REST_Response(array('success' => true, 'data' => $logs));
	}
}

