<?php
/**
 * Leads Admin Class
 *
 * Handles admin-specific functionality for Leads Module.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Admin class.
 *
 * Manages admin interface for leads.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Admin {

	/**
	 * Initialize admin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_aqop_add_note', array( $this, 'ajax_add_note' ) );
		add_action( 'wp_ajax_aqop_sync_lead_airtable', array( $this, 'ajax_sync_airtable' ) );
		add_action( 'wp_ajax_aqop_edit_note', array( $this, 'ajax_edit_note' ) );
		add_action( 'wp_ajax_aqop_delete_note', array( $this, 'ajax_delete_note' ) );
	}

	/**
	 * Register admin pages.
	 *
	 * Adds Leads submenu to Control Center.
	 *
	 * @since 1.0.0
	 */
	public function register_admin_pages() {
		// Check if user has permission.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Main leads list page
		add_submenu_page(
			'aqop-control-center',
			__( 'Leads Management', 'aqop-leads' ),
			__( 'Leads', 'aqop-leads' ),
			'manage_options',
			'aqop-leads',
			array( $this, 'render_leads_page' )
		);
		
		// Lead detail page (hidden from menu)
		add_submenu_page(
			null, // Hidden submenu
			__( 'Lead Details', 'aqop-leads' ),
			__( 'Lead Details', 'aqop-leads' ),
			'manage_options',
			'aqop-leads-view',
			array( $this, 'render_lead_detail_page' )
		);
		
		// Lead form page (hidden from menu - for add/edit)
		add_submenu_page(
			null, // Hidden submenu
			__( 'Add/Edit Lead', 'aqop-leads' ),
			__( 'Add/Edit Lead', 'aqop-leads' ),
			'manage_options',
			'aqop-leads-form',
			array( $this, 'render_lead_form_page' )
		);
	}

	/**
	 * Render leads page.
	 *
	 * @since 1.0.0
	 */
	public function render_leads_page() {
		// Handle success messages
		if ( isset( $_GET['message'] ) ) {
			$message_type = sanitize_key( $_GET['message'] );
			$messages = array(
				'created'       => array( 'success', __( 'Lead created successfully.', 'aqop-leads' ) ),
				'updated'       => array( 'success', __( 'Lead updated successfully.', 'aqop-leads' ) ),
				'deleted'       => array( 'success', __( 'Lead deleted successfully.', 'aqop-leads' ) ),
				'delete_failed' => array( 'error', __( 'Failed to delete lead. Please try again.', 'aqop-leads' ) ),
				'not_found'     => array( 'error', __( 'Lead not found. It may have already been deleted.', 'aqop-leads' ) ),
			);
			
			if ( isset( $messages[ $message_type ] ) ) {
				list( $type, $text ) = $messages[ $message_type ];
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $text )
				);
			}
		}
		?>
		<div class="wrap aqop-leads-admin">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-businessman"></span>
				<?php esc_html_e( 'Leads Management', 'aqop-leads' ); ?>
			</h1>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'aqop-leads-form' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
				<span class="dashicons dashicons-plus-alt"></span>
				<?php esc_html_e( 'Add New Lead', 'aqop-leads' ); ?>
			</a>
			<hr class="wp-header-end">
			
			<p><?php esc_html_e( 'Manage your leads, track interactions, and monitor performance.', 'aqop-leads' ); ?></p>

			<div class="aqop-leads-stats">
				<?php $this->render_quick_stats(); ?>
			</div>

			<div class="aqop-leads-list">
				<h2><?php esc_html_e( 'Recent Leads', 'aqop-leads' ); ?></h2>
				<?php $this->render_leads_table(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render lead detail page.
	 *
	 * @since 1.0.0
	 */
	public function render_lead_detail_page() {
		// Load the detail handler
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-lead-details-handler.php';
		
		// Include the detail view template
		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/lead-detail.php';
	}

	/**
	 * Render lead form page (add/edit).
	 *
	 * @since 1.0.0
	 */
	public function render_lead_form_page() {
		// Include the form template
		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/lead-form.php';
	}

	/**
	 * Handle form submission for add/edit/delete.
	 *
	 * @since 1.0.0
	 */
	public function handle_form_submission() {
		// Only process on leads admin pages
		if ( ! isset( $_GET['page'] ) || ! in_array( $_GET['page'], array( 'aqop-leads', 'aqop-leads-form', 'aqop-leads-view' ), true ) ) {
			return;
		}
		
		// Handle delete action
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['lead_id'] ) ) {
			$this->handle_delete_lead();
			return;
		}
		
		// Handle save action (add/edit)
		if ( isset( $_POST['save_lead'] ) ) {
			$this->handle_save_lead();
		}
	}

	/**
	 * Handle save lead (add or update).
	 *
	 * @since 1.0.0
	 */
	private function handle_save_lead() {
		// Verify nonce
		if ( ! isset( $_POST['aqop_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aqop_lead_nonce'] ) ), 'aqop_save_lead' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'aqop-leads' ) );
		}
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save leads.', 'aqop-leads' ) );
		}
		
		// Get lead ID (0 for new leads)
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$is_edit = $lead_id > 0;
		
		// Prepare lead data
		$lead_data = array(
			'name'        => isset( $_POST['lead_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_name'] ) ) : '',
			'email'       => isset( $_POST['lead_email'] ) ? sanitize_email( wp_unslash( $_POST['lead_email'] ) ) : '',
			'phone'       => isset( $_POST['lead_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_phone'] ) ) : '',
			'whatsapp'    => isset( $_POST['lead_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_whatsapp'] ) ) : '',
			'country_id'  => isset( $_POST['lead_country'] ) ? absint( $_POST['lead_country'] ) : null,
			'source_id'   => isset( $_POST['lead_source'] ) ? absint( $_POST['lead_source'] ) : null,
			'campaign_id' => isset( $_POST['lead_campaign'] ) ? absint( $_POST['lead_campaign'] ) : null,
			'status_id'   => isset( $_POST['lead_status'] ) ? absint( $_POST['lead_status'] ) : 1,
			'assigned_to' => isset( $_POST['lead_assigned_to'] ) ? absint( $_POST['lead_assigned_to'] ) : null,
			'priority'    => isset( $_POST['lead_priority'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_priority'] ) ) : 'medium',
		);
		
		// Process custom fields
		$custom_fields = array();
		if ( isset( $_POST['custom_field_key'] ) && is_array( $_POST['custom_field_key'] ) ) {
			$keys = array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_key'] ) );
			$values = isset( $_POST['custom_field_value'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['custom_field_value'] ) ) : array();
			
			foreach ( $keys as $index => $key ) {
				if ( ! empty( $key ) && isset( $values[ $index ] ) ) {
					$custom_fields[ $key ] = $values[ $index ];
				}
			}
		}
		
		if ( ! empty( $custom_fields ) ) {
			$lead_data['custom_fields'] = $custom_fields;
		}
		
		// Validate required fields
		if ( empty( $lead_data['name'] ) || empty( $lead_data['email'] ) || empty( $lead_data['phone'] ) ) {
			wp_die( esc_html__( 'Please fill in all required fields.', 'aqop-leads' ), '', array( 'back_link' => true ) );
		}
		
		// Save lead
		if ( $is_edit ) {
			// Update existing lead
			$success = AQOP_Leads_Manager::update_lead( $lead_id, $lead_data );
			
			if ( $success ) {
				// Redirect to detail page with success message
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => 'aqop-leads-view',
							'lead_id' => $lead_id,
							'message' => 'updated',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			} else {
				wp_die( esc_html__( 'Failed to update lead.', 'aqop-leads' ), '', array( 'back_link' => true ) );
			}
		} else {
			// Create new lead
			$new_lead_id = AQOP_Leads_Manager::create_lead( $lead_data );
			
			if ( $new_lead_id ) {
				// Add initial note if provided
				if ( isset( $_POST['lead_notes'] ) && ! empty( $_POST['lead_notes'] ) ) {
					$note_text = sanitize_textarea_field( wp_unslash( $_POST['lead_notes'] ) );
					AQOP_Leads_Manager::add_note( $new_lead_id, $note_text );
				}
				
				// Redirect to detail page with success message
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => 'aqop-leads-view',
							'lead_id' => $new_lead_id,
							'message' => 'created',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			} else {
				wp_die( esc_html__( 'Failed to create lead.', 'aqop-leads' ), '', array( 'back_link' => true ) );
			}
		}
	}

	// === DELETE HANDLER (Phase 1.3 - Enhanced) ===
	
	/**
	 * Handle delete lead action.
	 *
	 * @since 1.0.0
	 */
	private function handle_delete_lead() {
		// Get lead ID
		$lead_id = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
		
		if ( ! $lead_id ) {
			wp_die( esc_html__( 'Invalid lead ID.', 'aqop-leads' ) );
		}
		
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_lead_' . $lead_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'aqop-leads' ) );
		}
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete leads.', 'aqop-leads' ) );
		}
		
		// Get lead before deletion (for logging)
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		
		if ( ! $lead ) {
			// Lead not found - redirect with error
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'aqop-leads',
						'message' => 'not_found',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
		
		// Delete the lead
		$deleted = AQOP_Leads_Manager::delete_lead( $lead_id );
		
		if ( $deleted ) {
			// Log deletion event
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				$current_user = wp_get_current_user();
				AQOP_Event_Logger::log(
					'leads',
					'lead_deleted',
					'lead',
					$lead_id,
					array(
						'lead_name'  => $lead->name,
						'lead_email' => $lead->email,
						'deleted_by' => $current_user->user_login,
						'severity'   => 'info',
					)
				);
			}
			
			// Redirect with success message
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'aqop-leads',
						'message' => 'deleted',
					),
					admin_url( 'admin.php' )
				)
			);
		} else {
			// Redirect with error message
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'aqop-leads',
						'message' => 'delete_failed',
					),
					admin_url( 'admin.php' )
				)
			);
		}
		exit;
	}
	
	// === END DELETE HANDLER ===

	// === NOTES ENHANCEMENT (Phase 1.4) ===
	
	/**
	 * AJAX: Edit note.
	 *
	 * @since 1.0.4
	 */
	public function ajax_edit_note() {
		// Verify nonce
		check_ajax_referer( 'aqop_leads_nonce', 'nonce' );
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to edit notes.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Get parameters
		$note_id = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;
		$note_text = isset( $_POST['note_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_text'] ) ) : '';
		
		// Validate
		if ( ! $note_id || empty( $note_text ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid note ID or text.', 'aqop-leads' ),
				),
				400
			);
		}
		
		// Get note to verify ownership
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$note = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}aq_leads_notes WHERE id = %d",
				$note_id
			)
		);
		
		if ( ! $note ) {
			wp_send_json_error(
				array(
					'message' => __( 'Note not found.', 'aqop-leads' ),
				),
				404
			);
		}
		
		// Check if user is author or admin
		$current_user_id = get_current_user_id();
		if ( (int) $note->user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You can only edit your own notes.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Update note
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$wpdb->prefix . 'aq_leads_notes',
			array( 'note_text' => $note_text ),
			array( 'id' => $note_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		if ( false !== $updated ) {
			// Log note edit event
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_note_edited',
					'lead',
					$note->lead_id,
					array(
						'note_id' => $note_id,
						'user_id' => $current_user_id,
					)
				);
			}
			
			wp_send_json_success(
				array(
					'message' => __( 'Note updated successfully.', 'aqop-leads' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to update note.', 'aqop-leads' ),
				),
				500
			);
		}
	}

	/**
	 * AJAX: Delete note.
	 *
	 * @since 1.0.4
	 */
	public function ajax_delete_note() {
		// Verify nonce
		check_ajax_referer( 'aqop_leads_nonce', 'nonce' );
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to delete notes.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Get parameter
		$note_id = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;
		
		if ( ! $note_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid note ID.', 'aqop-leads' ),
				),
				400
			);
		}
		
		// Get note to verify ownership and get lead_id for logging
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$note = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}aq_leads_notes WHERE id = %d",
				$note_id
			)
		);
		
		if ( ! $note ) {
			wp_send_json_error(
				array(
					'message' => __( 'Note not found.', 'aqop-leads' ),
				),
				404
			);
		}
		
		// Check if user is author or admin
		$current_user_id = get_current_user_id();
		if ( (int) $note->user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You can only delete your own notes.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Delete note
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$wpdb->prefix . 'aq_leads_notes',
			array( 'id' => $note_id ),
			array( '%d' )
		);
		
		if ( $deleted ) {
			// Log note deletion event
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_note_deleted',
					'lead',
					$note->lead_id,
					array(
						'note_id' => $note_id,
						'user_id' => $current_user_id,
					)
				);
			}
			
			wp_send_json_success(
				array(
					'message' => __( 'Note deleted successfully.', 'aqop-leads' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to delete note.', 'aqop-leads' ),
				),
				500
			);
		}
	}
	
	// === END NOTES ENHANCEMENT ===

	/**
	 * Render quick stats.
	 *
	 * @since 1.0.0
	 */
	private function render_quick_stats() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads WHERE status_id = (SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = 'pending')"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$converted = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads WHERE status_id = (SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = 'converted')"
		);

		?>
		<div class="stats-grid">
			<div class="stat-card">
				<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
				<span><?php esc_html_e( 'Total Leads', 'aqop-leads' ); ?></span>
			</div>
			<div class="stat-card">
				<strong><?php echo esc_html( number_format_i18n( $pending ) ); ?></strong>
				<span><?php esc_html_e( 'Pending', 'aqop-leads' ); ?></span>
			</div>
			<div class="stat-card">
				<strong><?php echo esc_html( number_format_i18n( $converted ) ); ?></strong>
				<span><?php esc_html_e( 'Converted', 'aqop-leads' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render leads table.
	 *
	 * @since 1.0.0
	 */
	private function render_leads_table() {
		global $wpdb;
		
		// === FILTERS (Phase 2.1) ===
		
		// Build query arguments from filters
		$query_args = array(
			'limit'   => 50,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		);
		
		// Status filter
		if ( ! empty( $_GET['filter_status'] ) ) {
			$status_code = sanitize_text_field( wp_unslash( $_GET['filter_status'] ) );
			// Get status ID from code
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
					$status_code
				)
			);
			if ( $status_id ) {
				$query_args['status'] = $status_id;
			}
		}
		
		// Country filter
		if ( ! empty( $_GET['filter_country'] ) ) {
			$query_args['country'] = absint( $_GET['filter_country'] );
		}
		
		// Source filter
		if ( ! empty( $_GET['filter_source'] ) ) {
			$query_args['source'] = absint( $_GET['filter_source'] );
		}
		
		// Priority filter
		if ( ! empty( $_GET['filter_priority'] ) ) {
			$query_args['priority'] = sanitize_text_field( wp_unslash( $_GET['filter_priority'] ) );
		}
		
		// Date range filter
		if ( ! empty( $_GET['filter_date_from'] ) ) {
			$query_args['date_from'] = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
		}
		if ( ! empty( $_GET['filter_date_to'] ) ) {
			$query_args['date_to'] = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
		}
		
		// Query leads with filters
		$results = AQOP_Leads_Manager::query_leads( $query_args );
		
		// === FILTERS UI ===
		?>
		<!-- Filters Bar -->
		<form method="get" class="aqop-leads-filters">
			<input type="hidden" name="page" value="aqop-leads">
			
			<div class="tablenav top">
				<div class="alignleft actions">
					
					<!-- Status Filter -->
					<select name="filter_status" id="filter_status">
						<option value=""><?php esc_html_e( 'All Statuses', 'aqop-leads' ); ?></option>
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$statuses = $wpdb->get_results(
							"SELECT id, status_code, status_name_en, color 
							 FROM {$wpdb->prefix}aq_leads_status 
							 WHERE is_active = 1 
							 ORDER BY status_order ASC"
						);
						$current_status = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
						foreach ( $statuses as $status ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $status->status_code ),
								selected( $current_status, $status->status_code, false ),
								esc_html( $status->status_name_en )
							);
						}
						?>
					</select>
					
					<!-- Country Filter -->
					<select name="filter_country" id="filter_country">
						<option value=""><?php esc_html_e( 'All Countries', 'aqop-leads' ); ?></option>
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$countries = $wpdb->get_results(
							"SELECT id, country_code, country_name_en 
							 FROM {$wpdb->prefix}aq_dim_countries 
							 WHERE is_active = 1 
							 ORDER BY country_name_en ASC"
						);
						$current_country = isset( $_GET['filter_country'] ) ? absint( $_GET['filter_country'] ) : 0;
						foreach ( $countries as $country ) {
							printf(
								'<option value="%d" %s>%s</option>',
								esc_attr( $country->id ),
								selected( $current_country, $country->id, false ),
								esc_html( $country->country_name_en )
							);
						}
						?>
					</select>
					
					<!-- Source Filter -->
					<select name="filter_source" id="filter_source">
						<option value=""><?php esc_html_e( 'All Sources', 'aqop-leads' ); ?></option>
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$sources = $wpdb->get_results(
							"SELECT id, source_name 
							 FROM {$wpdb->prefix}aq_leads_sources 
							 WHERE is_active = 1 
							 ORDER BY source_name ASC"
						);
						$current_source = isset( $_GET['filter_source'] ) ? absint( $_GET['filter_source'] ) : 0;
						foreach ( $sources as $source ) {
							printf(
								'<option value="%d" %s>%s</option>',
								esc_attr( $source->id ),
								selected( $current_source, $source->id, false ),
								esc_html( $source->source_name )
							);
						}
						?>
					</select>
					
					<!-- Priority Filter -->
					<select name="filter_priority" id="filter_priority">
						<option value=""><?php esc_html_e( 'All Priorities', 'aqop-leads' ); ?></option>
						<?php
						$priorities = array( 'urgent', 'high', 'medium', 'low' );
						$current_priority = isset( $_GET['filter_priority'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_priority'] ) ) : '';
						foreach ( $priorities as $priority ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $priority ),
								selected( $current_priority, $priority, false ),
								esc_html( ucfirst( $priority ) )
							);
						}
						?>
					</select>
					
					<!-- Date Range Filter -->
					<input 
						type="date" 
						name="filter_date_from" 
						id="filter_date_from" 
						value="<?php echo isset( $_GET['filter_date_from'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) ) : ''; ?>" 
						placeholder="<?php esc_attr_e( 'From Date', 'aqop-leads' ); ?>"
					>
					
					<input 
						type="date" 
						name="filter_date_to" 
						id="filter_date_to" 
						value="<?php echo isset( $_GET['filter_date_to'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) ) : ''; ?>" 
						placeholder="<?php esc_attr_e( 'To Date', 'aqop-leads' ); ?>"
					>
					
					<!-- Apply Filters Button -->
					<button type="submit" class="button action">
						<span class="dashicons dashicons-filter"></span>
						<?php esc_html_e( 'Apply Filters', 'aqop-leads' ); ?>
					</button>
					
					<!-- Clear Filters Button -->
					<?php if ( ! empty( $_GET['filter_status'] ) || ! empty( $_GET['filter_country'] ) || ! empty( $_GET['filter_source'] ) || ! empty( $_GET['filter_priority'] ) || ! empty( $_GET['filter_date_from'] ) || ! empty( $_GET['filter_date_to'] ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-leads' ) ); ?>" class="button">
						<span class="dashicons dashicons-dismiss"></span>
						<?php esc_html_e( 'Clear Filters', 'aqop-leads' ); ?>
					</a>
					<?php endif; ?>
					
				</div>
				
				<!-- Active Filters Display -->
				<?php
				$active_filters = array();
				
				if ( ! empty( $_GET['filter_status'] ) ) {
					$status_name = '';
					$status_code = sanitize_text_field( wp_unslash( $_GET['filter_status'] ) );
					foreach ( $statuses as $status ) {
						if ( $status->status_code === $status_code ) {
							$status_name = $status->status_name_en;
							break;
						}
					}
					if ( $status_name ) {
						$active_filters[] = sprintf( __( 'Status: %s', 'aqop-leads' ), '<strong>' . esc_html( $status_name ) . '</strong>' );
					}
				}
				
				if ( ! empty( $_GET['filter_country'] ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$country_name = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT country_name_en FROM {$wpdb->prefix}aq_dim_countries WHERE id = %d",
							absint( $_GET['filter_country'] )
						)
					);
					if ( $country_name ) {
						$active_filters[] = sprintf( __( 'Country: %s', 'aqop-leads' ), '<strong>' . esc_html( $country_name ) . '</strong>' );
					}
				}
				
				if ( ! empty( $_GET['filter_source'] ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$source_name = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT source_name FROM {$wpdb->prefix}aq_leads_sources WHERE id = %d",
							absint( $_GET['filter_source'] )
						)
					);
					if ( $source_name ) {
						$active_filters[] = sprintf( __( 'Source: %s', 'aqop-leads' ), '<strong>' . esc_html( $source_name ) . '</strong>' );
					}
				}
				
				if ( ! empty( $_GET['filter_priority'] ) ) {
					$active_filters[] = sprintf( __( 'Priority: %s', 'aqop-leads' ), '<strong>' . esc_html( ucfirst( sanitize_text_field( wp_unslash( $_GET['filter_priority'] ) ) ) ) . '</strong>' );
				}
				
				if ( ! empty( $_GET['filter_date_from'] ) || ! empty( $_GET['filter_date_to'] ) ) {
					$date_from = ! empty( $_GET['filter_date_from'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) ) : '...';
					$date_to = ! empty( $_GET['filter_date_to'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) ) : '...';
					$active_filters[] = sprintf( __( 'Date Range: %s to %s', 'aqop-leads' ), '<strong>' . $date_from . '</strong>', '<strong>' . $date_to . '</strong>' );
				}
				
				if ( ! empty( $active_filters ) ) {
					echo '<div class="active-filters">';
					echo '<span class="dashicons dashicons-filter"></span> ';
					echo esc_html__( 'Active Filters: ', 'aqop-leads' );
					echo wp_kses_post( implode( ' <span class="filter-badge">•</span> ', $active_filters ) );
					echo '</div>';
				}
				?>
			</div>
		</form>
		<!-- === END FILTERS === -->
		
		<?php
		
		// Display results count
		if ( ! empty( $active_filters ) ) {
			printf(
				'<p class="search-results-info" style="margin-bottom: 10px; color: #646970;">%s</p>',
				sprintf(
					/* translators: %d: number of leads found */
					esc_html( _n( 'Found %d lead matching filters', 'Found %d leads matching filters', $results['total'], 'aqop-leads' ) ),
					'<strong>' . absint( $results['total'] ) . '</strong>'
				)
			);
		}
		
		if ( empty( $results['results'] ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'No leads found matching the selected filters.', 'aqop-leads' ) . '</p></div>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Name', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Email', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Status', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Country', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Created', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'aqop-leads' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $results['results'] as $lead ) : ?>
					<?php
					$view_url = add_query_arg(
						array(
							'page'    => 'aqop-leads-view',
							'lead_id' => $lead->id,
						),
						admin_url( 'admin.php' )
					);
					?>
					<tr>
						<td><?php echo esc_html( $lead->id ); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url( $view_url ); ?>">
									<?php echo esc_html( $lead->name ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $lead->email ); ?></td>
						<td><?php echo esc_html( $lead->phone ); ?></td>
						<td>
							<span class="status-badge" style="background-color: <?php echo esc_attr( $lead->status_color ); ?>;">
								<?php echo esc_html( $lead->status_name_en ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $lead->country_name_en ); ?></td>
						<td><?php echo esc_html( $lead->created_at ); ?></td>
						<td>
							<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'aqop-leads' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.0.0
	 * @param string $hook Current page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only on leads pages.
		if ( strpos( $hook, 'aqop-leads' ) === false ) {
			return;
		}

		// Common leads admin CSS
		wp_enqueue_style(
			'aqop-leads-admin',
			AQOP_LEADS_PLUGIN_URL . 'admin/css/leads-admin.css',
			array(),
			AQOP_LEADS_VERSION
		);
		
		// === FILTERS (Phase 2.1) ===
		// Load filters CSS on main leads list page
		if ( 'operation-platform_page_aqop-leads' === $hook ) {
			wp_enqueue_style(
				'aqop-leads-filters',
				AQOP_LEADS_PLUGIN_URL . 'admin/css/leads-filters.css',
				array(),
				AQOP_LEADS_VERSION
			);
		}
		// === END FILTERS ===

		// Common leads admin JS
		wp_enqueue_script(
			'aqop-leads-admin',
			AQOP_LEADS_PLUGIN_URL . 'admin/js/leads-admin.js',
			array( 'jquery' ),
			AQOP_LEADS_VERSION,
			true
		);
		
		// Localize script for AJAX
		wp_localize_script(
			'aqop-leads-admin',
			'aqopLeads',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'aqop_leads_nonce' ),
				'strings'   => array(
					'adding'            => __( 'Adding...', 'aqop-leads' ),
					'addNote'           => __( 'Add Note', 'aqop-leads' ),
					'syncing'           => __( 'Syncing...', 'aqop-leads' ),
					'syncAirtable'      => __( 'Sync to Airtable', 'aqop-leads' ),
					'error'             => __( 'An error occurred. Please try again.', 'aqop-leads' ),
					'noteFailed'        => __( 'Failed to add note.', 'aqop-leads' ),
					'syncSuccess'       => __( 'Lead synced to Airtable successfully!', 'aqop-leads' ),
					'syncFailed'        => __( 'Failed to sync to Airtable.', 'aqop-leads' ),
					'confirmDelete'     => __( 'Are you sure you want to delete this lead?', 'aqop-leads' ),
					'noteEmpty'         => __( 'Note text cannot be empty.', 'aqop-leads' ),
					'confirmDeleteNote' => __( 'Are you sure you want to delete this note?', 'aqop-leads' ),
					'noNotes'           => __( 'No notes yet. Add the first note above.', 'aqop-leads' ),
				),
			)
		);
		
		// Load detail page assets only on detail page
		if ( 'admin_page_aqop-leads-view' === $hook ) {
			wp_enqueue_style(
				'aqop-lead-detail',
				AQOP_LEADS_PLUGIN_URL . 'admin/css/lead-detail.css',
				array(),
				AQOP_LEADS_VERSION
			);
			
			wp_enqueue_script(
				'aqop-lead-detail',
				AQOP_LEADS_PLUGIN_URL . 'admin/js/lead-detail.js',
				array( 'jquery', 'aqop-leads-admin' ),
				AQOP_LEADS_VERSION,
				true
			);
		}
	}

	/**
	 * AJAX: Add note to lead.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_note() {
		// Verify nonce
		check_ajax_referer( 'aqop_add_note', 'aqop_note_nonce' );
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to add notes.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Get parameters
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		$note_text = isset( $_POST['note_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_text'] ) ) : '';
		
		// Validate
		if ( ! $lead_id || empty( $note_text ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid lead ID or note text.', 'aqop-leads' ),
				),
				400
			);
		}
		
		// Add note
		$note_id = AQOP_Leads_Manager::add_note( $lead_id, $note_text );
		
		if ( $note_id ) {
			wp_send_json_success(
				array(
					'message' => __( 'Note added successfully.', 'aqop-leads' ),
					'note_id' => $note_id,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to add note. Please try again.', 'aqop-leads' ),
				),
				500
			);
		}
	}

	/**
	 * AJAX: Sync lead to Airtable.
	 *
	 * @since 1.0.0
	 */
	public function ajax_sync_airtable() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aqop_leads_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Check capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to sync leads.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Check if Integrations Hub is available
		if ( ! class_exists( 'AQOP_Integrations_Hub' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Integrations Hub is not available.', 'aqop-leads' ),
				),
				500
			);
		}
		
		// Get lead ID
		$lead_id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
		
		if ( ! $lead_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid lead ID.', 'aqop-leads' ),
				),
				400
			);
		}
		
		// Get lead
		$lead = AQOP_Leads_Manager::get_lead( $lead_id );
		
		if ( ! $lead ) {
			wp_send_json_error(
				array(
					'message' => __( 'Lead not found.', 'aqop-leads' ),
				),
				404
			);
		}
		
		// Prepare Airtable data
		$airtable_data = array(
			'Name'           => $lead->name,
			'Email'          => $lead->email,
			'Phone'          => $lead->phone,
			'WhatsApp'       => $lead->whatsapp,
			'Country'        => $lead->country_name_en,
			'Status'         => $lead->status_name_en,
			'Source'         => $lead->source_name,
			'Priority'       => ucfirst( $lead->priority ),
			'Assigned To'    => $lead->assigned_user_name,
			'Created Date'   => $lead->created_at,
			'Last Updated'   => $lead->updated_at,
			'WordPress ID'   => $lead_id,
		);
		
		// Sync to Airtable
		$result = AQOP_Integrations_Hub::sync_to_airtable( 'leads', $lead_id, $airtable_data );
		
		if ( $result['success'] ) {
			// Update airtable_record_id if returned
			if ( isset( $result['airtable_id'] ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->prefix . 'aq_leads',
					array( 'airtable_record_id' => $result['airtable_id'] ),
					array( 'id' => $lead_id ),
					array( '%s' ),
					array( '%d' )
				);
			}
			
			wp_send_json_success(
				array(
					'message'     => __( 'Lead synced to Airtable successfully.', 'aqop-leads' ),
					'airtable_id' => $result['airtable_id'] ?? null,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result['message'] ?? __( 'Failed to sync to Airtable.', 'aqop-leads' ),
				),
				500
			);
		}
	}

	/**
	 * Render leads table.
	 *
	 * @since 1.0.0
	 */
	private function render_leads_table() {
		$results = AQOP_Leads_Manager::query_leads(
			array(
				'limit'   => 20,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			)
		);

		if ( empty( $results['results'] ) ) {
			echo '<p>' . esc_html__( 'No leads found.', 'aqop-leads' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Name', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Email', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Status', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Country', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Created', 'aqop-leads' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'aqop-leads' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $results['results'] as $lead ) : ?>
					<?php
					$view_url = add_query_arg(
						array(
							'page'    => 'aqop-leads-view',
							'lead_id' => $lead->id,
						),
						admin_url( 'admin.php' )
					);
					?>
					<tr>
						<td><?php echo esc_html( $lead->id ); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url( $view_url ); ?>">
									<?php echo esc_html( $lead->name ); ?>
								</a>
							</strong>
						</td>
						<td><?php echo esc_html( $lead->email ); ?></td>
						<td><?php echo esc_html( $lead->phone ); ?></td>
						<td>
							<span class="status-badge" style="background-color: <?php echo esc_attr( $lead->status_color ); ?>;">
								<?php echo esc_html( $lead->status_name_en ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $lead->country_name_en ); ?></td>
						<td><?php echo esc_html( $lead->created_at ); ?></td>
						<td>
							<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'aqop-leads' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
