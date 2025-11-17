<?php
/**
 * Leads REST API Controller
 *
 * Provides secure REST API endpoints for external integrations.
 *
 * @package AQOP_Leads
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_API class.
 *
 * REST API controller for leads management.
 *
 * @since 1.0.6
 */
class AQOP_Leads_API {

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
	public function register_routes() {
		// === REST API (Phase 3.1) ===
		
		// List leads (GET /aqop/v1/leads)
		register_rest_route(
			$this->namespace,
			'/leads',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_leads' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_collection_params(),
			)
		);

		// Get single lead (GET /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
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
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_lead_schema(),
			)
		);

		// Update lead (PUT/PATCH /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
					$this->get_lead_schema()
				),
			)
		);

		// Delete lead (DELETE /aqop/v1/leads/{id})
		register_rest_route(
			$this->namespace,
			'/leads/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_statuses' ),
				'permission_callback' => '__return_true', // Public endpoint
			)
		);

		// Get countries (GET /aqop/v1/leads/countries)
		register_rest_route(
			$this->namespace,
			'/leads/countries',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_countries' ),
				'permission_callback' => '__return_true', // Public endpoint
			)
		);

		// Get sources (GET /aqop/v1/leads/sources)
		register_rest_route(
			$this->namespace,
			'/leads/sources',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sources' ),
				'permission_callback' => '__return_true', // Public endpoint
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
	public function get_leads( $request ) {
		$params = $request->get_params();
		
		$args = array(
			'limit'   => isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 50,
			'offset'  => isset( $params['page'] ) ? ( absint( $params['page'] ) - 1 ) * absint( $params['per_page'] ) : 0,
			'orderby' => isset( $params['orderby'] ) ? sanitize_text_field( $params['orderby'] ) : 'created_at',
			'order'   => isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'DESC',
		);
		
		// Apply filters
		if ( ! empty( $params['status'] ) ) {
			// Convert status code to ID
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
					sanitize_text_field( $params['status'] )
				)
			);
			if ( $status_id ) {
				$args['status'] = $status_id;
			}
		}
		
		if ( ! empty( $params['country'] ) ) {
			$args['country'] = absint( $params['country'] );
		}
		
		if ( ! empty( $params['source'] ) ) {
			$args['source'] = absint( $params['source'] );
		}
		
		if ( ! empty( $params['priority'] ) ) {
			$args['priority'] = sanitize_text_field( $params['priority'] );
		}
		
		if ( ! empty( $params['search'] ) ) {
			$args['search'] = sanitize_text_field( $params['search'] );
		}
		
		$result = AQOP_Leads_Manager::query_leads( $args );
		
		$response = new WP_REST_Response(
			array(
				'leads'    => $result['results'],
				'total'    => $result['total'],
				'pages'    => $result['pages'],
				'page'     => isset( $params['page'] ) ? absint( $params['page'] ) : 1,
				'per_page' => isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 50,
			)
		);
		
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', $result['pages'] );
		
		return $response;
	}

	/**
	 * Get single lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_lead( $request ) {
		$lead_id = $request['id'];
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		
		if ( ! $lead ) {
			return new WP_Error(
				'lead_not_found',
				__( 'Lead not found.', 'aqop-leads' ),
				array( 'status' => 404 )
			);
		}
		
		// Get notes
		$notes = AQOP_Leads_Manager::get_notes( $lead_id );
		
		return new WP_REST_Response(
			array(
				'lead'  => $lead,
				'notes' => $notes,
			)
		);
	}

	/**
	 * Create lead.
	 *
	 * @since 1.0.6
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function create_lead( $request ) {
		$params = $request->get_params();
		
		// Validate required fields
		if ( empty( $params['name'] ) || empty( $params['email'] ) || empty( $params['phone'] ) ) {
			return new WP_Error(
				'missing_required_fields',
				__( 'Name, email, and phone are required fields.', 'aqop-leads' ),
				array( 'status' => 400 )
			);
		}
		
		$lead_data = array(
			'name'        => sanitize_text_field( $params['name'] ),
			'email'       => sanitize_email( $params['email'] ),
			'phone'       => sanitize_text_field( $params['phone'] ),
			'whatsapp'    => isset( $params['whatsapp'] ) ? sanitize_text_field( $params['whatsapp'] ) : '',
			'country_id'  => isset( $params['country_id'] ) ? absint( $params['country_id'] ) : null,
			'source_id'   => isset( $params['source_id'] ) ? absint( $params['source_id'] ) : null,
			'campaign_id' => isset( $params['campaign_id'] ) ? absint( $params['campaign_id'] ) : null,
			'priority'    => isset( $params['priority'] ) ? sanitize_text_field( $params['priority'] ) : 'medium',
		);
		
		// Handle status
		if ( isset( $params['status'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
					sanitize_text_field( $params['status'] )
				)
			);
			if ( $status_id ) {
				$lead_data['status_id'] = $status_id;
			}
		}
		
		$lead_id = AQOP_Leads_Manager::create_lead( $lead_data );
		
		if ( ! $lead_id ) {
			return new WP_Error(
				'create_failed',
				__( 'Failed to create lead.', 'aqop-leads' ),
				array( 'status' => 500 )
			);
		}
		
		// Add initial note if provided
		if ( ! empty( $params['note'] ) ) {
			AQOP_Leads_Manager::add_note( $lead_id, sanitize_textarea_field( $params['note'] ) );
		}
		
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		
		return new WP_REST_Response(
			array(
				'message' => __( 'Lead created successfully.', 'aqop-leads' ),
				'lead'    => $lead,
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
	public function update_lead( $request ) {
		$lead_id = $request['id'];
		$params = $request->get_params();
		
		// Check if lead exists
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		if ( ! $lead ) {
			return new WP_Error(
				'lead_not_found',
				__( 'Lead not found.', 'aqop-leads' ),
				array( 'status' => 404 )
			);
		}
		
		$lead_data = array();
		
		$allowed_fields = array( 'name', 'email', 'phone', 'whatsapp', 'country_id', 'source_id', 'campaign_id', 'status', 'priority', 'assigned_to' );
		
		foreach ( $allowed_fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				if ( in_array( $field, array( 'country_id', 'source_id', 'campaign_id', 'assigned_to' ), true ) ) {
					$lead_data[ $field ] = absint( $params[ $field ] );
				} elseif ( 'email' === $field ) {
					$lead_data[ $field ] = sanitize_email( $params[ $field ] );
				} elseif ( 'status' === $field ) {
					// Convert status code to ID
					global $wpdb;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$status_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
							sanitize_text_field( $params['status'] )
						)
					);
					if ( $status_id ) {
						$lead_data['status_id'] = $status_id;
					}
				} else {
					$lead_data[ $field ] = sanitize_text_field( $params[ $field ] );
				}
			}
		}
		
		if ( empty( $lead_data ) ) {
			return new WP_Error(
				'no_fields_to_update',
				__( 'No fields provided for update.', 'aqop-leads' ),
				array( 'status' => 400 )
			);
		}
		
		$updated = AQOP_Leads_Manager::update_lead( $lead_id, $lead_data );
		
		if ( ! $updated ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update lead.', 'aqop-leads' ),
				array( 'status' => 500 )
			);
		}
		
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		
		return new WP_REST_Response(
			array(
				'message' => __( 'Lead updated successfully.', 'aqop-leads' ),
				'lead'    => $lead,
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
	public function delete_lead( $request ) {
		$lead_id = $request['id'];
		
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		if ( ! $lead ) {
			return new WP_Error(
				'lead_not_found',
				__( 'Lead not found.', 'aqop-leads' ),
				array( 'status' => 404 )
			);
		}
		
		$deleted = AQOP_Leads_Manager::delete_lead( $lead_id );
		
		if ( ! $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete lead.', 'aqop-leads' ),
				array( 'status' => 500 )
			);
		}
		
		return new WP_REST_Response(
			array(
				'message' => __( 'Lead deleted successfully.', 'aqop-leads' ),
				'deleted' => true,
				'id'      => $lead_id,
			)
		);
	}

	/**
	 * Get available statuses.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_statuses() {
		global $wpdb;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$statuses = $wpdb->get_results(
			"SELECT id, status_code, status_name_en, status_name_ar, color, status_order
			 FROM {$wpdb->prefix}aq_leads_status
			 WHERE is_active = 1
			 ORDER BY status_order ASC"
		);
		
		return new WP_REST_Response( $statuses );
	}

	/**
	 * Get available countries.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_countries() {
		global $wpdb;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$countries = $wpdb->get_results(
			"SELECT id, country_code, country_name_en, country_name_ar, region
			 FROM {$wpdb->prefix}aq_dim_countries
			 WHERE is_active = 1
			 ORDER BY country_name_en ASC"
		);
		
		return new WP_REST_Response( $countries );
	}

	/**
	 * Get available sources.
	 *
	 * @since 1.0.6
	 * @return WP_REST_Response Response object.
	 */
	public function get_sources() {
		global $wpdb;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sources = $wpdb->get_results(
			"SELECT id, source_code, source_name, source_type
			 FROM {$wpdb->prefix}aq_leads_sources
			 WHERE is_active = 1
			 ORDER BY source_name ASC"
		);
		
		return new WP_REST_Response( $sources );
	}

	/**
	 * Check API permission.
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error Permission result.
	 */
	public function check_permission() {
		// Require manage_options capability
		// For production with external apps, implement Application Passwords or OAuth
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'aqop-leads' ),
				array( 'status' => 403 )
			);
		}
		
		return true;
	}

	/**
	 * Get collection parameters schema.
	 *
	 * @since 1.0.6
	 * @return array Collection parameters.
	 */
	private function get_collection_params() {
		return array(
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $param ) {
					return $param > 0;
				},
			),
			'per_page' => array(
				'default'           => 50,
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $param ) {
					return $param > 0 && $param <= 200;
				},
			),
			'search'   => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'status'   => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country'  => array(
				'sanitize_callback' => 'absint',
			),
			'source'   => array(
				'sanitize_callback' => 'absint',
			),
			'priority' => array(
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => array( 'urgent', 'high', 'medium', 'low' ),
			),
			'orderby'  => array(
				'default'           => 'created_at',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => array( 'id', 'name', 'email', 'created_at', 'updated_at', 'status_id', 'priority' ),
			),
			'order'    => array(
				'default'           => 'DESC',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => array( 'ASC', 'DESC', 'asc', 'desc' ),
			),
		);
	}

	/**
	 * Get lead schema for validation.
	 *
	 * @since 1.0.6
	 * @return array Lead schema.
	 */
	private function get_lead_schema() {
		return array(
			'name'        => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'       => array(
				'required'          => true,
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'is_email',
			),
			'phone'       => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'whatsapp'    => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country_id'  => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'source_id'   => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'campaign_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'status'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'priority'    => array(
				'type'              => 'string',
				'enum'              => array( 'urgent', 'high', 'medium', 'low' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'medium',
			),
			'assigned_to' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'note'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}
}

