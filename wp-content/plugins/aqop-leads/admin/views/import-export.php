<?php
/**
 * Import/Export Page
 *
 * Provides interface for bulk lead import and export operations.
 *
 * @package AQOP_Leads
 * @since   1.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check permission
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'aqop-leads' ) );
}

global $wpdb;
?>

<div class="wrap aqop-import-export">
	<h1>
		<span class="dashicons dashicons-database-import"></span>
		<?php esc_html_e( 'Import/Export Leads', 'aqop-leads' ); ?>
	</h1>
	
	<p class="description">
		<?php esc_html_e( 'Bulk import leads from CSV or export existing leads for backup and analysis.', 'aqop-leads' ); ?>
	</p>

	<div class="aqop-ie-container">
		
		<!-- === EXPORT SECTION (Phase 3.3) === -->
		<div class="card aqop-ie-section aqop-export-section">
			<h2>
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export Leads', 'aqop-leads' ); ?>
			</h2>
			<p><?php esc_html_e( 'Download leads as CSV file for backup, analysis, or migration.', 'aqop-leads' ); ?></p>
			
			<form method="post" class="aqop-export-form">
				<?php wp_nonce_field( 'aqop_export_leads', 'aqop_export_nonce' ); ?>
				<input type="hidden" name="action" value="export_leads">
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Export Type', 'aqop-leads' ); ?></th>
							<td>
								<label>
									<input type="radio" name="export_type" value="all" checked>
									<?php esc_html_e( 'All Leads', 'aqop-leads' ); ?>
								</label><br>
								<label>
									<input type="radio" name="export_type" value="filtered">
									<?php esc_html_e( 'Filtered Leads (use filters below)', 'aqop-leads' ); ?>
								</label>
							</td>
						</tr>
						
						<tr class="export-filters" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Status', 'aqop-leads' ); ?></th>
							<td>
								<select name="export_status" class="regular-text">
									<option value=""><?php esc_html_e( 'All Statuses', 'aqop-leads' ); ?></option>
									<?php
									// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
									$statuses = $wpdb->get_results(
										"SELECT status_code, status_name_en 
										 FROM {$wpdb->prefix}aq_leads_status 
										 WHERE is_active = 1
										 ORDER BY status_order ASC"
									);
									foreach ( $statuses as $status ) {
										printf(
											'<option value="%s">%s</option>',
											esc_attr( $status->status_code ),
											esc_html( $status->status_name_en )
										);
									}
									?>
								</select>
							</td>
						</tr>
						
						<tr class="export-filters" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Country', 'aqop-leads' ); ?></th>
							<td>
								<select name="export_country" class="regular-text">
									<option value=""><?php esc_html_e( 'All Countries', 'aqop-leads' ); ?></option>
									<?php
									// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
									$countries = $wpdb->get_results(
										"SELECT id, country_name_en 
										 FROM {$wpdb->prefix}aq_dim_countries 
										 WHERE is_active = 1
										 ORDER BY country_name_en ASC"
									);
									foreach ( $countries as $country ) {
										printf(
											'<option value="%d">%s</option>',
											esc_attr( $country->id ),
											esc_html( $country->country_name_en )
										);
									}
									?>
								</select>
							</td>
						</tr>
						
						<tr class="export-filters" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Priority', 'aqop-leads' ); ?></th>
							<td>
								<select name="export_priority" class="regular-text">
									<option value=""><?php esc_html_e( 'All Priorities', 'aqop-leads' ); ?></option>
									<option value="urgent"><?php esc_html_e( 'Urgent', 'aqop-leads' ); ?></option>
									<option value="high"><?php esc_html_e( 'High', 'aqop-leads' ); ?></option>
									<option value="medium"><?php esc_html_e( 'Medium', 'aqop-leads' ); ?></option>
									<option value="low"><?php esc_html_e( 'Low', 'aqop-leads' ); ?></option>
								</select>
							</td>
						</tr>
						
						<tr class="export-filters" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Date Range', 'aqop-leads' ); ?></th>
							<td>
								<input type="date" name="export_date_from" placeholder="<?php esc_attr_e( 'From', 'aqop-leads' ); ?>" style="margin-right: 10px;">
								<input type="date" name="export_date_to" placeholder="<?php esc_attr_e( 'To', 'aqop-leads' ); ?>">
								<p class="description"><?php esc_html_e( 'Leave empty to export all dates.', 'aqop-leads' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				
				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Download CSV', 'aqop-leads' ); ?>
					</button>
				</p>
			</form>
		</div>
		<!-- === END EXPORT SECTION === -->

		<!-- === IMPORT SECTION (Phase 3.3) === -->
		<div class="card aqop-ie-section aqop-import-section">
			<h2>
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Import Leads', 'aqop-leads' ); ?>
			</h2>
			<p><?php esc_html_e( 'Upload a CSV file to import leads in bulk. Supports duplicate detection and status assignment.', 'aqop-leads' ); ?></p>
			
			<form method="post" enctype="multipart/form-data" class="aqop-import-form">
				<?php wp_nonce_field( 'aqop_import_leads', 'aqop_import_nonce' ); ?>
				<input type="hidden" name="action" value="import_leads">
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="import_file"><?php esc_html_e( 'CSV File', 'aqop-leads' ); ?></label>
							</th>
							<td>
								<input type="file" name="import_file" id="import_file" accept=".csv" required>
								<p class="description">
									<?php esc_html_e( 'Maximum file size: 5MB. File must be in CSV format with UTF-8 encoding.', 'aqop-leads' ); ?>
								</p>
							</td>
						</tr>
						
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Status', 'aqop-leads' ); ?></th>
							<td>
								<select name="import_default_status" class="regular-text">
									<?php
									foreach ( $statuses as $status ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $status->status_code ),
											selected( 'pending', $status->status_code, false ),
											esc_html( $status->status_name_en )
										);
									}
									?>
								</select>
								<p class="description"><?php esc_html_e( 'Used when CSV doesn\'t specify a status.', 'aqop-leads' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row"><?php esc_html_e( 'Duplicate Handling', 'aqop-leads' ); ?></th>
							<td>
								<label>
									<input type="radio" name="duplicate_handling" value="skip" checked>
									<?php esc_html_e( 'Skip duplicates (check by email)', 'aqop-leads' ); ?>
								</label><br>
								<label>
									<input type="radio" name="duplicate_handling" value="update">
									<?php esc_html_e( 'Update existing leads', 'aqop-leads' ); ?>
								</label><br>
								<label>
									<input type="radio" name="duplicate_handling" value="create">
									<?php esc_html_e( 'Create duplicate entries', 'aqop-leads' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Duplicates are detected by matching email addresses.', 'aqop-leads' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				
				<div class="import-template-info">
					<h3>
						<span class="dashicons dashicons-media-spreadsheet"></span>
						<?php esc_html_e( 'CSV Template & Format', 'aqop-leads' ); ?>
					</h3>
					<p><?php esc_html_e( 'Your CSV file should have these columns (column order doesn\'t matter):', 'aqop-leads' ); ?></p>
					<div class="csv-columns">
						<code class="required-col">name</code>
						<code class="required-col">email</code>
						<code class="required-col">phone</code>
						<code>whatsapp</code>
						<code>country_id</code>
						<code>source_id</code>
						<code>campaign_id</code>
						<code>status</code>
						<code>priority</code>
					</div>
					<p class="description">
						<strong><?php esc_html_e( 'Required columns:', 'aqop-leads' ); ?></strong> name, email, phone
					</p>
					<p>
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'aqop-import-export', 'action' => 'download_template' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-media-spreadsheet"></span>
							<?php esc_html_e( 'Download Template CSV', 'aqop-leads' ); ?>
						</a>
					</p>
				</div>
				
				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Upload & Import', 'aqop-leads' ); ?>
					</button>
				</p>
			</form>
		</div>
		<!-- === END IMPORT SECTION === -->

	</div>
</div>

<style>
.aqop-ie-container {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin-top: 20px;
}

.aqop-ie-section {
	padding: 20px;
}

.aqop-ie-section h2 {
	margin-top: 0;
	display: flex;
	align-items: center;
	gap: 8px;
	color: #1d2327;
}

.import-template-info {
	background: #f6f7f7;
	padding: 15px;
	border-radius: 4px;
	margin-top: 20px;
	border-left: 4px solid #2271b1;
}

.import-template-info h3 {
	margin-top: 0;
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
}

.csv-columns {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin: 15px 0;
}

.csv-columns code {
	padding: 6px 12px;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	font-size: 12px;
	display: inline-block;
}

.csv-columns code.required-col {
	background: #d4edda;
	border-color: #48bb78;
	color: #155724;
	font-weight: 600;
}

.export-filters {
	background: #f0f6fc;
}

@media (max-width: 1024px) {
	.aqop-ie-container {
		grid-template-columns: 1fr;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Show/hide export filters
	$('input[name="export_type"]').on('change', function() {
		if ($(this).val() === 'filtered') {
			$('.export-filters').fadeIn();
		} else {
			$('.export-filters').fadeOut();
		}
	});
	
	// File upload validation
	$('#import_file').on('change', function() {
		var file = this.files[0];
		if (file) {
			// Check file size (5MB max)
			if (file.size > 5 * 1024 * 1024) {
				alert('<?php echo esc_js( __( 'File is too large. Maximum size is 5MB.', 'aqop-leads' ) ); ?>');
				$(this).val('');
				return;
			}
			
			// Check file extension
			var ext = file.name.split('.').pop().toLowerCase();
			if (ext !== 'csv') {
				alert('<?php echo esc_js( __( 'Please select a CSV file.', 'aqop-leads' ) ); ?>');
				$(this).val('');
				return;
			}
		}
	});
	
	// Confirm import
	$('.aqop-import-form').on('submit', function(e) {
		var fileInput = $('#import_file')[0];
		if (!fileInput.files || !fileInput.files[0]) {
			alert('<?php echo esc_js( __( 'Please select a CSV file to import.', 'aqop-leads' ) ); ?>');
			e.preventDefault();
			return false;
		}
		
		return confirm('<?php echo esc_js( __( 'Are you sure you want to import leads from this file? This action may create or update multiple records.', 'aqop-leads' ) ); ?>');
	});
});
</script>

