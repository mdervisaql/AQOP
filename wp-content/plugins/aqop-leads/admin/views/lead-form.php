<?php
/**
 * Lead Add/Edit Form Template
 *
 * Form for creating new leads or editing existing ones.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'aqop-leads' ) );
}

// Determine if this is add or edit mode
$lead_id = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
$is_edit = $lead_id > 0;

if ( $is_edit ) {
	// Load existing lead
	$lead = AQOP_Leads_Manager::get_lead( $lead_id );
	
	if ( ! $lead ) {
		wp_die(
			esc_html__( 'Lead not found.', 'aqop-leads' ),
			esc_html__( 'Error', 'aqop-leads' ),
			array( 'back_link' => true )
		);
	}
	
	$page_title = __( 'Edit Lead', 'aqop-leads' );
	$button_text = __( 'Update Lead', 'aqop-leads' );
} else {
	$lead = null;
	$page_title = __( 'Add New Lead', 'aqop-leads' );
	$button_text = __( 'Add Lead', 'aqop-leads' );
}

// Get dropdown data
global $wpdb;

// Countries
$countries = $wpdb->get_results(
	"SELECT id, country_code, country_name_en, country_name_ar 
	 FROM {$wpdb->prefix}aq_dim_countries 
	 WHERE is_active = 1 
	 ORDER BY country_name_en ASC"
);

// Statuses
$statuses = $wpdb->get_results(
	"SELECT id, status_code, status_name_en, status_name_ar, color 
	 FROM {$wpdb->prefix}aq_leads_status 
	 WHERE is_active = 1 
	 ORDER BY status_order ASC"
);

// Sources
$sources = $wpdb->get_results(
	"SELECT id, source_code, source_name, source_type 
	 FROM {$wpdb->prefix}aq_leads_sources 
	 WHERE is_active = 1 
	 ORDER BY source_name ASC"
);

// Campaigns
$campaigns = $wpdb->get_results(
	"SELECT id, name, start_date, end_date 
	 FROM {$wpdb->prefix}aq_leads_campaigns 
	 WHERE is_active = 1 
	 ORDER BY name ASC"
);

// Get users with operation roles
$operation_users = get_users(
	array(
		'role__in' => array( 'administrator', 'operation_admin', 'operation_manager' ),
		'orderby'  => 'display_name',
		'order'    => 'ASC',
	)
);

// Parse custom fields if editing
$custom_fields = array();
if ( $is_edit && ! empty( $lead->custom_fields ) ) {
	$custom_fields = is_array( $lead->custom_fields ) ? $lead->custom_fields : json_decode( $lead->custom_fields, true );
	if ( ! is_array( $custom_fields ) ) {
		$custom_fields = array();
	}
}

// Back URL
$back_url = $is_edit 
	? add_query_arg( array( 'page' => 'aqop-leads-view', 'lead_id' => $lead_id ), admin_url( 'admin.php' ) )
	: add_query_arg( array( 'page' => 'aqop-leads' ), admin_url( 'admin.php' ) );
?>

<div class="wrap aqop-lead-form">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-businessman"></span>
		<?php echo esc_html( $page_title ); ?>
	</h1>
	
	<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
		<?php esc_html_e( 'Back', 'aqop-leads' ); ?>
	</a>
	
	<hr class="wp-header-end">
	
	<form method="post" action="" id="lead-form" class="aqop-form">
		<?php wp_nonce_field( 'aqop_save_lead', 'aqop_lead_nonce' ); ?>
		
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="lead_id" value="<?php echo esc_attr( $lead_id ); ?>">
		<?php endif; ?>
		
		<input type="hidden" name="action" value="save_lead">
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				
				<!-- Main Column -->
				<div id="post-body-content">
					
					<!-- Contact Information -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-id"></span>
								<?php esc_html_e( 'Contact Information', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="lead_name">
												<?php esc_html_e( 'Full Name', 'aqop-leads' ); ?>
												<span class="required">*</span>
											</label>
										</th>
										<td>
											<input 
												type="text" 
												id="lead_name" 
												name="lead_name" 
												class="regular-text" 
												value="<?php echo $is_edit ? esc_attr( $lead->name ) : ''; ?>" 
												required
											>
											<p class="description">
												<?php esc_html_e( 'Enter the lead\'s full name.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_email">
												<?php esc_html_e( 'Email', 'aqop-leads' ); ?>
												<span class="required">*</span>
											</label>
										</th>
										<td>
											<input 
												type="email" 
												id="lead_email" 
												name="lead_email" 
												class="regular-text" 
												value="<?php echo $is_edit ? esc_attr( $lead->email ) : ''; ?>" 
												required
											>
											<p class="description">
												<?php esc_html_e( 'Valid email address required.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_phone">
												<?php esc_html_e( 'Phone', 'aqop-leads' ); ?>
												<span class="required">*</span>
											</label>
										</th>
										<td>
											<input 
												type="tel" 
												id="lead_phone" 
												name="lead_phone" 
												class="regular-text" 
												value="<?php echo $is_edit ? esc_attr( $lead->phone ) : ''; ?>" 
												pattern="[+]?[0-9\s\-\(\)]+"
												placeholder="+966 50 123 4567"
												required
											>
											<p class="description">
												<?php esc_html_e( 'Phone number with country code (e.g., +966 50 123 4567).', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_whatsapp">
												<?php esc_html_e( 'WhatsApp', 'aqop-leads' ); ?>
											</label>
										</th>
										<td>
											<input 
												type="tel" 
												id="lead_whatsapp" 
												name="lead_whatsapp" 
												class="regular-text" 
												value="<?php echo $is_edit ? esc_attr( $lead->whatsapp ) : ''; ?>" 
												pattern="[+]?[0-9\s\-\(\)]+"
												placeholder="+966 50 123 4567"
											>
											<p class="description">
												<?php esc_html_e( 'WhatsApp number (leave empty if same as phone).', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					
					<!-- Lead Details -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Lead Details', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="lead_country">
												<?php esc_html_e( 'Country', 'aqop-leads' ); ?>
												<span class="required">*</span>
											</label>
										</th>
										<td>
											<select id="lead_country" name="lead_country" class="regular-text" required>
												<option value=""><?php esc_html_e( 'Select Country', 'aqop-leads' ); ?></option>
												<?php foreach ( $countries as $country ) : ?>
													<?php $selected = ( $is_edit && $lead->country_id == $country->id ) ? 'selected' : ''; ?>
													<option value="<?php echo esc_attr( $country->id ); ?>" <?php echo $selected; ?>>
														<?php echo esc_html( $country->country_name_en ); ?> (<?php echo esc_html( $country->country_name_ar ); ?>)
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_status">
												<?php esc_html_e( 'Status', 'aqop-leads' ); ?>
												<span class="required">*</span>
											</label>
										</th>
										<td>
											<select id="lead_status" name="lead_status" class="regular-text" required>
												<?php foreach ( $statuses as $status ) : ?>
													<?php 
													$selected = $is_edit 
														? ( $lead->status_id == $status->id ? 'selected' : '' ) 
														: ( 'pending' === $status->status_code ? 'selected' : '' );
													?>
													<option value="<?php echo esc_attr( $status->id ); ?>" <?php echo $selected; ?> data-color="<?php echo esc_attr( $status->color ); ?>">
														<?php echo esc_html( $status->status_name_en ); ?> (<?php echo esc_html( $status->status_name_ar ); ?>)
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description">
												<?php esc_html_e( 'Current status of this lead.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_priority">
												<?php esc_html_e( 'Priority', 'aqop-leads' ); ?>
											</label>
										</th>
										<td>
											<select id="lead_priority" name="lead_priority" class="regular-text">
												<?php
												$priorities = array( 'low', 'medium', 'high', 'urgent' );
												$current_priority = $is_edit ? $lead->priority : 'medium';
												
												foreach ( $priorities as $priority ) :
													$selected = ( $current_priority === $priority ) ? 'selected' : '';
													?>
													<option value="<?php echo esc_attr( $priority ); ?>" <?php echo $selected; ?>>
														<?php echo esc_html( ucfirst( $priority ) ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_source">
												<?php esc_html_e( 'Source', 'aqop-leads' ); ?>
											</label>
										</th>
										<td>
											<select id="lead_source" name="lead_source" class="regular-text">
												<option value=""><?php esc_html_e( 'Select Source', 'aqop-leads' ); ?></option>
												<?php foreach ( $sources as $source ) : ?>
													<?php $selected = ( $is_edit && $lead->source_id == $source->id ) ? 'selected' : ''; ?>
													<option value="<?php echo esc_attr( $source->id ); ?>" <?php echo $selected; ?>>
														<?php echo esc_html( $source->source_name ); ?> (<?php echo esc_html( ucfirst( $source->source_type ) ); ?>)
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description">
												<?php esc_html_e( 'How this lead was acquired.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
									
									<tr>
										<th scope="row">
											<label for="lead_campaign">
												<?php esc_html_e( 'Campaign', 'aqop-leads' ); ?>
											</label>
										</th>
										<td>
											<select id="lead_campaign" name="lead_campaign" class="regular-text">
												<option value=""><?php esc_html_e( 'No Campaign', 'aqop-leads' ); ?></option>
												<?php foreach ( $campaigns as $campaign ) : ?>
													<?php $selected = ( $is_edit && $lead->campaign_id == $campaign->id ) ? 'selected' : ''; ?>
													<option value="<?php echo esc_attr( $campaign->id ); ?>" <?php echo $selected; ?>>
														<?php echo esc_html( $campaign->name ); ?>
														<?php if ( $campaign->start_date ) : ?>
															(<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $campaign->start_date ) ) ); ?>)
														<?php endif; ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description">
												<?php esc_html_e( 'Marketing campaign this lead came from.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					
					<!-- Initial Notes (Add Mode Only) -->
					<?php if ( ! $is_edit ) : ?>
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-admin-comments"></span>
								<?php esc_html_e( 'Initial Notes', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="lead_notes">
												<?php esc_html_e( 'Notes', 'aqop-leads' ); ?>
											</label>
										</th>
										<td>
											<textarea 
												id="lead_notes" 
												name="lead_notes" 
												rows="5" 
												class="large-text"
												placeholder="<?php esc_attr_e( 'Add any initial notes about this lead...', 'aqop-leads' ); ?>"
											></textarea>
											<p class="description">
												<?php esc_html_e( 'Optional notes to help remember important details about this lead.', 'aqop-leads' ); ?>
											</p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<?php endif; ?>
					
				</div>
				
				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					
					<!-- Publish Box -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<?php esc_html_e( 'Save', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div id="major-publishing-actions">
									<div id="delete-action">
										<a href="<?php echo esc_url( $back_url ); ?>" class="submitdelete deletion">
											<?php esc_html_e( 'Cancel', 'aqop-leads' ); ?>
										</a>
									</div>
									<div id="publishing-action">
										<button type="submit" name="save_lead" class="button button-primary button-large">
											<span class="dashicons dashicons-saved"></span>
											<?php echo esc_html( $button_text ); ?>
										</button>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Assignment -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-groups"></span>
								<?php esc_html_e( 'Assignment', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<p>
								<label for="lead_assigned_to">
									<?php esc_html_e( 'Assign To', 'aqop-leads' ); ?>
								</label>
								<select id="lead_assigned_to" name="lead_assigned_to" class="widefat">
									<option value=""><?php esc_html_e( 'Not Assigned', 'aqop-leads' ); ?></option>
									<?php foreach ( $operation_users as $user ) : ?>
										<?php $selected = ( $is_edit && $lead->assigned_to == $user->ID ) ? 'selected' : ''; ?>
										<option value="<?php echo esc_attr( $user->ID ); ?>" <?php echo $selected; ?>>
											<?php echo esc_html( $user->display_name ); ?>
											<?php if ( $user->user_email ) : ?>
												(<?php echo esc_html( $user->user_email ); ?>)
											<?php endif; ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>
						</div>
					</div>
					
					<!-- Custom Fields -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-editor-table"></span>
								<?php esc_html_e( 'Custom Fields', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<div id="custom-fields-container">
								<?php if ( ! empty( $custom_fields ) ) : ?>
									<?php foreach ( $custom_fields as $key => $value ) : ?>
										<div class="custom-field-row">
											<input 
												type="text" 
												name="custom_field_key[]" 
												placeholder="<?php esc_attr_e( 'Key', 'aqop-leads' ); ?>" 
												value="<?php echo esc_attr( $key ); ?>"
												class="widefat custom-field-key"
											>
											<input 
												type="text" 
												name="custom_field_value[]" 
												placeholder="<?php esc_attr_e( 'Value', 'aqop-leads' ); ?>" 
												value="<?php echo esc_attr( is_array( $value ) ? implode( ', ', $value ) : $value ); ?>"
												class="widefat custom-field-value"
											>
											<button type="button" class="button remove-custom-field">
												<span class="dashicons dashicons-no"></span>
											</button>
										</div>
									<?php endforeach; ?>
								<?php else : ?>
									<p class="description" id="no-custom-fields-msg">
										<?php esc_html_e( 'No custom fields yet.', 'aqop-leads' ); ?>
									</p>
								<?php endif; ?>
							</div>
							
							<p style="margin-top: 10px;">
								<button type="button" class="button" id="add-custom-field">
									<span class="dashicons dashicons-plus-alt"></span>
									<?php esc_html_e( 'Add Custom Field', 'aqop-leads' ); ?>
								</button>
							</p>
							
							<p class="description">
								<?php esc_html_e( 'Add custom key-value pairs for additional lead information.', 'aqop-leads' ); ?>
							</p>
						</div>
					</div>
					
					<?php if ( $is_edit ) : ?>
					<!-- Lead Info -->
					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span class="dashicons dashicons-info"></span>
								<?php esc_html_e( 'Lead Info', 'aqop-leads' ); ?>
							</h2>
						</div>
						<div class="inside">
							<p>
								<strong><?php esc_html_e( 'Lead ID:', 'aqop-leads' ); ?></strong>
								#<?php echo esc_html( $lead->id ); ?>
							</p>
							<p>
								<strong><?php esc_html_e( 'Created:', 'aqop-leads' ); ?></strong><br>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead->created_at ) ) ); ?>
							</p>
							<?php if ( $lead->updated_at ) : ?>
							<p>
								<strong><?php esc_html_e( 'Last Updated:', 'aqop-leads' ); ?></strong><br>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead->updated_at ) ) ); ?>
							</p>
							<?php endif; ?>
							<?php if ( $lead->airtable_record_id ) : ?>
							<p>
								<strong><?php esc_html_e( 'Airtable ID:', 'aqop-leads' ); ?></strong><br>
								<code><?php echo esc_html( $lead->airtable_record_id ); ?></code>
							</p>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
					
				</div>
				
			</div>
		</div>
	</form>
</div>

<style>
.required { 
	color: #dc3232; 
	font-weight: bold;
}

.custom-field-row { 
	margin-bottom: 10px; 
	display: flex;
	gap: 5px;
	align-items: center;
}

.custom-field-row input.custom-field-key { 
	width: 35%; 
}

.custom-field-row input.custom-field-value { 
	width: 50%; 
}

.custom-field-row .remove-custom-field {
	flex-shrink: 0;
}

.form-table th { 
	width: 200px;
	padding: 20px 10px 20px 0;
}

#poststuff .inside { 
	padding: 12px; 
	margin: 0;
}

.postbox-header h2 {
	display: flex;
	align-items: center;
	gap: 8px;
}

.aqop-lead-form .wp-heading-inline {
	display: flex;
	align-items: center;
	gap: 8px;
}

#publishing-action .button {
	display: flex;
	align-items: center;
	gap: 5px;
	justify-content: center;
}

.status-badge {
	display: inline-block;
	padding: 4px 10px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	color: white;
}

/* Form validation styles */
input:invalid {
	border-color: #dc3232;
}

input:invalid:focus {
	box-shadow: 0 0 2px rgba(220, 50, 50, 0.5);
}

/* Loading state */
.button.loading {
	opacity: 0.7;
	pointer-events: none;
}

.button .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
	'use strict';
	
	/**
	 * Custom Fields Management
	 */
	
	// Add custom field row
	$('#add-custom-field').on('click', function() {
		// Hide "no fields" message
		$('#no-custom-fields-msg').hide();
		
		var row = $('<div class="custom-field-row"></div>');
		
		row.append(
			$('<input>', {
				type: 'text',
				name: 'custom_field_key[]',
				placeholder: '<?php echo esc_js( __( 'Key', 'aqop-leads' ) ); ?>',
				class: 'widefat custom-field-key'
			})
		);
		
		row.append(
			$('<input>', {
				type: 'text',
				name: 'custom_field_value[]',
				placeholder: '<?php echo esc_js( __( 'Value', 'aqop-leads' ) ); ?>',
				class: 'widefat custom-field-value'
			})
		);
		
		var removeBtn = $('<button>', {
			type: 'button',
			class: 'button remove-custom-field',
			html: '<span class="dashicons dashicons-no"></span>'
		});
		
		row.append(removeBtn);
		
		$('#custom-fields-container').append(row);
		
		// Focus on the new key input
		row.find('.custom-field-key').focus();
	});
	
	// Remove custom field row
	$(document).on('click', '.remove-custom-field', function() {
		$(this).closest('.custom-field-row').fadeOut(300, function() {
			$(this).remove();
			
			// Show "no fields" message if no rows left
			if ($('.custom-field-row').length === 0) {
				$('#no-custom-fields-msg').show();
			}
		});
	});
	
	/**
	 * Form Validation
	 */
	$('#lead-form').on('submit', function(e) {
		var isValid = true;
		var errors = [];
		
		// Validate email
		var email = $('#lead_email').val();
		if (email && !isValidEmail(email)) {
			isValid = false;
			errors.push('<?php echo esc_js( __( 'Please enter a valid email address.', 'aqop-leads' ) ); ?>');
			$('#lead_email').addClass('error');
		}
		
		// Validate phone
		var phone = $('#lead_phone').val();
		if (!phone || phone.trim().length < 10) {
			isValid = false;
			errors.push('<?php echo esc_js( __( 'Please enter a valid phone number.', 'aqop-leads' ) ); ?>');
			$('#lead_phone').addClass('error');
		}
		
		// Validate country
		if (!$('#lead_country').val()) {
			isValid = false;
			errors.push('<?php echo esc_js( __( 'Please select a country.', 'aqop-leads' ) ); ?>');
			$('#lead_country').addClass('error');
		}
		
		// Validate status
		if (!$('#lead_status').val()) {
			isValid = false;
			errors.push('<?php echo esc_js( __( 'Please select a status.', 'aqop-leads' ) ); ?>');
			$('#lead_status').addClass('error');
		}
		
		if (!isValid) {
			e.preventDefault();
			alert('<?php echo esc_js( __( 'Please fix the following errors:', 'aqop-leads' ) ); ?>\n\n' + errors.join('\n'));
			
			// Scroll to first error
			$('html, body').animate({
				scrollTop: $('.error').first().offset().top - 100
			}, 500);
			
			return false;
		}
		
		// Show loading state on submit button
		var $button = $(this).find('button[type="submit"]');
		$button.addClass('loading').prop('disabled', true);
		$button.html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js( __( 'Saving...', 'aqop-leads' ) ); ?>');
	});
	
	// Remove error class on input change
	$('input, select, textarea').on('change input', function() {
		$(this).removeClass('error');
	});
	
	/**
	 * Email validation helper
	 */
	function isValidEmail(email) {
		var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}
	
	/**
	 * Auto-format phone input
	 */
	$('#lead_phone, #lead_whatsapp').on('input', function() {
		var value = $(this).val();
		
		// Remove all non-numeric except + and spaces
		value = value.replace(/[^0-9+\s\-\(\)]/g, '');
		
		$(this).val(value);
	});
	
	/**
	 * WhatsApp auto-fill from phone
	 */
	$('#lead_phone').on('blur', function() {
		var phone = $(this).val();
		var whatsapp = $('#lead_whatsapp').val();
		
		// If WhatsApp is empty, suggest using phone number
		if (phone && !whatsapp) {
			if (confirm('<?php echo esc_js( __( 'Use the same number for WhatsApp?', 'aqop-leads' ) ); ?>')) {
				$('#lead_whatsapp').val(phone);
			}
		}
	});
	
	/**
	 * Status color preview
	 */
	$('#lead_status').on('change', function() {
		var color = $(this).find(':selected').data('color');
		if (color) {
			$(this).css('border-left', '4px solid ' + color);
		}
	}).trigger('change');
	
	/**
	 * Prevent accidental navigation
	 */
	var formModified = false;
	
	$('#lead-form input, #lead-form select, #lead-form textarea').on('change', function() {
		formModified = true;
	});
	
	$(window).on('beforeunload', function() {
		if (formModified) {
			return '<?php echo esc_js( __( 'You have unsaved changes. Are you sure you want to leave?', 'aqop-leads' ) ); ?>';
		}
	});
	
	$('#lead-form').on('submit', function() {
		formModified = false; // Don't warn on form submit
	});
});
</script>

