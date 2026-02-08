<?php
/**
 * Lead Detail View Template
 *
 * Displays comprehensive lead information with notes, timeline, and actions.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Get lead ID from URL.
$lead_id = isset($_GET['lead_id']) ? absint($_GET['lead_id']) : 0;

if (!$lead_id) {
	wp_die(
		esc_html__('Invalid lead ID.', 'aqop-leads'),
		esc_html__('Error', 'aqop-leads'),
		array('back_link' => true)
	);
}

// Load handler class.
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-lead-details-handler.php';

// Check if user can view this lead.
if (!AQOP_Lead_Details_Handler::can_user_view_lead(get_current_user_id(), $lead_id)) {
	wp_die(
		esc_html__('You do not have permission to view this lead.', 'aqop-leads'),
		esc_html__('Access Denied', 'aqop-leads'),
		array('response' => 403, 'back_link' => true)
	);
}

// Get lead data with notes.
$lead_data = AQOP_Lead_Details_Handler::get_lead_with_notes($lead_id);

if (is_wp_error($lead_data)) {
	wp_die(
		esc_html($lead_data->get_error_message()),
		esc_html__('Error', 'aqop-leads'),
		array('back_link' => true)
	);
}

// Handle action messages from redirects
if (isset($_GET['message'])) {
	$message_type = sanitize_key($_GET['message']);
	$messages = array(
		'created' => array('success', __('Lead created successfully.', 'aqop-leads')),
		'updated' => array('success', __('Lead updated successfully.', 'aqop-leads')),
		'deleted' => array('success', __('Lead deleted successfully.', 'aqop-leads')),
		'note_added' => array('success', __('Note added successfully.', 'aqop-leads')),
		'note_failed' => array('error', __('Failed to add note.', 'aqop-leads')),
		'sync_success' => array('success', __('Lead synced to Airtable.', 'aqop-leads')),
		'sync_failed' => array('error', __('Failed to sync to Airtable.', 'aqop-leads')),
	);

	if (isset($messages[$message_type])) {
		list($type, $text) = $messages[$message_type];
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr($type),
			esc_html($text)
		);
	}
}

// Format lead data for display.
$formatted = AQOP_Lead_Details_Handler::format_lead_for_display($lead_data['lead']);
$lead = $lead_data['lead'];  // Use raw lead object
$notes = $lead_data['notes'];

/**
 * Safe DateTime formatter.
 *
 * @param string $datetime DateTime string.
 * @param string $format   Format string.
 * @return string Formatted date or fallback.
 */
function aqop_safe_format_date($datetime, $format = 'M j, Y \a\t g:i A')
{
	if (empty($datetime) || '0000-00-00 00:00:00' === $datetime) {
		return esc_html__('Never', 'aqop-leads');
	}

	try {
		$dt = new DateTime($datetime);
		return $dt->format($format);
	} catch (Exception $e) {
		return esc_html__('Invalid date', 'aqop-leads');
	}
}

// Prepare action URLs with nonces.
$edit_url = add_query_arg(
	array(
		'page' => 'aqop-leads-form',
		'lead_id' => $lead_id,
	),
	admin_url('admin.php')
);

// Delete URL with nonce
$delete_url = wp_nonce_url(
	add_query_arg(
		array(
			'page' => 'aqop-leads',
			'action' => 'delete',
			'lead_id' => $lead_id,
		),
		admin_url('admin.php')
	),
	'delete_lead_' . $lead_id
);

$back_url = add_query_arg(array('page' => 'aqop-leads'), admin_url('admin.php'));
?>

<div class="wrap aqop-lead-detail">

	<!-- Page Header -->
	<div class="aqop-lead-header">
		<div class="aqop-lead-header-top">
			<a href="<?php echo esc_url($back_url); ?>" class="page-title-action">
				<span class="dashicons dashicons-arrow-left-alt2"></span>
				<?php esc_html_e('Back to Leads', 'aqop-leads'); ?>
			</a>
		</div>

		<div class="aqop-lead-title-section">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-businessman"></span>
				<?php echo esc_html($lead->name); ?>
			</h1>

			<?php echo wp_kses_post($formatted['status_badge']); ?>

			<div class="aqop-lead-actions">
				<a href="<?php echo esc_url($edit_url); ?>" class="button button-primary">
					<span class="dashicons dashicons-edit"></span>
					<?php esc_html_e('Edit Lead', 'aqop-leads'); ?>
				</a>

				<button type="button" class="button button-link-delete aqop-delete-trigger">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e('Delete', 'aqop-leads'); ?>
				</button>
			</div>
		</div>

		<p class="aqop-lead-meta">
			<?php
			/* translators: 1: Created date, 2: Updated date */
			printf(
				esc_html__('Created %1$s | Last updated %2$s', 'aqop-leads'),
				'<strong>' . esc_html($formatted['created_date']) . '</strong>',
				'<strong>' . esc_html($formatted['updated_date']) . '</strong>'
			);
			?>
		</p>
	</div>

	<hr class="wp-header-end">

	<div class="aqop-lead-content">
		<div class="aqop-lead-main">

			<!-- Lead Score Card -->
			<div class="card aqop-score-card">
				<h2 class="title">
					<span class="dashicons dashicons-performance"></span>
					<?php esc_html_e('Lead Score', 'aqop-leads'); ?>
				</h2>
				<div class="aqop-score-display">
					<div class="score-circle-wrapper">
						<div class="score-circle" data-score="<?php echo absint($lead->lead_score); ?>">
							<span class="score-value"><?php echo absint($lead->lead_score); ?></span>
							<span class="score-label"><?php esc_html_e('Score', 'aqop-leads'); ?></span>
						</div>
					</div>
					<div class="score-details">
						<div class="score-rating">
							<strong><?php esc_html_e('Rating:', 'aqop-leads'); ?></strong>
							<span
								class="rating-badge rating-<?php echo esc_attr(strtolower($lead->lead_rating)); ?>">
								<?php echo esc_html($lead->lead_rating); ?>
							</span>
						</div>
						<p class="score-updated">
							<?php
							/* translators: %s: Date */
							printf(
								esc_html__('Last updated: %s', 'aqop-leads'),
								esc_html(aqop_safe_format_date($lead->score_updated_at))
							);
							?>
						</p>
					</div>
					<div class="score-actions">
						<button type="button" class="button button-secondary" id="recalculate-score"
							data-lead-id="<?php echo esc_attr($lead->id); ?>">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e('Recalculate', 'aqop-leads'); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Contact Information Card -->
			<div class="card">
				<h2 class="title">
					<span class="dashicons dashicons-phone"></span>
					<?php esc_html_e('Contact Information', 'aqop-leads'); ?>
				</h2>
				<table class="form-table">
					<tbody>
						<?php if ($lead->email): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Email', 'aqop-leads'); ?></th>
								<td>
									<?php echo wp_kses_post($formatted['email_link']); ?>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->phone): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Phone', 'aqop-leads'); ?></th>
								<td>
									<?php echo wp_kses_post($formatted['phone_link']); ?>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->whatsapp): ?>
							<tr>
								<th scope="row"><?php esc_html_e('WhatsApp', 'aqop-leads'); ?></th>
								<td>
									<a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $lead->whatsapp)); ?>"
										target="_blank" rel="noopener">
										<span class="dashicons dashicons-whatsapp"></span>
										<?php echo esc_html($lead->whatsapp); ?>
									</a>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->country_name_en): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Country', 'aqop-leads'); ?></th>
								<td>
									<span class="aqop-country">
										<?php echo esc_html($lead->country_name_en); ?>
										<?php if ($lead->country_name_ar): ?>
											<small>(<?php echo esc_html($lead->country_name_ar); ?>)</small>
										<?php endif; ?>
									</span>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->source_name): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Source', 'aqop-leads'); ?></th>
								<td>
									<span class="dashicons dashicons-migrate"></span>
									<?php echo esc_html($lead->source_name); ?>
								</td>
							</tr>
						<?php endif; ?>

						<?php if (!empty($lead->campaign_id)): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Campaign', 'aqop-leads'); ?></th>
								<td>
									<?php
									echo $formatted['campaign_name'] ? $formatted['campaign_name'] : esc_html__('Unknown Campaign', 'aqop-leads');
									?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Lead Details Card -->
			<div class="card">
				<h2 class="title">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e('Lead Details', 'aqop-leads'); ?>
				</h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e('Priority', 'aqop-leads'); ?></th>
							<td>
								<?php
								$priority_colors = array(
									'low' => '#718096',
									'medium' => '#4299e1',
									'high' => '#ed8936',
									'urgent' => '#f56565',
								);
								$priority_color = isset($priority_colors[$lead->priority]) ? $priority_colors[$lead->priority] : '#718096';
								?>
								<span class="aqop-priority-badge"
									style="background-color: <?php echo esc_attr($priority_color); ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
									<?php echo esc_html(ucfirst($lead->priority)); ?>
								</span>
							</td>
						</tr>

						<?php if ($lead->assigned_user_name): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Assigned To', 'aqop-leads'); ?></th>
								<td>
									<?php echo get_avatar($lead->assigned_to, 32); ?>
									<strong><?php echo esc_html($lead->assigned_user_name); ?></strong>
								</td>
							</tr>
						<?php else: ?>
							<tr>
								<th scope="row"><?php esc_html_e('Assigned To', 'aqop-leads'); ?></th>
								<td>
									<em><?php esc_html_e('Not assigned', 'aqop-leads'); ?></em>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->last_contact_at): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Last Contact', 'aqop-leads'); ?></th>
								<td>
									<?php echo esc_html(aqop_safe_format_date($lead->last_contact_at)); ?>
								</td>
							</tr>
						<?php endif; ?>

						<?php if ($lead->airtable_record_id): ?>
							<tr>
								<th scope="row"><?php esc_html_e('Airtable', 'aqop-leads'); ?></th>
								<td>
									<span class="dashicons dashicons-database-view"></span>
									<code><?php echo esc_html($lead->airtable_record_id); ?></code>
									<span class="dashicons dashicons-yes-alt" style="color: #48bb78;"
										title="<?php esc_attr_e('Synced', 'aqop-leads'); ?>"></span>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Notes Card -->
			<div class="card">
				<h2 class="title">
					<span class="dashicons dashicons-admin-comments"></span>
					<?php esc_html_e('Notes & Activity', 'aqop-leads'); ?>
					<span class="count">(<?php echo absint(count($notes)); ?>)</span>
				</h2>

				<div class="aqop-notes-section">
					<!-- Add Note Form -->
					<div class="aqop-add-note">
						<form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
							class="aqop-note-form">
							<?php wp_nonce_field('aqop_add_note', 'aqop_note_nonce'); ?>
							<input type="hidden" name="action" value="aqop_add_note">
							<input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_id); ?>">

							<textarea name="note_text" rows="3" class="large-text"
								placeholder="<?php esc_attr_e('Add a note about this lead...', 'aqop-leads'); ?>"
								required></textarea>

							<p class="submit">
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-plus"></span>
									<?php esc_html_e('Add Note', 'aqop-leads'); ?>
								</button>
							</p>
						</form>
					</div>

					<!-- Notes Timeline -->
					<div class="aqop-notes-timeline">
						<?php if (!empty($notes)): ?>
							<?php foreach ($notes as $note): ?>
								<!-- === NOTES ENHANCEMENT (Phase 1.4) === -->
								<div class="aqop-note-item" data-note-id="<?php echo esc_attr($note->id); ?>">
									<div class="aqop-note-avatar">
										<?php echo get_avatar($note->user_id, 40); ?>
									</div>
									<div class="aqop-note-content">
										<div class="aqop-note-header">
											<strong class="aqop-note-author">
												<?php echo esc_html($note->user_name); ?>
											</strong>
											<span class="aqop-note-time">
												<?php echo esc_html(aqop_safe_format_date($note->created_at)); ?>
											</span>

											<?php if (get_current_user_id() === (int) $note->user_id || current_user_can('manage_options')): ?>
												<div class="aqop-note-actions">
													<button type="button" class="aqop-note-edit-btn"
														data-note-id="<?php echo esc_attr($note->id); ?>"
														title="<?php esc_attr_e('Edit Note', 'aqop-leads'); ?>"
														aria-label="<?php esc_attr_e('Edit Note', 'aqop-leads'); ?>">
														<span class="dashicons dashicons-edit"></span>
													</button>
													<button type="button" class="aqop-note-delete-btn"
														data-note-id="<?php echo esc_attr($note->id); ?>"
														title="<?php esc_attr_e('Delete Note', 'aqop-leads'); ?>"
														aria-label="<?php esc_attr_e('Delete Note', 'aqop-leads'); ?>">
														<span class="dashicons dashicons-trash"></span>
													</button>
												</div>
											<?php endif; ?>
										</div>
										<div class="aqop-note-text"
											data-original-text="<?php echo esc_attr($note->note_text); ?>">
											<?php echo wp_kses_post(nl2br(esc_html($note->note_text))); ?>
										</div>
										<div class="aqop-note-edit-form" style="display: none;">
											<textarea class="aqop-note-edit-textarea"
												rows="3"><?php echo esc_textarea($note->note_text); ?></textarea>
											<div class="aqop-note-edit-actions">
												<button type="button" class="button button-small aqop-note-save-btn">
													<span class="dashicons dashicons-yes"></span>
													<?php esc_html_e('Save', 'aqop-leads'); ?>
												</button>
												<button type="button" class="button button-small aqop-note-cancel-btn">
													<span class="dashicons dashicons-no-alt"></span>
													<?php esc_html_e('Cancel', 'aqop-leads'); ?>
												</button>
											</div>
										</div>
									</div>
								</div>
								<!-- === END NOTES ENHANCEMENT === -->
							<?php endforeach; ?>
						<?php else: ?>
							<p class="description">
								<?php esc_html_e('No notes yet. Add the first note above.', 'aqop-leads'); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Custom Fields Card (if exists) -->
			<?php if (!empty($lead->custom_fields) && is_array($lead->custom_fields)): ?>
				<div class="card">
					<h2 class="title">
						<span class="dashicons dashicons-editor-table"></span>
						<?php esc_html_e('Custom Fields', 'aqop-leads'); ?>
					</h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e('Field', 'aqop-leads'); ?></th>
								<th><?php esc_html_e('Value', 'aqop-leads'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($lead->custom_fields as $field_key => $field_value): ?>
								<tr>
									<td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $field_key))); ?></strong>
									</td>
									<td>
										<?php
										if (is_array($field_value)) {
											echo esc_html(implode(', ', $field_value));
										} else {
											echo esc_html($field_value);
										}
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

		</div>

		<!-- Sidebar -->
		<div class="aqop-lead-sidebar">

			<!-- Quick Stats -->
			<div class="card">
				<h3><?php esc_html_e('Quick Stats', 'aqop-leads'); ?></h3>
				<ul class="aqop-stats-list">
					<li>
						<span class="dashicons dashicons-calendar-alt"></span>
						<strong><?php esc_html_e('Age:', 'aqop-leads'); ?></strong>
						<?php
						try {
							$created = new DateTime($lead->created_at);
							$now = new DateTime();
							$diff = $created->diff($now);
							echo esc_html(sprintf(_n('%d day', '%d days', $diff->days, 'aqop-leads'), $diff->days));
						} catch (Exception $e) {
							esc_html_e('N/A', 'aqop-leads');
						}
						?>
					</li>
					<li>
						<span class="dashicons dashicons-admin-comments"></span>
						<strong><?php esc_html_e('Notes:', 'aqop-leads'); ?></strong>
						<?php echo absint(count($notes)); ?>
					</li>
					<li>
						<span class="dashicons dashicons-id"></span>
						<strong><?php esc_html_e('Lead ID:', 'aqop-leads'); ?></strong>
						#<?php echo absint($lead->id); ?>
					</li>
				</ul>
			</div>

			<!-- Actions -->
			<div class="card">
				<h3><?php esc_html_e('Actions', 'aqop-leads'); ?></h3>
				<div class="aqop-actions-list">
					<a href="<?php echo esc_url($edit_url); ?>" class="button button-secondary button-large"
						style="width: 100%; margin-bottom: 8px;">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e('Edit Lead', 'aqop-leads'); ?>
					</a>

					<?php if (class_exists('AQOP_Integrations_Hub') && $lead->id): ?>
						<button type="button" class="button button-secondary button-large aqop-sync-airtable"
							data-lead-id="<?php echo esc_attr($lead->id); ?>" style="width: 100%; margin-bottom: 8px;">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e('Sync to Airtable', 'aqop-leads'); ?>
						</button>
					<?php endif; ?>

					<button type="button" class="button button-link-delete button-large aqop-delete-trigger"
						style="width: 100%;">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e('Delete Lead', 'aqop-leads'); ?>
					</button>
				</div>
			</div>

		</div>
	</div>

	<!-- === DELETE MODAL (Phase 1.3) === -->
	<div id="delete-lead-modal" class="aqop-modal" style="display: none;">
		<div class="aqop-modal-content">
			<div class="aqop-modal-header">
				<h2>
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e('Delete Lead', 'aqop-leads'); ?>
				</h2>
				<button type="button" class="aqop-modal-close"
					aria-label="<?php esc_attr_e('Close', 'aqop-leads'); ?>">&times;</button>
			</div>
			<div class="aqop-modal-body">
				<p class="warning-message">
					<span class="dashicons dashicons-warning"></span>
					<?php
					printf(
						/* translators: %s: lead name */
						esc_html__('Are you sure you want to permanently delete "%s"?', 'aqop-leads'),
						'<strong>' . esc_html($lead->name) . '</strong>'
					);
					?>
				</p>
				<p><?php esc_html_e('This action cannot be undone. All associated notes will also be deleted.', 'aqop-leads'); ?>
				</p>

				<div class="deletion-summary">
					<h4><?php esc_html_e('What will be deleted:', 'aqop-leads'); ?></h4>
					<ul>
						<li>
							<span class="dashicons dashicons-businessman"></span>
							<?php esc_html_e('Lead contact information and details', 'aqop-leads'); ?>
						</li>
						<li>
							<span class="dashicons dashicons-admin-comments"></span>
							<?php
							$notes_count = count($notes);
							printf(
								/* translators: %d: number of notes */
								esc_html(_n('%d note', '%d notes', $notes_count, 'aqop-leads')),
								$notes_count
							);
							?>
						</li>
						<li>
							<span class="dashicons dashicons-editor-table"></span>
							<?php esc_html_e('Custom fields and metadata', 'aqop-leads'); ?>
						</li>
						<?php if ($lead->airtable_record_id): ?>
							<li>
								<span class="dashicons dashicons-info"></span>
								<?php esc_html_e('Note: Airtable record will NOT be deleted', 'aqop-leads'); ?>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
			<div class="aqop-modal-footer">
				<button type="button" class="button button-secondary aqop-modal-cancel">
					<span class="dashicons dashicons-no"></span>
					<?php esc_html_e('Cancel', 'aqop-leads'); ?>
				</button>
				<a href="<?php echo esc_url($delete_url); ?>" class="button button-primary button-danger">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e('Yes, Delete Permanently', 'aqop-leads'); ?>
				</a>
			</div>
		</div>
	</div>
	<!-- === END DELETE MODAL === -->

</div>