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
		add_action( 'admin_init', array( $this, 'handle_import_export' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_aqop_add_note', array( $this, 'ajax_add_note' ) );
		add_action( 'wp_ajax_aqop_sync_lead_airtable', array( $this, 'ajax_sync_airtable' ) );
		add_action( 'wp_ajax_aqop_edit_note', array( $this, 'ajax_edit_note' ) );
		add_action( 'wp_ajax_aqop_delete_note', array( $this, 'ajax_delete_note' ) );
		add_action( 'wp_ajax_aqop_bulk_action', array( $this, 'ajax_bulk_action' ) );
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

		// === ANALYTICS DASHBOARD (Phase 4.2) ===
		// Dashboard page (replaces default Control Center for Leads)
		add_submenu_page(
			'aqop-control-center',
			__( 'Leads Dashboard', 'aqop-leads' ),
			__( 'Dashboard', 'aqop-leads' ),
			'manage_options',
			'aqop-leads-dashboard',
			array( $this, 'render_dashboard_page' )
		);
		// === END ANALYTICS DASHBOARD ===
		
		// Main leads list page
		add_submenu_page(
			'aqop-control-center',
			__( 'Leads Management', 'aqop-leads' ),
			__( 'All Leads', 'aqop-leads' ),
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
		
		// === REST API (Phase 3.1) ===
		// API Documentation page
		add_submenu_page(
			'aqop-control-center',
			__( 'API Documentation', 'aqop-leads' ),
			__( 'API Docs', 'aqop-leads' ),
			'manage_options',
			'aqop-leads-api',
			array( $this, 'render_api_docs_page' )
		);
		// === END REST API ===
		
		// === IMPORT/EXPORT (Phase 3.3) ===
		// Import/Export page
		add_submenu_page(
			'aqop-control-center',
			__( 'Import/Export Leads', 'aqop-leads' ),
			__( 'Import/Export', 'aqop-leads' ),
			'manage_options',
			'aqop-import-export',
			array( $this, 'render_import_export_page' )
		);
		// === END IMPORT/EXPORT ===
		
		// === SETTINGS PAGE (Phase 4.1) ===
		// Settings page
		add_submenu_page(
			'aqop-control-center',
			__( 'Leads Settings', 'aqop-leads' ),
			__( 'Settings', 'aqop-leads' ),
			'manage_options',
			'aqop-settings',
			array( $this, 'render_settings_page' )
		);
		// === END SETTINGS PAGE ===
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

	// === REST API (Phase 3.1) ===
	
	/**
	 * Render API documentation page.
	 *
	 * @since 1.0.6
	 */
	public function render_api_docs_page() {
		// Include the API docs template
		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/api-docs.php';
	}
	
	// === END REST API ===

	// === IMPORT/EXPORT (Phase 3.3) ===
	
	/**
	 * Render import/export page.
	 *
	 * @since 1.0.8
	 */
	public function render_import_export_page() {
		// Show results if redirected from import
		if ( isset( $_GET['imported'] ) ) {
			$imported = absint( $_GET['imported'] );
			$updated = isset( $_GET['updated'] ) ? absint( $_GET['updated'] ) : 0;
			$skipped = isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0;
			$errors = isset( $_GET['errors'] ) ? absint( $_GET['errors'] ) : 0;

			echo '<div class="notice notice-success is-dismissible"><p>';
			printf(
				/* translators: 1: imported count, 2: updated count, 3: skipped count */
				esc_html__( 'Import completed: %1$d created, %2$d updated, %3$d skipped.', 'aqop-leads' ),
				$imported,
				$updated,
				$skipped
			);
			echo '</p></div>';

			if ( $errors > 0 ) {
				echo '<div class="notice notice-warning is-dismissible"><p>';
				printf(
					/* translators: %d: error count */
					esc_html__( '%d rows had errors and were skipped.', 'aqop-leads' ),
					$errors
				);
				echo '</p></div>';
			}
		}

		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/import-export.php';
	}

	/**
	 * Handle import/export form submissions.
	 *
	 * @since 1.0.8
	 */
	public function handle_import_export() {
		// Only process on import-export page
		if ( ! isset( $_GET['page'] ) || 'aqop-import-export' !== $_GET['page'] ) {
			return;
		}

		// Handle template download
		if ( isset( $_GET['action'] ) && 'download_template' === $_GET['action'] ) {
			$this->download_csv_template();
			return;
		}

		// Handle POST actions
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );

		if ( 'export_leads' === $action ) {
			$this->handle_export();
		} elseif ( 'import_leads' === $action ) {
			$this->handle_import();
		}
	}

	/**
	 * Download CSV template.
	 *
	 * @since 1.0.8
	 */
	private function download_csv_template() {
		// Check permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'aqop-leads' ) );
		}

		$filename = 'leads_import_template.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for UTF-8
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers with example row
		fputcsv(
			$output,
			array( 'name', 'email', 'phone', 'whatsapp', 'country_id', 'source_id', 'campaign_id', 'status', 'priority' )
		);
		
		// Example row
		fputcsv(
			$output,
			array( 'John Doe', 'john@example.com', '+966501234567', '+966501234567', '1', '1', '', 'pending', 'medium' )
		);

		fclose( $output );
		exit;
	}

	/**
	 * Handle CSV export.
	 *
	 * @since 1.0.8
	 */
	private function handle_export() {
		// Verify nonce
		if ( ! isset( $_POST['aqop_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aqop_export_nonce'] ) ), 'aqop_export_leads' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'aqop-leads' ) );
		}

		// Check permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'aqop-leads' ) );
		}

		// Build query args
		$query_args = array(
			'limit' => 999999, // Get all matching leads
		);
		
		$export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( wp_unslash( $_POST['export_type'] ) ) : 'all';
		
		if ( 'filtered' === $export_type ) {
			if ( ! empty( $_POST['export_status'] ) ) {
				global $wpdb;
				$status_code = sanitize_text_field( wp_unslash( $_POST['export_status'] ) );
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
			
			if ( ! empty( $_POST['export_country'] ) ) {
				$query_args['country'] = absint( $_POST['export_country'] );
			}
			
			if ( ! empty( $_POST['export_priority'] ) ) {
				$query_args['priority'] = sanitize_text_field( wp_unslash( $_POST['export_priority'] ) );
			}
			
			if ( ! empty( $_POST['export_date_from'] ) ) {
				$query_args['date_from'] = sanitize_text_field( wp_unslash( $_POST['export_date_from'] ) );
			}
			
			if ( ! empty( $_POST['export_date_to'] ) ) {
				$query_args['date_to'] = sanitize_text_field( wp_unslash( $_POST['export_date_to'] ) );
			}
		}

		// Get leads
		$results = AQOP_Leads_Manager::query_leads( $query_args );
		$leads = $results['results'];

		// Generate CSV filename
		$filename = 'leads_export_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for UTF-8
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv(
			$output,
			array(
				'ID',
				'Name',
				'Email',
				'Phone',
				'WhatsApp',
				'Country',
				'Source',
				'Status',
				'Priority',
				'Assigned To',
				'Created At',
				'Updated At',
				'Airtable ID',
			)
		);

		// Data
		foreach ( $leads as $lead ) {
			fputcsv(
				$output,
				array(
					$lead->id,
					$lead->name,
					$lead->email,
					$lead->phone,
					$lead->whatsapp,
					$lead->country_name_en,
					$lead->source_name,
					$lead->status_name_en,
					ucfirst( $lead->priority ),
					$lead->assigned_user_name,
					$lead->created_at,
					$lead->updated_at,
					$lead->airtable_record_id,
				)
			);
		}

		fclose( $output );
		
		// Log export event
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'leads_exported',
				'system',
				0,
				array(
					'count'       => count( $leads ),
					'export_type' => $export_type,
					'user_id'     => get_current_user_id(),
				)
			);
		}
		
		exit;
	}

	/**
	 * Handle CSV import.
	 *
	 * @since 1.0.8
	 */
	private function handle_import() {
		// Verify nonce
		if ( ! isset( $_POST['aqop_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aqop_import_nonce'] ) ), 'aqop_import_leads' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'aqop-leads' ) );
		}

		// Check permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'aqop-leads' ) );
		}

		// Check file
		if ( ! isset( $_FILES['import_file'] ) || UPLOAD_ERR_OK !== $_FILES['import_file']['error'] ) {
			wp_die( esc_html__( 'Please select a valid CSV file.', 'aqop-leads' ) );
		}

		$file = $_FILES['import_file'];
		
		// Validate file type
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			wp_die( esc_html__( 'Invalid file type. Please upload a CSV file.', 'aqop-leads' ) );
		}

		// Validate file size (5MB max)
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_die( esc_html__( 'File is too large. Maximum size is 5MB.', 'aqop-leads' ) );
		}

		$default_status = isset( $_POST['import_default_status'] ) ? sanitize_text_field( wp_unslash( $_POST['import_default_status'] ) ) : 'pending';
		$duplicate_handling = isset( $_POST['duplicate_handling'] ) ? sanitize_text_field( wp_unslash( $_POST['duplicate_handling'] ) ) : 'skip';

		// Parse CSV
		$handle = fopen( $file['tmp_name'], 'r' );
		
		// Skip BOM if present
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$headers = fgetcsv( $handle );
		
		if ( ! $headers ) {
			fclose( $handle );
			wp_die( esc_html__( 'Invalid CSV file format.', 'aqop-leads' ) );
		}

		$imported = 0;
		$updated = 0;
		$skipped = 0;
		$errors = 0;

		global $wpdb;
		
		// Get default status ID
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$default_status_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
				$default_status
			)
		);

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) < 3 ) {
				$errors++;
				continue; // Skip invalid rows
			}

			$data = array_combine( $headers, $row );

			// Required fields validation
			if ( empty( $data['name'] ) || empty( $data['email'] ) || empty( $data['phone'] ) ) {
				$errors++;
				continue;
			}

			// Check for duplicate by email
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads WHERE email = %s",
					sanitize_email( $data['email'] )
				)
			);

			if ( $existing && 'skip' === $duplicate_handling ) {
				$skipped++;
				continue;
			}

			// Prepare lead data
			$lead_data = array(
				'name'       => sanitize_text_field( $data['name'] ),
				'email'      => sanitize_email( $data['email'] ),
				'phone'      => sanitize_text_field( $data['phone'] ),
				'whatsapp'   => isset( $data['whatsapp'] ) && ! empty( $data['whatsapp'] ) ? sanitize_text_field( $data['whatsapp'] ) : sanitize_text_field( $data['phone'] ),
				'country_id' => isset( $data['country_id'] ) && ! empty( $data['country_id'] ) ? absint( $data['country_id'] ) : null,
				'source_id'  => isset( $data['source_id'] ) && ! empty( $data['source_id'] ) ? absint( $data['source_id'] ) : null,
				'priority'   => isset( $data['priority'] ) && ! empty( $data['priority'] ) ? sanitize_text_field( $data['priority'] ) : 'medium',
			);

			// Handle status
			if ( isset( $data['status'] ) && ! empty( $data['status'] ) ) {
				$status_code = sanitize_text_field( $data['status'] );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$status_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
						$status_code
					)
				);
				$lead_data['status_id'] = $status_id ? $status_id : $default_status_id;
			} else {
				$lead_data['status_id'] = $default_status_id;
			}

			if ( $existing && 'update' === $duplicate_handling ) {
				// Update existing lead
				$result = AQOP_Leads_Manager::update_lead( $existing, $lead_data );
				if ( $result ) {
					$updated++;
				} else {
					$errors++;
				}
			} else {
				// Create new lead
				$result = AQOP_Leads_Manager::create_lead( $lead_data );
				if ( $result ) {
					$imported++;
				} else {
					$errors++;
				}
			}
		}

		fclose( $handle );

		// Log import event
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'leads_imported',
				'system',
				0,
				array(
					'imported'           => $imported,
					'updated'            => $updated,
					'skipped'            => $skipped,
					'errors'             => $errors,
					'duplicate_handling' => $duplicate_handling,
					'user_id'            => get_current_user_id(),
				)
			);
		}

		// Redirect with results
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'aqop-import-export',
					'imported' => $imported,
					'updated'  => $updated,
					'skipped'  => $skipped,
					'errors'   => $errors,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	
	// === END IMPORT/EXPORT ===

	// === ANALYTICS DASHBOARD (Phase 4.2) ===
	
	/**
	 * Render analytics dashboard page.
	 *
	 * @since 1.0.10
	 */
	public function render_dashboard_page() {
		// Include the dashboard template
		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/dashboard.php';
	}
	
	// === END ANALYTICS DASHBOARD ===

	// === SETTINGS PAGE (Phase 4.1) ===
	
	/**
	 * Render settings page.
	 *
	 * @since 1.0.9
	 */
	public function render_settings_page() {
		// Show success messages
		if ( isset( $_GET['message'] ) ) {
			$message = sanitize_key( $_GET['message'] );
			$messages = array(
				'source_added'        => __( 'Lead source added successfully.', 'aqop-leads' ),
				'source_updated'      => __( 'Lead source updated successfully.', 'aqop-leads' ),
				'integrations_saved'  => __( 'Integration settings saved successfully.', 'aqop-leads' ),
				'notifications_saved' => __( 'Notification settings saved successfully.', 'aqop-leads' ),
			);
			
			if ( isset( $messages[ $message ] ) ) {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $messages[ $message ] )
				);
			}
		}

		include AQOP_LEADS_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Handle settings form submissions.
	 *
	 * @since 1.0.9
	 */
	public function handle_settings_save() {
		// Only process on settings page
		if ( ! isset( $_POST['aqop_settings_action'] ) ) {
			return;
		}

		check_admin_referer( 'aqop_settings_save' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'aqop-leads' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['aqop_settings_action'] ) );

		switch ( $action ) {
			case 'add_source':
				$this->add_lead_source();
				break;
			case 'update_integrations':
				$this->update_integration_settings();
				break;
			case 'update_notifications':
				$this->update_notification_settings();
				break;
		}
	}

	/**
	 * Add new lead source.
	 *
	 * @since 1.0.9
	 */
	private function add_lead_source() {
		global $wpdb;

		$source_name = isset( $_POST['source_name'] ) ? sanitize_text_field( wp_unslash( $_POST['source_name'] ) ) : '';
		$source_type = isset( $_POST['source_type'] ) ? sanitize_text_field( wp_unslash( $_POST['source_type'] ) ) : 'organic';
		$cost_per_lead = isset( $_POST['cost_per_lead'] ) && ! empty( $_POST['cost_per_lead'] ) ? floatval( $_POST['cost_per_lead'] ) : null;

		if ( empty( $source_name ) ) {
			wp_die( esc_html__( 'Source name is required.', 'aqop-leads' ), '', array( 'back_link' => true ) );
		}

		// Generate source code from name
		$source_code = sanitize_title( $source_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_leads_sources',
			array(
				'source_code'  => $source_code,
				'source_name'  => $source_name,
				'source_type'  => $source_type,
				'cost_per_lead' => $cost_per_lead,
				'is_active'    => 1,
			),
			array( '%s', '%s', '%s', '%f', '%d' )
		);

		if ( $inserted ) {
			// Log event
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'source_added',
					'system',
					0,
					array(
						'source_name' => $source_name,
						'source_type' => $source_type,
						'user_id'     => get_current_user_id(),
					)
				);
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'aqop-settings',
						'message' => 'source_added',
					),
					admin_url( 'admin.php' )
				)
			);
		} else {
			wp_die( esc_html__( 'Failed to add source.', 'aqop-leads' ), '', array( 'back_link' => true ) );
		}
		exit;
	}

	/**
	 * Update integration settings.
	 *
	 * @since 1.0.9
	 */
	private function update_integration_settings() {
		// Airtable
		if ( isset( $_POST['airtable_token'] ) ) {
			update_option( 'aqop_airtable_token', sanitize_text_field( wp_unslash( $_POST['airtable_token'] ) ) );
		}
		if ( isset( $_POST['airtable_base_id'] ) ) {
			update_option( 'aqop_airtable_base_id', sanitize_text_field( wp_unslash( $_POST['airtable_base_id'] ) ) );
		}
		if ( isset( $_POST['airtable_table_name'] ) ) {
			update_option( 'aqop_airtable_table_name', sanitize_text_field( wp_unslash( $_POST['airtable_table_name'] ) ) );
		}
		update_option( 'aqop_airtable_auto_sync', isset( $_POST['airtable_auto_sync'] ) ? '1' : '0' );

		// Telegram
		if ( isset( $_POST['telegram_bot_token'] ) ) {
			update_option( 'aqop_telegram_bot_token', sanitize_text_field( wp_unslash( $_POST['telegram_bot_token'] ) ) );
		}
		if ( isset( $_POST['telegram_chat_id'] ) ) {
			update_option( 'aqop_telegram_chat_id', sanitize_text_field( wp_unslash( $_POST['telegram_chat_id'] ) ) );
		}
		update_option( 'aqop_telegram_notify_new', isset( $_POST['telegram_notify_new'] ) ? '1' : '0' );

		// Log event
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'integrations_updated',
				'system',
				0,
				array(
					'user_id' => get_current_user_id(),
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'aqop-settings',
					'message' => 'integrations_saved',
				),
				admin_url( 'admin.php' )
			) . '#integrations'
		);
		exit;
	}

	/**
	 * Update notification settings.
	 *
	 * @since 1.0.9
	 */
	private function update_notification_settings() {
		if ( isset( $_POST['notification_email'] ) ) {
			$email = sanitize_email( wp_unslash( $_POST['notification_email'] ) );
			if ( is_email( $email ) ) {
				update_option( 'aqop_notification_email', $email );
			}
		}
		
		update_option( 'aqop_notify_new_lead', isset( $_POST['notify_new_lead'] ) ? '1' : '0' );
		update_option( 'aqop_notify_status_change', isset( $_POST['notify_status_change'] ) ? '1' : '0' );
		update_option( 'aqop_notify_assignment', isset( $_POST['notify_assignment'] ) ? '1' : '0' );

		// Log event
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'notification_settings_updated',
				'system',
				0,
				array(
					'user_id' => get_current_user_id(),
				)
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'aqop-settings',
					'message' => 'notifications_saved',
				),
				admin_url( 'admin.php' )
			) . '#notifications'
		);
		exit;
	}
	
	// === END SETTINGS PAGE ===

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

	// === BULK ACTIONS (Phase 2.3) ===
	
	/**
	 * Handle bulk actions via AJAX.
	 *
	 * @since 1.0.5
	 */
	public function ajax_bulk_action() {
		// Verify nonce
		check_ajax_referer( 'aqop_leads_nonce', 'nonce' );
		
		// Check permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform bulk actions.', 'aqop-leads' ),
				),
				403
			);
		}
		
		// Get parameters
		$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$lead_ids = isset( $_POST['lead_ids'] ) ? array_map( 'absint', (array) $_POST['lead_ids'] ) : array();
		
		if ( empty( $action ) || empty( $lead_ids ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid action or no leads selected.', 'aqop-leads' ),
				),
				400
			);
		}
		
		$results = array(
			'success' => 0,
			'failed'  => 0,
		);
		
		global $wpdb;
		
		switch ( $action ) {
			case 'delete':
				foreach ( $lead_ids as $lead_id ) {
					$deleted = AQOP_Leads_Manager::delete_lead( $lead_id );
					if ( $deleted ) {
						$results['success']++;
					} else {
						$results['failed']++;
					}
				}
				
				$message = sprintf(
					/* translators: 1: number of leads deleted, 2: number failed */
					__( 'Deleted %1$d leads. %2$d failed.', 'aqop-leads' ),
					$results['success'],
					$results['failed']
				);
				break;
				
			case 'export':
				// Generate CSV
				$csv_data = $this->generate_csv_export( $lead_ids );
				
				wp_send_json_success(
					array(
						'message'  => sprintf( __( 'Exporting %d leads...', 'aqop-leads' ), count( $lead_ids ) ),
						'csv_data' => $csv_data,
						'filename' => 'leads_export_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv',
					)
				);
				return;
				
			default:
				// Handle status change (status_pending, status_contacted, etc.)
				if ( strpos( $action, 'status_' ) === 0 ) {
					$new_status_code = str_replace( 'status_', '', $action );
					
					// Get status ID
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$status_id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
							$new_status_code
						)
					);
					
					if ( ! $status_id ) {
						wp_send_json_error(
							array(
								'message' => __( 'Invalid status.', 'aqop-leads' ),
							),
							400
						);
					}
					
					foreach ( $lead_ids as $lead_id ) {
						$updated = AQOP_Leads_Manager::change_status( $lead_id, $status_id );
						
						if ( $updated ) {
							$results['success']++;
						} else {
							$results['failed']++;
						}
					}
					
					$message = sprintf(
						/* translators: 1: number of leads updated, 2: new status, 3: number failed */
						__( 'Changed status of %1$d leads to %2$s. %3$d failed.', 'aqop-leads' ),
						$results['success'],
						ucfirst( $new_status_code ),
						$results['failed']
					);
				} else {
					wp_send_json_error(
						array(
							'message' => __( 'Unknown bulk action.', 'aqop-leads' ),
						),
						400
					);
				}
				break;
		}
		
		wp_send_json_success(
			array(
				'message' => $message,
				'results' => $results,
			)
		);
	}

	/**
	 * Generate CSV export data.
	 *
	 * @since 1.0.5
	 * @param array $lead_ids Lead IDs to export.
	 * @return string CSV data.
	 */
	private function generate_csv_export( $lead_ids ) {
		if ( empty( $lead_ids ) ) {
			return '';
		}
		
		global $wpdb;
		
		// Build placeholders for IN clause
		$placeholders = implode( ', ', array_fill( 0, count( $lead_ids ), '%d' ) );
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$leads = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, 
				 c.country_name_en,
				 c.country_code,
				 s.source_name,
				 st.status_name_en,
				 u.display_name as assigned_user_name
				 FROM {$wpdb->prefix}aq_leads l
				 LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON l.country_id = c.id
				 LEFT JOIN {$wpdb->prefix}aq_leads_sources s ON l.source_id = s.id
				 LEFT JOIN {$wpdb->prefix}aq_leads_status st ON l.status_id = st.id
				 LEFT JOIN {$wpdb->users} u ON l.assigned_to = u.ID
				 WHERE l.id IN ({$placeholders})
				 ORDER BY l.id ASC",
				$lead_ids
			)
		);
		
		// Build CSV
		ob_start();
		$output = fopen( 'php://output', 'w' );
		
		// Headers
		fputcsv(
			$output,
			array(
				'ID',
				'Name',
				'Email',
				'Phone',
				'WhatsApp',
				'Country',
				'Status',
				'Source',
				'Priority',
				'Assigned To',
				'Created At',
				'Last Updated',
				'Airtable ID',
			)
		);
		
		// Data
		foreach ( $leads as $lead ) {
			fputcsv(
				$output,
				array(
					$lead->id,
					$lead->name,
					$lead->email,
					$lead->phone,
					$lead->whatsapp,
					$lead->country_name_en,
					$lead->status_name_en,
					$lead->source_name,
					ucfirst( $lead->priority ),
					$lead->assigned_user_name,
					$lead->created_at,
					$lead->updated_at,
					$lead->airtable_record_id,
				)
			);
		}
		
		fclose( $output );
		return ob_get_clean();
	}
	
	// === END BULK ACTIONS ===

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
		// === SEARCH & PAGINATION (Phase 2.2) ===
		
		// Pagination
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$current_page = max( 1, $current_page );
		$per_page = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 50;
		$per_page = max( 1, min( $per_page, 200 ) ); // Limit between 1-200
		
		// Search
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		
		// === SORTING (Phase 2.3) ===
		// Sorting parameters
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc';
		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		// === END SORTING ===
		
		// Build query arguments from filters
		$query_args = array(
			'limit'   => $per_page,
			'offset'  => ( $current_page - 1 ) * $per_page,
			'orderby' => $orderby,
			'order'   => $order,
		);
		
		// Search parameter
		if ( ! empty( $search ) ) {
			$query_args['search'] = $search;
		}
		
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
		
		// Query leads with all filters
		$results = AQOP_Leads_Manager::query_leads( $query_args );
		
		// === FILTERS UI ===
		?>
		<!-- === BULK ACTIONS (Phase 2.3) === -->
		<div class="bulk-actions-bar">
			<div class="alignleft actions bulkactions">
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk Actions', 'aqop-leads' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'aqop-leads' ); ?></option>
					<option value="status_pending"><?php esc_html_e( 'Change Status → Pending', 'aqop-leads' ); ?></option>
					<option value="status_contacted"><?php esc_html_e( 'Change Status → Contacted', 'aqop-leads' ); ?></option>
					<option value="status_qualified"><?php esc_html_e( 'Change Status → Qualified', 'aqop-leads' ); ?></option>
					<option value="status_converted"><?php esc_html_e( 'Change Status → Converted', 'aqop-leads' ); ?></option>
					<option value="status_lost"><?php esc_html_e( 'Change Status → Lost', 'aqop-leads' ); ?></option>
					<option value="export"><?php esc_html_e( 'Export to CSV', 'aqop-leads' ); ?></option>
				</select>
				<button type="button" id="doaction" class="button action" disabled>
					<?php esc_html_e( 'Apply', 'aqop-leads' ); ?>
				</button>
			</div>
		</div>
		<!-- === END BULK ACTIONS === -->
		
		<!-- Filters Bar -->
		<form method="get" class="aqop-leads-filters">
			<input type="hidden" name="page" value="aqop-leads">
			
			<!-- === SEARCH BOX (Phase 2.2) === -->
			<div class="search-box">
				<label for="leads-search-input" class="screen-reader-text">
					<?php esc_html_e( 'Search Leads', 'aqop-leads' ); ?>
				</label>
				<input 
					type="search" 
					id="leads-search-input" 
					name="s" 
					value="<?php echo isset( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>" 
					placeholder="<?php esc_attr_e( 'Search by name, email, or phone...', 'aqop-leads' ); ?>"
					class="leads-search-input"
				>
				<button type="submit" class="button">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Search', 'aqop-leads' ); ?>
				</button>
			</div>
			<!-- === END SEARCH BOX === -->
			
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
				
				// Search term
				if ( ! empty( $search ) ) {
					$active_filters[] = sprintf( __( 'Search: %s', 'aqop-leads' ), '<strong>' . esc_html( $search ) . '</strong>' );
				}
				
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
		
		// === RESULTS DISPLAY (Phase 2.2) ===
		
		// Display results summary
		if ( ! empty( $search ) || ! empty( $active_filters ) ) {
			$showing_count = count( $results['results'] );
			$total_count = absint( $results['total'] );
			
			printf(
				'<p class="search-results-info" style="margin-bottom: 10px; color: #646970; font-size: 13px;">%s</p>',
				sprintf(
					/* translators: 1: showing count, 2: total count */
					esc_html__( 'Showing %1$s of %2$s leads', 'aqop-leads' ),
					'<strong>' . number_format_i18n( $showing_count ) . '</strong>',
					'<strong>' . number_format_i18n( $total_count ) . '</strong>'
				)
			);
		}
		
		if ( empty( $results['results'] ) ) {
			$message = ! empty( $search ) 
				? __( 'No leads found matching your search.', 'aqop-leads' )
				: __( 'No leads found matching the selected filters.', 'aqop-leads' );
			echo '<div class="notice notice-warning inline"><p>' . esc_html( $message ) . '</p></div>';
			return;
		}

		?>
		<!-- === SORTABLE TABLE (Phase 2.3) === -->
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" id="cb-select-all-1">
					</td>
					<?php
					// Helper function to generate sortable column
					$sortable_column = function( $column, $label ) use ( $orderby, $order, $search ) {
						$is_current = ( $orderby === $column );
						$new_order = ( $is_current && 'ASC' === $order ) ? 'desc' : 'asc';
						$arrow = '';
						
						if ( $is_current ) {
							$arrow = 'ASC' === $order ? '<span class="dashicons dashicons-arrow-up-alt2"></span>' : '<span class="dashicons dashicons-arrow-down-alt2"></span>';
						}
						
						// Build URL preserving all filters
						$url_args = array(
							'page'    => 'aqop-leads',
							'orderby' => $column,
							'order'   => $new_order,
						);
						
						// Preserve all GET parameters
						if ( ! empty( $search ) ) {
							$url_args['s'] = $search;
						}
						if ( ! empty( $_GET['filter_status'] ) ) {
							$url_args['filter_status'] = sanitize_text_field( wp_unslash( $_GET['filter_status'] ) );
						}
						if ( ! empty( $_GET['filter_country'] ) ) {
							$url_args['filter_country'] = absint( $_GET['filter_country'] );
						}
						if ( ! empty( $_GET['filter_source'] ) ) {
							$url_args['filter_source'] = absint( $_GET['filter_source'] );
						}
						if ( ! empty( $_GET['filter_priority'] ) ) {
							$url_args['filter_priority'] = sanitize_text_field( wp_unslash( $_GET['filter_priority'] ) );
						}
						if ( ! empty( $_GET['filter_date_from'] ) ) {
							$url_args['filter_date_from'] = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
						}
						if ( ! empty( $_GET['filter_date_to'] ) ) {
							$url_args['filter_date_to'] = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
						}
						if ( ! empty( $_GET['paged'] ) ) {
							$url_args['paged'] = absint( $_GET['paged'] );
						}
						if ( ! empty( $_GET['per_page'] ) && 50 !== absint( $_GET['per_page'] ) ) {
							$url_args['per_page'] = absint( $_GET['per_page'] );
						}
						
						$url = add_query_arg( $url_args, admin_url( 'admin.php' ) );
						$class = $is_current ? 'sorted' : 'sortable';
						$class .= $is_current && 'ASC' === $order ? ' asc' : '';
						$class .= $is_current && 'DESC' === $order ? ' desc' : '';
						
						printf(
							'<th scope="col" class="manage-column column-%s %s">
								<a href="%s">
									<span>%s</span>
									<span class="sorting-indicator">%s</span>
								</a>
							</th>',
							esc_attr( $column ),
							esc_attr( $class ),
							esc_url( $url ),
							esc_html( $label ),
							$arrow
						);
					};
					
					// Render sortable columns
					$sortable_column( 'id', __( 'ID', 'aqop-leads' ) );
					$sortable_column( 'name', __( 'Name', 'aqop-leads' ) );
					$sortable_column( 'email', __( 'Email', 'aqop-leads' ) );
					?>
					<th scope="col" class="manage-column column-phone"><?php esc_html_e( 'Phone', 'aqop-leads' ); ?></th>
					<?php
					$sortable_column( 'status_id', __( 'Status', 'aqop-leads' ) );
					?>
					<th scope="col" class="manage-column column-country"><?php esc_html_e( 'Country', 'aqop-leads' ); ?></th>
					<?php
					$sortable_column( 'priority', __( 'Priority', 'aqop-leads' ) );
					$sortable_column( 'created_at', __( 'Created', 'aqop-leads' ) );
					?>
					<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'aqop-leads' ); ?></th>
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
						<th scope="row" class="check-column">
							<input type="checkbox" name="lead_ids[]" value="<?php echo esc_attr( $lead->id ); ?>" class="lead-checkbox">
						</th>
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
							<span class="status-badge" style="background-color: <?php echo esc_attr( $lead->status_color ); ?>; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
								<?php echo esc_html( $lead->status_name_en ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $lead->country_name_en ); ?></td>
						<td>
							<?php
							$priority_colors = array(
								'urgent' => '#f56565',
								'high'   => '#ed8936',
								'medium' => '#4299e1',
								'low'    => '#718096',
							);
							$p_color = isset( $priority_colors[ $lead->priority ] ) ? $priority_colors[ $lead->priority ] : '#718096';
							?>
							<span style="color: <?php echo esc_attr( $p_color ); ?>; font-weight: 600;">
								<?php echo esc_html( ucfirst( $lead->priority ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $lead->created_at ) ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small">
								<?php esc_html_e( 'View', 'aqop-leads' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- === PAGINATION (Phase 2.2) === -->
		<?php
		$total_pages = absint( $results['pages'] );
		
		if ( $total_pages > 1 ) :
			?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							/* translators: %s: number of leads */
							esc_html( _n( '%s lead', '%s leads', $results['total'], 'aqop-leads' ) ),
							number_format_i18n( $results['total'] )
						);
						?>
					</span>
					
					<span class="pagination-links">
						<?php
						// Build base URL with all current filters
						$base_url_args = array( 'page' => 'aqop-leads' );
						
						if ( ! empty( $search ) ) {
							$base_url_args['s'] = $search;
						}
						if ( ! empty( $_GET['filter_status'] ) ) {
							$base_url_args['filter_status'] = sanitize_text_field( wp_unslash( $_GET['filter_status'] ) );
						}
						if ( ! empty( $_GET['filter_country'] ) ) {
							$base_url_args['filter_country'] = absint( $_GET['filter_country'] );
						}
						if ( ! empty( $_GET['filter_source'] ) ) {
							$base_url_args['filter_source'] = absint( $_GET['filter_source'] );
						}
						if ( ! empty( $_GET['filter_priority'] ) ) {
							$base_url_args['filter_priority'] = sanitize_text_field( wp_unslash( $_GET['filter_priority'] ) );
						}
						if ( ! empty( $_GET['filter_date_from'] ) ) {
							$base_url_args['filter_date_from'] = sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) );
						}
						if ( ! empty( $_GET['filter_date_to'] ) ) {
							$base_url_args['filter_date_to'] = sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) );
						}
						if ( $per_page !== 50 ) {
							$base_url_args['per_page'] = $per_page;
						}
						
						$base_url = add_query_arg( $base_url_args, admin_url( 'admin.php' ) );
						
						echo paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%', $base_url ),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => __( '&laquo; Previous', 'aqop-leads' ),
								'next_text' => __( 'Next &raquo;', 'aqop-leads' ),
								'type'      => 'list',
								'end_size'  => 1,
								'mid_size'  => 2,
							)
						);
						?>
					</span>
					
					<!-- Per Page Selector -->
					<span class="per-page-selector">
						<label for="per-page-select"><?php esc_html_e( 'Per page:', 'aqop-leads' ); ?></label>
						<select id="per-page-select" data-current-page="<?php echo esc_attr( $current_page ); ?>">
							<?php
							$per_page_options = array( 20, 50, 100, 200 );
							foreach ( $per_page_options as $option ) {
								printf(
									'<option value="%d" %s>%d</option>',
									esc_attr( $option ),
									selected( $per_page, $option, false ),
									esc_html( $option )
								);
							}
							?>
						</select>
					</span>
				</div>
			</div>
		<?php endif; ?>
		<!-- === END PAGINATION === -->
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
		
		// === ANALYTICS DASHBOARD (Phase 4.2) ===
		// Chart.js for dashboard
		if ( isset( $_GET['page'] ) && 'aqop-leads-dashboard' === $_GET['page'] ) {
			wp_enqueue_script(
				'chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
				array(),
				'4.4.0',
				true
			);
		}
		// === END ANALYTICS DASHBOARD ===
		
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
					'selectBulkAction'  => __( 'Please select an action.', 'aqop-leads' ),
					'selectLeads'       => __( 'Please select at least one lead.', 'aqop-leads' ),
					'confirmBulkDelete' => __( 'Are you sure you want to delete the selected leads?', 'aqop-leads' ),
					'apply'             => __( 'Apply', 'aqop-leads' ),
					'processing'        => __( 'Processing...', 'aqop-leads' ),
					'exporting'         => __( 'Exporting...', 'aqop-leads' ),
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
