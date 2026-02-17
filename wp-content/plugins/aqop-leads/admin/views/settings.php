<?php
/**
 * Settings Page
 *
 * Manage lead sources, statuses, integrations, and notifications.
 *
 * @package AQOP_Leads
 * @since   1.0.9
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Check permission
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have permission to access this page.', 'aqop-leads'));
}

global $wpdb;

/**
 * ============================================================================
 * HELPER FUNCTIONS - Database Query Optimization
 * ============================================================================
 */

/**
 * Get WordPress lead table field options
 */
function aqop_get_wp_field_options()
{
	global $wpdb;
	static $wp_field_options = null;

	if (null !== $wp_field_options) {
		return $wp_field_options;
	}

	$wp_field_options = array();
	$table_name = $wpdb->prefix . 'aq_leads';
	$columns = $wpdb->get_results("DESCRIBE {$table_name}");

	$skip_fields = array('id', 'created_at', 'updated_at', 'last_contact_at', 'airtable_record_id');

	foreach ($columns as $column) {
		if (in_array($column->Field, $skip_fields, true)) {
			continue;
		}
		$wp_field_options[$column->Field] = ucwords(str_replace('_', ' ', $column->Field));
	}

	return $wp_field_options;
}

/**
 * Get WordPress lead table field details with types
 */
function aqop_get_wp_field_details()
{
	global $wpdb;
	static $wp_field_details = null;

	if (null !== $wp_field_details) {
		return $wp_field_details;
	}

	$wp_field_details = array();
	$table_name = $wpdb->prefix . 'aq_leads';
	$columns = $wpdb->get_results("DESCRIBE {$table_name}");

	$skip_fields = array('id', 'created_at', 'updated_at', 'last_contact_at', 'airtable_record_id');

	foreach ($columns as $column) {
		if (in_array($column->Field, $skip_fields, true)) {
			continue;
		}

		$wp_field_details[] = array(
			'value' => $column->Field,
			'label' => ucwords(str_replace('_', ' ', $column->Field)),
			'type' => $column->Type,
		);
	}

	return $wp_field_details;
}

/**
 * Generate smart default mappings
 */
function aqop_get_smart_default_mappings()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'aq_leads';
	$columns = $wpdb->get_results("DESCRIBE {$table_name}");

	$skip_fields = array('id', 'created_at', 'updated_at', 'last_contact_at', 'airtable_record_id');
	$default_mappings = array();

	foreach ($columns as $column) {
		$field_name = $column->Field;

		if (in_array($field_name, $skip_fields, true)) {
			continue;
		}

		$airtable_field = aqop_suggest_airtable_field_name($field_name);
		$auto_create_fields = array('country_id', 'campaign_id', 'source_id', 'group_id', 'status_id');
		$auto_create = in_array($field_name, $auto_create_fields, true);

		$default_mappings[] = array(
			'airtable_field' => $airtable_field,
			'wp_field' => $field_name,
			'auto_create' => $auto_create,
		);
	}

	return $default_mappings;
}

/**
 * Suggest Airtable field name
 */
function aqop_suggest_airtable_field_name($wp_field)
{
	$field_map = array(
		'name' => 'Name', // ✅ Maps "Name" field to "name" column
		'email' => 'Email',
		'phone' => 'Phone',
		'country_id' => 'Country',
		'campaign_id' => 'Campaign',
		'group_id' => 'Campaign Group',
		'source_id' => 'Source',
		'status_id' => 'Status',
		'priority' => 'Priority',
		'notes' => 'Notes',
		'platform' => 'Platform',
	);

	return isset($field_map[$wp_field])
		? $field_map[$wp_field]
		: ucwords(str_replace('_', ' ', $wp_field));
}

?>
<style>
	.aqop-card {
		background: #fff;
		border: 1px solid #ccd0d4;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		max-width: 100%;
		box-sizing: border-box;
	}

	.aqop-card h2,
	.aqop-card h3 {
		margin-top: 0;
	}

	.aqop-integration-card {
		border-left: 4px solid #2271b1;
	}
</style>

<div class="wrap aqop-settings">
	<h1>
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e('Leads Settings', 'aqop-leads'); ?>
	</h1>

	<p class="description">
		<?php esc_html_e('Configure lead sources, statuses, integrations, and notification preferences.', 'aqop-leads'); ?>
	</p>

	<!-- === SETTINGS PAGE (Phase 4.1) === -->
	<div class="aqop-settings-tabs">
		<!-- === GENERAL SETTINGS SECTION === -->
		<div class="aqop-tab-section">
			<h3 class="aqop-section-title">📊 General Settings</h3>
			<div class="aqop-section-tabs">
				<a href="#sources" class="nav-tab nav-tab-active" data-tab="sources">
					<span class="dashicons dashicons-category"></span>
					<?php esc_html_e('Lead Sources', 'aqop-leads'); ?>
				</a>
				<a href="#statuses" class="nav-tab" data-tab="statuses">
					<span class="dashicons dashicons-flag"></span>
					<?php esc_html_e('Lead Statuses', 'aqop-leads'); ?>
				</a>
				<a href="#countries" class="nav-tab" data-tab="countries">
					<span class="dashicons dashicons-admin-site"></span>
					<?php esc_html_e('Countries', 'aqop-leads'); ?>
				</a>
				<a href="#scoring" class="nav-tab" data-tab="scoring">
					<span class="dashicons dashicons-performance"></span>
					<?php esc_html_e('Lead Scoring', 'aqop-leads'); ?>
				</a>
			</div>
		</div>

		<!-- === INTEGRATIONS SECTION === -->
		<div class="aqop-tab-section">
			<h3 class="aqop-section-title">⚙️ Integrations</h3>
			<div class="aqop-section-tabs">
				<a href="#integrations" class="nav-tab" data-tab="integrations">
					<span class="dashicons dashicons-admin-plugins"></span>
					<?php esc_html_e('Integrations', 'aqop-leads'); ?>
				</a>
				<a href="#notifications" class="nav-tab" data-tab="notifications">
					<span class="dashicons dashicons-email"></span>
					<?php esc_html_e('Notifications', 'aqop-leads'); ?>
				</a>
				<a href="#cors-settings" class="nav-tab" data-tab="cors-settings">
					<span class="dashicons dashicons-admin-site-alt3"></span>
					<?php esc_html_e('CORS Settings', 'aqop-leads'); ?>
				</a>
				<a href="#meta-integration" class="nav-tab" data-tab="meta-integration">
					<span class="dashicons dashicons-facebook"></span>
					<?php esc_html_e('Meta Integration', 'aqop-leads'); ?>
				</a>
				<a href="#airtable-mapping" class="nav-tab" data-tab="airtable-mapping">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e('Airtable Mapping', 'aqop-leads'); ?>
				</a>
				<a href="#facebook-lead-ads" class="nav-tab" data-tab="facebook-lead-ads">
					<span class="dashicons dashicons-facebook-alt"></span>
					<?php esc_html_e('Facebook Lead Ads', 'aqop-leads'); ?>
				</a>
			</div>
		</div>

		<!-- === CAMPAIGNS SECTION === -->
		<div class="aqop-tab-section">
			<h3 class="aqop-section-title">📢 Campaigns</h3>
			<div class="aqop-section-tabs">
				<a href="#campaign-groups" class="nav-tab" data-tab="campaign-groups">
					<span class="dashicons dashicons-category"></span>
					<?php esc_html_e('Campaign Groups', 'aqop-leads'); ?>
				</a>
				<a href="#campaigns" class="nav-tab" data-tab="campaigns">
					<span class="dashicons dashicons-megaphone"></span>
					<?php esc_html_e('Campaigns', 'aqop-leads'); ?>
				</a>
				<a href="#campaign-questions" class="nav-tab" data-tab="campaign-questions">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e('Campaign Questions', 'aqop-leads'); ?>
				</a>
			</div>
		</div>

		<!-- === LEAD SOURCES TAB === -->
		<div id="sources" class="aqop-settings-tab active">
			<div class="aqop-card">
				<h2><?php esc_html_e('Manage Lead Sources', 'aqop-leads'); ?></h2>
				<p><?php esc_html_e('Track where your leads come from. Add custom sources for better attribution and ROI analysis.', 'aqop-leads'); ?>
				</p>
			</div>

			<!-- Add New Source Form -->
			<div class="aqop-card">
				<h3><?php esc_html_e('Add New Source', 'aqop-leads'); ?></h3>
				<form method="post" class="aqop-settings-form">
					<input type="hidden" id="_wpnonce_sources" name="_wpnonce"
						value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
					<?php wp_referer_field(); ?>
					<input type="hidden" name="aqop_settings_action" value="add_source">

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="source_name"><?php esc_html_e('Source Name', 'aqop-leads'); ?> <span
											class="required">*</span></label>
								</th>
								<td>
									<input type="text" id="source_name" name="source_name" required
										placeholder="<?php esc_attr_e('e.g., Facebook Ads, LinkedIn, Referral', 'aqop-leads'); ?>"
										class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="source_type"><?php esc_html_e('Source Type', 'aqop-leads'); ?></label>
								</th>
								<td>
									<select id="source_type" name="source_type" class="regular-text">
										<option value="paid"><?php esc_html_e('Paid', 'aqop-leads'); ?></option>
										<option value="organic"><?php esc_html_e('Organic', 'aqop-leads'); ?></option>
										<option value="referral"><?php esc_html_e('Referral', 'aqop-leads'); ?>
										</option>
										<option value="direct"><?php esc_html_e('Direct', 'aqop-leads'); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label
										for="cost_per_lead"><?php esc_html_e('Cost Per Lead', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="number" id="cost_per_lead" name="cost_per_lead" step="0.01" min="0"
										placeholder="0.00" class="small-text">
									<p class="description">
										<?php esc_html_e('Optional. For ROI tracking.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e('Add Source', 'aqop-leads'); ?>
						</button>
					</p>
				</form>
			</div>

			<!-- Existing Sources Table -->
			<div class="aqop-card">
				<h3><?php esc_html_e('Existing Sources', 'aqop-leads'); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 50px;"><?php esc_html_e('ID', 'aqop-leads'); ?></th>
							<th><?php esc_html_e('Source Name', 'aqop-leads'); ?></th>
							<th><?php esc_html_e('Type', 'aqop-leads'); ?></th>
							<th style="width: 100px;"><?php esc_html_e('Leads Count', 'aqop-leads'); ?></th>
							<th style="width: 80px;"><?php esc_html_e('Status', 'aqop-leads'); ?></th>
							<th style="width: 150px;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						// Query sources with lead counts
						$sources = $wpdb->get_results(
							"SELECT s.*, COUNT(l.id) as lead_count
							 FROM {$wpdb->prefix}aq_leads_sources s
							 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.source_id
							 GROUP BY s.id
							 ORDER BY s.source_name ASC"
						);

						// Check for sources and render table
						if (empty($sources)) {
							echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">' . esc_html__('No sources found. Add your first source above.', 'aqop-leads') . '</td></tr>';
						} else {
							foreach ($sources as $source) {
								$status_badge = $source->is_active
									? '<span class="aqop-badge aqop-badge-success">Active</span>'
									: '<span class="aqop-badge aqop-badge-default">Inactive</span>';

								$source_type_class = 'source-type-' . esc_attr($source->source_type ?? 'unknown');
								$source_type_display = esc_html(ucfirst($source->source_type ?? 'Unknown'));
								$button_text = $source->is_active ? esc_html__('Deactivate', 'aqop-leads') : esc_html__('Activate', 'aqop-leads');

								echo '<tr>';
								echo '<td>' . absint($source->id) . '</td>';
								echo '<td><strong>' . esc_html($source->source_name) . '</strong><br>';
								echo '<small>Code: ' . esc_html($source->source_code) . '</small></td>';
								echo '<td><span class="' . esc_attr($source_type_class) . '">' . esc_html($source_type_display) . '</span></td>';
								echo '<td><strong>' . absint($source->lead_count) . '</strong></td>';
								echo '<td>' . wp_kses_post($status_badge) . '</td>';
								echo '<td>';
								echo '<button class="button button-small toggle-source-status" data-id="' . esc_attr($source->id) . '" data-current="' . esc_attr($source->is_active) . '">';
								echo esc_html($button_text);
								echo '</button>';
								echo '</td>';
								echo '</tr>';
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- === LEAD STATUSES TAB === -->
		<div id="statuses" class="aqop-settings-tab">
			<div class="aqop-card">
				<h2><?php esc_html_e('Manage Lead Statuses', 'aqop-leads'); ?></h2>
				<p><?php esc_html_e('Define the stages of your lead pipeline. Status colors help visualize lead progress.', 'aqop-leads'); ?>
				</p>
			</div>

			<div class="aqop-card">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Status Name', 'aqop-leads'); ?></th>
							<th style="width: 120px;"><?php esc_html_e('Code', 'aqop-leads'); ?></th>
							<th style="width: 150px;"><?php esc_html_e('Color', 'aqop-leads'); ?></th>
							<th style="width: 100px;"><?php esc_html_e('Leads Count', 'aqop-leads'); ?></th>
							<th style="width: 80px;"><?php esc_html_e('Order', 'aqop-leads'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$statuses = $wpdb->get_results(
							"SELECT s.*, COUNT(l.id) as lead_count
							 FROM {$wpdb->prefix}aq_leads_status s
							 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.status_id
							 GROUP BY s.id
							 ORDER BY s.status_order ASC"
						);

						foreach ($statuses as $status) {
							printf(
								'<tr>
									<td>
										<strong>%s</strong><br>
										<small style="color: #646970;">%s</small>
									</td>
									<td><code>%s</code></td>
									<td>
										<span class="aqop-color-badge" style="background-color: %s; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600;">
											%s
										</span>
									</td>
									<td><strong>%d</strong></td>
									<td>%d</td>
								</tr>',
								esc_html($status->status_name_en),
								esc_html($status->status_name_ar),
								esc_html($status->status_code),
								esc_attr($status->color),
								esc_html($status->status_name_en),
								absint($status->lead_count),
								absint($status->status_order)
							);
						}
						?>
					</tbody>
				</table>
				<p class="description">
					<?php esc_html_e('Note: Status management is currently view-only. Contact administrator to add custom statuses.', 'aqop-leads'); ?>
				</p>
			</div>
		</div>

		<!-- === INTEGRATIONS TAB === -->
		<div id="integrations" class="aqop-settings-tab">
			<div class="aqop-card">
				<h2><?php esc_html_e('External Integrations', 'aqop-leads'); ?></h2>
				<p><?php esc_html_e('Configure external services to sync leads automatically.', 'aqop-leads'); ?></p>
			</div>

			<form method="post">
				<input type="hidden" id="_wpnonce_integrations" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="update_integrations">

				<!-- Airtable Integration -->
				<div class="aqop-card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-database"></span>
						<?php esc_html_e('Airtable', 'aqop-leads'); ?>
					</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="airtable_token"><?php esc_html_e('API Token', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="password" id="airtable_token" name="airtable_token"
										value="<?php echo esc_attr(get_option('aqop_airtable_token', '')); ?>"
										class="large-text code">
									<p class="description">
										<?php esc_html_e('Get your API token from Airtable account settings.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="airtable_base_id"><?php esc_html_e('Base ID', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="airtable_base_id" name="airtable_base_id"
										value="<?php echo esc_attr(get_option('aqop_airtable_base_id', '')); ?>"
										class="regular-text code" placeholder="appXXXXXXXXXXXXXX">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label
										for="airtable_table_name"><?php esc_html_e('Table Name', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="airtable_table_name" name="airtable_table_name"
										value="<?php echo esc_attr(get_option('aqop_airtable_table_name', 'Leads')); ?>"
										class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e('Auto-Sync', 'aqop-leads'); ?></th>
								<td>
									<label>
										<input type="checkbox" name="airtable_auto_sync" value="1" <?php checked(get_option('aqop_airtable_auto_sync'), '1'); ?>>
										<?php esc_html_e('Automatically sync new and updated leads to Airtable', 'aqop-leads'); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Airtable Sync -->
				<div class="aqop-card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e('Airtable Sync', 'aqop-leads'); ?>
					</h3>

					<div style="margin-bottom: 15px;">
						<button type="button" id="airtable-sync-now" class="button button-primary">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e('Sync Now', 'aqop-leads'); ?>
						</button>
						<button type="button" id="airtable-test-sync" class="button button-secondary"
							style="margin-left: 10px;">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e('Test Sync (10 records)', 'aqop-leads'); ?>
						</button>
						<span id="sync-spinner" class="spinner" style="float: none; margin-top: 0;"></span>
					</div>

					<div id="sync-results" style="display: none;">
						<div class="notice notice-success inline">
							<p id="sync-message"></p>
						</div>
					</div>

					<!-- Test Sync Detailed Results -->
					<div id="test-sync-results" style="display: none; margin-top: 15px;">
						<div class="notice notice-info inline" style="padding: 15px;">
							<h4 style="margin-top: 0;">
								<?php esc_html_e('Test Sync Results (10 Records)', 'aqop-leads'); ?>
							</h4>
							<div id="test-sync-details"></div>
						</div>
					</div>

					<?php
					// Show sync statistics
					$sync_stats = get_option('aqop_airtable_sync_stats', array());
					$last_sync = get_option('aqop_airtable_last_sync', '');
					?>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e('Last Sync', 'aqop-leads'); ?></th>
								<td>
									<strong><?php echo esc_html(empty($last_sync) ? 'Never' : date_i18n('M j, Y H:i', strtotime($last_sync))); ?></strong>
								</td>
							</tr>
							<?php if (!empty($sync_stats) && !empty($last_sync)): ?>
								<tr>
									<th scope="row"><?php esc_html_e('Sync Statistics', 'aqop-leads'); ?></th>
									<td>
										<ul style="margin: 0; padding-left: 20px;">
											<li><?php printf(esc_html__('Leads Processed: %d', 'aqop-leads'), intval($sync_stats['leads_processed'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Leads Created: %d', 'aqop-leads'), intval($sync_stats['leads_created'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Leads Updated: %d', 'aqop-leads'), intval($sync_stats['leads_updated'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Countries Created: %d', 'aqop-leads'), intval($sync_stats['countries_created'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Campaigns Created: %d', 'aqop-leads'), intval($sync_stats['campaigns_created'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Groups Created: %d', 'aqop-leads'), intval($sync_stats['groups_created'] ?? 0)); ?>
											</li>
											<li><?php printf(esc_html__('Sources Created: %d', 'aqop-leads'), intval($sync_stats['sources_created'] ?? 0)); ?>
											</li>
										</ul>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<div class="notice notice-info inline">
						<p>
							<strong><?php esc_html_e('Note:', 'aqop-leads'); ?></strong>
							<?php esc_html_e('Sync will create missing countries, campaigns, groups, and sources automatically based on your field mappings.', 'aqop-leads'); ?>
						</p>
					</div>

					<!-- Auto-Sync Settings -->
					<hr style="margin: 25px 0 20px; border: none; border-top: 1px solid #e0e0e0;" />
					<h4 style="margin: 0 0 10px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
						<span class="dashicons dashicons-update-alt"></span>
						<?php esc_html_e('Automatic Sync Settings', 'aqop-leads'); ?>
					</h4>

					<?php
					$auto_sync_enabled = get_option('aqop_airtable_auto_sync_enabled', false);
					$auto_sync_interval = get_option('aqop_airtable_auto_sync_interval', 'every_30_minutes');
					$last_auto_sync = get_option('aqop_airtable_last_auto_sync', '');
					$last_auto_sync_result = get_option('aqop_airtable_last_auto_sync_result', array());
					$next_scheduled = wp_next_scheduled('aqop_airtable_auto_sync_hook');
					?>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e('Enable Auto-Sync', 'aqop-leads'); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aqop_airtable_auto_sync_enabled" value="1"
											id="auto-sync-toggle" <?php checked($auto_sync_enabled, true); ?>>
										<?php esc_html_e('Automatically sync from Airtable on a schedule', 'aqop-leads'); ?>
									</label>
									<p class="description">
										<?php esc_html_e('When enabled, leads will be automatically pulled from Airtable at the selected interval.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr id="auto-sync-interval-row"
								style="<?php echo $auto_sync_enabled ? '' : 'display: none;'; ?>">
								<th scope="row"><?php esc_html_e('Sync Interval', 'aqop-leads'); ?></th>
								<td>
									<select name="aqop_airtable_auto_sync_interval" id="auto-sync-interval">
										<option value="every_15_minutes" <?php selected($auto_sync_interval, 'every_15_minutes'); ?>>
											<?php esc_html_e('Every 15 Minutes', 'aqop-leads'); ?>
										</option>
										<option value="every_30_minutes" <?php selected($auto_sync_interval, 'every_30_minutes'); ?>>
											<?php esc_html_e('Every 30 Minutes', 'aqop-leads'); ?>
										</option>
										<option value="every_hour" <?php selected($auto_sync_interval, 'every_hour'); ?>>
											<?php esc_html_e('Every Hour', 'aqop-leads'); ?>
										</option>
										<option value="every_6_hours" <?php selected($auto_sync_interval, 'every_6_hours'); ?>>
											<?php esc_html_e('Every 6 Hours', 'aqop-leads'); ?>
										</option>
										<option value="daily" <?php selected($auto_sync_interval, 'daily'); ?>>
											<?php esc_html_e('Daily', 'aqop-leads'); ?>
										</option>
									</select>
								</td>
							</tr>
							<!-- Smart Sync Settings -->
							<?php $smart_sync_enabled = get_option('aqop_airtable_smart_sync_enabled', '0'); ?>
							<tr id="smart-sync-row" style="<?php echo $auto_sync_enabled ? '' : 'display: none;'; ?>">
								<th scope="row"><?php esc_html_e('Smart Sync', 'aqop-leads'); ?></th>
								<td>
									<labelstyle="display: flex; align-items: center; gap: 8px;">
										<input type="checkbox" name="aqop_airtable_smart_sync_enabled" value="1"
											id="smart-sync-toggle" <?php checked($smart_sync_enabled, '1'); ?>>
										<span><?php esc_html_e('Only sync new/unsynced records from Airtable', 'aqop-leads'); ?></span>
										</label>
										<p class="description">
											<?php esc_html_e('When enabled, only records with "sync_with_aqop" unchecked in Airtable will be synced. After syncing, the checkbox will be marked in Airtable.', 'aqop-leads'); ?>
										</p>
										<p class="description"
											style="margin-top: 5px; font-size: 12px; color: #d63638;">
											<strong><?php esc_html_e('Important:', 'aqop-leads'); ?></strong>
											<?php esc_html_e('You must create a checkbox field named "sync_with_aqop" in your Airtable table for this feature to work.', 'aqop-leads'); ?>
										</p>
								</td>
							</tr>
							<?php if ($auto_sync_enabled && $next_scheduled): ?>
								<tr>
									<th scope="row"><?php esc_html_e('Next Scheduled Sync', 'aqop-leads'); ?></th>
									<td>
										<strong style="color: #0073aa;">
											<?php echo esc_html(date_i18n('M j, Y H:i:s', $next_scheduled)); ?>
										</strong>
										<span style="color: #666;">
											(<?php printf(esc_html__('in %s', 'aqop-leads'), human_time_diff(time(), $next_scheduled)); ?>)
										</span>
									</td>
								</tr>
							<?php endif; ?>
							<?php if (!empty($last_auto_sync)): ?>
								<tr>
									<th scope="row"><?php esc_html_e('Last Auto-Sync', 'aqop-leads'); ?></th>
									<td>
										<strong><?php echo esc_html(date_i18n('M j, Y H:i:s', strtotime($last_auto_sync))); ?></strong>
										<?php if (!empty($last_auto_sync_result)): ?>
											<?php if (!empty($last_auto_sync_result['success'])): ?>
												<span style="color: #46b450; margin-left: 10px;">
													✅ <?php printf(
														esc_html__('Success: %d processed, %d created, %d updated', 'aqop-leads'),
														intval($last_auto_sync_result['leads_processed'] ?? 0),
														intval($last_auto_sync_result['leads_created'] ?? 0),
														intval($last_auto_sync_result['leads_updated'] ?? 0)
													); ?>
												</span>
											<?php else: ?>
												<span style="color: #dc3232; margin-left: 10px;">
													❌ <?php echo esc_html($last_auto_sync_result['message'] ?? 'Failed'); ?>
												</span>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<p class="submit" style="margin-top: 0;">
						<button type="button" id="save-auto-sync-settings" class="button button-secondary"
							style="margin-right: 15px;">
							<span class="dashicons dashicons-yes"
								style="vertical-align: middle; margin-top: -2px;"></span>
							<?php esc_html_e('Save Auto-Sync Settings', 'aqop-leads'); ?>
						</button>
						<button type="button" id="force-full-sync" class="button button-secondary">
							<span class="dashicons dashicons-update"
								style="vertical-align: middle; margin-top: -2px;"></span>
							<?php esc_html_e('Force Full Sync', 'aqop-leads'); ?>
						</button>
						<span id="auto-sync-save-spinner" class="spinner" style="float: none;"></span>
						<span id="auto-sync-save-message" style="margin-left: 10px;"></span>
					</p>
					<p class="description" style="margin-top: -10px;">
						<?php esc_html_e('Force Full Sync ignores the Smart Sync filter and syncs ALL records from Airtable.', 'aqop-leads'); ?>
					</p>
				</div>

				<!-- Telegram Integration -->
				<div class="aqop-card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-format-chat"></span>
						<?php esc_html_e('Telegram', 'aqop-leads'); ?>
					</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label
										for="telegram_bot_token"><?php esc_html_e('Bot Token', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="telegram_bot_token" name="telegram_bot_token"
										value="<?php echo esc_attr(get_option('aqop_telegram_bot_token', '')); ?>"
										class="large-text code" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
									<p class="description">
										<?php esc_html_e('Get bot token from @BotFather on Telegram.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="telegram_chat_id"><?php esc_html_e('Chat ID', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="telegram_chat_id" name="telegram_chat_id"
										value="<?php echo esc_attr(get_option('aqop_telegram_chat_id', '')); ?>"
										class="regular-text" placeholder="-1001234567890">
									<p class="description">
										<?php esc_html_e('Channel or group chat ID where notifications will be sent.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e('Notifications', 'aqop-leads'); ?></th>
								<td>
									<label>
										<input type="checkbox" name="telegram_notify_new" value="1" <?php checked(get_option('aqop_telegram_notify_new'), '1'); ?>>
										<?php esc_html_e('Send Telegram message when new lead is submitted', 'aqop-leads'); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save Integration Settings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- === WHATSAPP INTEGRATION TAB === -->
		<div id="meta-integration" class="aqop-settings-tab">
			<div class="aqop-card">
				<h2><?php esc_html_e('WhatsApp Business Integration', 'aqop-leads'); ?></h2>
				<p><?php esc_html_e('Connect your WhatsApp Business account to send and receive messages directly.', 'aqop-leads'); ?>
				</p>
			</div>

			<form method="post">
				<input type="hidden" id="_wpnonce_whatsapp" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="update_whatsapp_settings">

				<div class="aqop-card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-whatsapp"></span>
						<?php esc_html_e('WhatsApp Configuration', 'aqop-leads'); ?>
					</h3>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label
										for="whatsapp_phone_number_id"><?php esc_html_e('Phone Number ID', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="whatsapp_phone_number_id"
										name="aqop_whatsapp_phone_number_id"
										value="<?php echo esc_attr(get_option('aqop_whatsapp_phone_number_id', '')); ?>"
										class="regular-text code">
									<p class="description">
										<?php esc_html_e('Found in Meta App Dashboard > WhatsApp > API Setup.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label
										for="whatsapp_business_account_id"><?php esc_html_e('WhatsApp Business Account ID', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="whatsapp_business_account_id"
										name="aqop_whatsapp_business_account_id"
										value="<?php echo esc_attr(get_option('aqop_whatsapp_business_account_id', '')); ?>"
										class="regular-text code">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label
										for="whatsapp_access_token"><?php esc_html_e('Permanent Access Token', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="password" id="whatsapp_access_token" name="aqop_whatsapp_access_token"
										value="<?php echo esc_attr(get_option('aqop_whatsapp_access_token', '')); ?>"
										class="large-text code">
									<p class="description">
										<?php esc_html_e('System User Access Token with "whatsapp_business_messaging" permission.', 'aqop-leads'); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Webhook Configuration', 'aqop-leads'); ?></label>
								</th>
								<td>
									<p>
										<strong><?php esc_html_e('Callback URL:', 'aqop-leads'); ?></strong><br>
										<code>https://operation.aqleeat.co/wp-json/aqop/v1/whatsapp/webhook</code>
									</p>
									<p>
										<strong><?php esc_html_e('Verify Token:', 'aqop-leads'); ?></strong><br>
										<code><?php echo esc_html(get_option('aqop_whatsapp_webhook_token', wp_generate_password(32, false))); ?></code>
									</p>
									<?php if (!get_option('aqop_whatsapp_webhook_token')):
										update_option('aqop_whatsapp_webhook_token', wp_generate_password(32, false));
									endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e('Status', 'aqop-leads'); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aqop_whatsapp_enabled" value="1" <?php checked(get_option('aqop_whatsapp_enabled'), '1'); ?>>
										<?php esc_html_e('Enable WhatsApp Integration', 'aqop-leads'); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>

					<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
						<button type="button" id="test-whatsapp-connection" class="button button-secondary">
							<?php esc_html_e('Test Connection', 'aqop-leads'); ?>
						</button>
						<span id="whatsapp-test-result" style="margin-left: 10px;"></span>
					</div>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save WhatsApp Settings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

	</div>

	<!-- === FACEBOOK LEAD ADS TAB === -->
	<div id="facebook-lead-ads" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Facebook Lead Ads Integration', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Connect your Facebook Lead Ads to automatically sync leads.', 'aqop-leads'); ?></p>
		</div>

		<form method="post">
			<input type="hidden" id="_wpnonce_facebook" name="_wpnonce"
				value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
			<?php wp_referer_field(); ?>
			<input type="hidden" name="aqop_settings_action" value="update_facebook_settings">

			<!-- App Settings (Admin Only) -->
			<?php if (current_user_can('manage_options')): ?>
				<div class="aqop-card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-admin-network"></span>
						<?php esc_html_e('App Configuration', 'aqop-leads'); ?>
					</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="aqop_fb_app_id"><?php esc_html_e('App ID', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="text" id="aqop_fb_app_id" name="aqop_fb_app_id"
										value="<?php echo esc_attr(get_option('aqop_fb_app_id', '')); ?>"
										class="regular-text code">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="aqop_fb_app_secret"><?php esc_html_e('App Secret', 'aqop-leads'); ?></label>
								</th>
								<td>
									<input type="password" id="aqop_fb_app_secret" name="aqop_fb_app_secret"
										value="<?php echo esc_attr(get_option('aqop_fb_app_secret', '')); ?>"
										class="large-text code">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label><?php esc_html_e('Webhook Configuration', 'aqop-leads'); ?></label>
								</th>
								<td>
									<p>
										<strong><?php esc_html_e('Callback URL:', 'aqop-leads'); ?></strong><br>
										<code>https://operation.aqleeat.co/wp-json/aqop/v1/facebook/webhook</code>
									</p>
									<p>
										<strong><?php esc_html_e('Verify Token:', 'aqop-leads'); ?></strong><br>
										<code><?php echo esc_html(get_option('aqop_fb_verify_token', wp_generate_password(32, false))); ?></code>
									</p>
									<?php if (!get_option('aqop_fb_verify_token')):
										update_option('aqop_fb_verify_token', wp_generate_password(32, false));
									endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<div class="aqop-card">
				<h3><?php esc_html_e('Connection & Mapping', 'aqop-leads'); ?></h3>
				<p>
					<?php esc_html_e('To connect your account and map forms, please use the', 'aqop-leads'); ?>
					<a href="/settings/facebook-leads"
						target="_blank"><?php esc_html_e('Facebook Leads Settings Page', 'aqop-leads'); ?></a>
					<?php esc_html_e('in the frontend application.', 'aqop-leads'); ?>
				</p>
			</div>

			<p class="submit">
				<button type="submit" class="button button-primary button-large">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e('Save Facebook Settings', 'aqop-leads'); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- === LEAD SCORING TAB === -->
	<?php include_once AQOP_LEADS_PLUGIN_DIR . 'admin/views/settings-scoring.php'; ?>

	<!-- === NOTIFICATIONS TAB === -->
	<div id="notifications" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Notification Settings', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Configure email notifications for lead events.', 'aqop-leads'); ?></p>
		</div>

		<div class="aqop-card">
			<form method="post">
				<input type="hidden" id="_wpnonce_notifications" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="update_notifications">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label
									for="notification_email"><?php esc_html_e('Notification Email', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="email" id="notification_email" name="notification_email"
									value="<?php echo esc_attr(get_option('aqop_notification_email', get_option('admin_email'))); ?>"
									class="regular-text">
								<p class="description">
									<?php esc_html_e('Receive lead notifications at this email address.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Email Notifications', 'aqop-leads'); ?></th>
							<td>
								<label>
									<input type="checkbox" name="notify_new_lead" value="1" <?php checked(get_option('aqop_notify_new_lead', '1'), '1'); ?>>
									<?php esc_html_e('New lead submitted (via public form or API)', 'aqop-leads'); ?>
								</label><br>
								<label>
									<input type="checkbox" name="notify_status_change" value="1" <?php checked(get_option('aqop_notify_status_change'), '1'); ?>>
									<?php esc_html_e('Lead status changed to "Converted"', 'aqop-leads'); ?>
								</label><br>
								<label>
									<input type="checkbox" name="notify_assignment" value="1" <?php checked(get_option('aqop_notify_assignment'), '1'); ?>>
									<?php esc_html_e('Lead assigned to user', 'aqop-leads'); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save Notification Settings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>
	</div>

	<!-- === CAMPAIGN QUESTIONS TAB === -->
	<div id="campaign-questions" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Campaign Questions Management', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Configure custom questions for each campaign to map Meta Lead Ads answers.', 'aqop-leads'); ?>
			</p>
		</div>

		<?php
		// Get all campaigns
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaigns = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}aq_leads_campaigns 
				 WHERE is_active = 1 
				 ORDER BY name ASC"
		);

		// Get all campaign questions from options
		$all_questions = get_option('aqop_campaign_questions', array());

		if (empty($campaigns)) {
			echo '<div class="aqop-card">';
			echo '<p>' . esc_html__('No campaigns found. Create a campaign first before adding questions.', 'aqop-leads') . '</p>';
			echo '</div>';
		} else {
			foreach ($campaigns as $campaign) {
				$campaign_id = 'campaign_' . $campaign->id;
				$questions = isset($all_questions[$campaign_id]) ? $all_questions[$campaign_id] : array();
				?>
				<div class="aqop-card campaign-questions-card" data-campaign-id="<?php echo esc_attr($campaign_id); ?>">
					<h3 class="campaign-title">
						<span class="dashicons dashicons-megaphone"></span>
						<?php echo esc_html($campaign->name); ?>
						<span class="campaign-id-badge">#<?php echo esc_html($campaign->id); ?></span>
					</h3>

					<!-- Questions List -->
					<div class="questions-list">
						<?php if (empty($questions)): ?>
							<p class="no-questions"><?php esc_html_e('No questions added yet.', 'aqop-leads'); ?></p>
						<?php else: ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th style="width: 80px;"><?php esc_html_e('Question ID', 'aqop-leads'); ?></th>
										<th><?php esc_html_e('Question Text', 'aqop-leads'); ?></th>
										<th style="width: 120px;"><?php esc_html_e('Field Type', 'aqop-leads'); ?></th>
										<th style="width: 150px;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($questions as $q_id => $question): ?>
										<tr class="question-row" data-question-id="<?php echo esc_attr($q_id); ?>">
											<td><code><?php echo esc_html($q_id); ?></code></td>
											<td dir="auto"><?php echo esc_html($question['text']); ?></td>
											<td>
												<span class="field-type-badge field-type-<?php echo esc_attr($question['type']); ?>">
													<?php echo esc_html(ucfirst($question['type'])); ?>
												</span>
											</td>
											<td>
												<button type="button" class="button button-small edit-question"
													data-campaign="<?php echo esc_attr($campaign_id); ?>"
													data-qid="<?php echo esc_attr($q_id); ?>"
													data-text="<?php echo esc_attr($question['text']); ?>"
													data-type="<?php echo esc_attr($question['type']); ?>">
													<span class="dashicons dashicons-edit"></span>
													<?php esc_html_e('Edit', 'aqop-leads'); ?>
												</button>
												<button type="button" class="button button-small button-link-delete delete-question"
													data-campaign="<?php echo esc_attr($campaign_id); ?>"
													data-qid="<?php echo esc_attr($q_id); ?>">
													<span class="dashicons dashicons-trash"></span>
													<?php esc_html_e('Delete', 'aqop-leads'); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<!-- Add Question Form -->
					<div class="add-question-form" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dcdcde;">
						<h4><?php esc_html_e('Add New Question', 'aqop-leads'); ?></h4>
						<table class="form-table">
							<tbody>
								<tr>
									<th style="width: 150px;">
										<label><?php esc_html_e('Question ID', 'aqop-leads'); ?></label>
									</th>
									<td>
										<input type="text" class="new-question-id regular-text"
											placeholder="<?php esc_attr_e('e.g., q1, q2, q3', 'aqop-leads'); ?>"
											pattern="q[0-9]+"
											title="<?php esc_attr_e('Format: q1, q2, q3, etc.', 'aqop-leads'); ?>">
										<p class="description">
											<?php esc_html_e('Format: q1, q2, q3, etc.', 'aqop-leads'); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th>
										<label><?php esc_html_e('Question Text', 'aqop-leads'); ?></label>
									</th>
									<td>
										<input type="text" class="new-question-text large-text" dir="auto"
											placeholder="<?php esc_attr_e('ما مستواك التعليمي؟', 'aqop-leads'); ?>">
										<p class="description">
											<?php esc_html_e('Supports Arabic and English.', 'aqop-leads'); ?>
										</p>
									</td>
								</tr>
								<tr>
									<th>
										<label><?php esc_html_e('Field Type', 'aqop-leads'); ?></label>
									</th>
									<td>
										<select class="new-question-type regular-text">
											<option value="text"><?php esc_html_e('Text', 'aqop-leads'); ?></option>
											<option value="select"><?php esc_html_e('Select (Dropdown)', 'aqop-leads'); ?>
											</option>
											<option value="radio"><?php esc_html_e('Radio', 'aqop-leads'); ?></option>
											<option value="checkbox"><?php esc_html_e('Checkbox', 'aqop-leads'); ?>
											</option>
											<option value="textarea"><?php esc_html_e('Textarea', 'aqop-leads'); ?>
											</option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
						<button type="button" class="button button-secondary add-question-btn"
							data-campaign="<?php echo esc_attr($campaign_id); ?>">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e('Add Question', 'aqop-leads'); ?>
						</button>
					</div>
				</div>
				<?php
			}
		}
		?>

		<!-- Add Campaign Button -->
		<div class="aqop-card" style="text-align: center; padding: 30px;">
			<p class="description">
				<?php esc_html_e('Don\'t see your campaign? Add campaigns in the database or contact your administrator.', 'aqop-leads'); ?>
			</p>
		</div>
	</div>

	<!-- === CORS SETTINGS TAB === -->
	<div id="cors-settings" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('CORS (Cross-Origin Resource Sharing) Settings', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Configure allowed origins for the React frontend and external API access.', 'aqop-leads'); ?>
			</p>
		</div>

		<div class="aqop-card">
			<form method="post">
				<input type="hidden" id="_wpnonce_cors" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="update_cors">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label
									for="jwt_allowed_origins"><?php esc_html_e('Allowed Origins', 'aqop-leads'); ?></label>
							</th>
							<td>
								<textarea id="jwt_allowed_origins" name="jwt_allowed_origins" rows="8"
									class="large-text code"
									placeholder="https://app.yourdomain.com&#10;https://dashboard.yourdomain.com&#10;https://staging.yourdomain.com"><?php echo esc_textarea(get_option('aqop_jwt_allowed_origins', '')); ?></textarea>
								<p class="description">
									<?php esc_html_e('Enter one origin per line. Example:', 'aqop-leads'); ?><br>
									<code>https://app.yourdomain.com</code><br>
									<code>https://dashboard.yourdomain.com</code>
								</p>
								<div class="notice notice-info inline" style="margin-top: 10px;">
									<p>
										<strong><?php esc_html_e('Default Origins (Always Allowed):', 'aqop-leads'); ?></strong><br>
										• http://localhost:5173<br>
										• http://localhost:5174<br>
										• http://localhost:3000
									</p>
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Current Request Origin', 'aqop-leads'); ?></th>
							<td>
								<code
									style="padding: 8px; background: #f0f0f1; display: inline-block; border-radius: 4px;">
										<?php
										$current_origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_html(sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN']))) : __('None (Direct access)', 'aqop-leads');
										echo $current_origin;
										?>
									</code>
								<p class="description">
									<?php esc_html_e('This is the origin of the current request. Use this to verify your frontend URL.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('CORS Status', 'aqop-leads'); ?></th>
							<td>
								<?php
								$allowed_origins = aqop_jwt_get_allowed_origins();
								$current_origin = isset($_SERVER['HTTP_ORIGIN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';

								if (empty($current_origin)) {
									echo '<span class="aqop-badge" style="background: #f0f0f1; color: #646970;">' . esc_html__('No Origin Detected', 'aqop-leads') . '</span>';
								} elseif (in_array($current_origin, $allowed_origins, true)) {
									echo '<span class="aqop-badge aqop-badge-success">✓ ' . esc_html__('Origin Allowed', 'aqop-leads') . '</span>';
								} else {
									echo '<span class="aqop-badge" style="background: #f8d7da; color: #721c24;">✗ ' . esc_html__('Origin Blocked', 'aqop-leads') . '</span>';
								}
								?>
								<p class="description">
									<?php esc_html_e('Shows if the current request origin is allowed.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save CORS Settings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>

			<!-- Help Section -->
			<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f1;">
				<h3><?php esc_html_e('Quick Setup Guide', 'aqop-leads'); ?></h3>
				<ol style="line-height: 1.8;">
					<li>
						<strong><?php esc_html_e('Development:', 'aqop-leads'); ?></strong><br>
						<?php esc_html_e('Default origins (localhost:5173, 5174, 3000) are always allowed. No configuration needed.', 'aqop-leads'); ?>
					</li>
					<li>
						<strong><?php esc_html_e('Production:', 'aqop-leads'); ?></strong><br>
						<?php esc_html_e('Add your production domain(s) above. Example:', 'aqop-leads'); ?>
						<code>https://app.yourdomain.com</code>
					</li>
					<li>
						<strong><?php esc_html_e('Multiple Domains:', 'aqop-leads'); ?></strong><br>
						<?php esc_html_e('Enter each domain on a new line (one per line).', 'aqop-leads'); ?>
					</li>
					<li>
						<strong><?php esc_html_e('Testing:', 'aqop-leads'); ?></strong><br>
						<?php esc_html_e('After saving, refresh your React app and check if API calls work.', 'aqop-leads'); ?>
					</li>
				</ol>

				<div class="notice notice-warning inline" style="margin-top: 15px;">
					<p>
						<strong><?php esc_html_e('Security Note:', 'aqop-leads'); ?></strong><br>
						<?php esc_html_e('Always use HTTPS in production. Only add trusted domains to prevent security risks.', 'aqop-leads'); ?>
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- === META INTEGRATION TAB === -->
	<div id="meta-integration" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Meta (Facebook) Lead Ads Integration', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Connect Meta Lead Ads to automatically receive leads from Facebook and Instagram campaigns.', 'aqop-leads'); ?>
			</p>
		</div>

		<!-- Connection Status -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Connection Status', 'aqop-leads'); ?></h3>
			<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
				<?php
				$verify_token = get_option('aqop_meta_verify_token', '');
				$app_secret = get_option('aqop_meta_app_secret', '');
				$webhook_url = get_rest_url(null, 'aqop/v1/meta/webhook');

				$status_color = (!empty($verify_token) && !empty($app_secret)) ? '#28a745' : '#ffc107';
				$status_text = (!empty($verify_token) && !empty($app_secret)) ? esc_html__('Ready', 'aqop-leads') : esc_html__('Not Configured', 'aqop-leads');
				$status_icon = (!empty($verify_token) && !empty($app_secret)) ? '✓' : '⚠';
				?>
				<span
					style="color: <?php echo esc_attr($status_color); ?>; font-size: 18px;"><?php echo esc_html($status_icon); ?></span>
				<strong
					style="color: <?php echo esc_attr($status_color); ?>;"><?php echo esc_html($status_text); ?></strong>
			</div>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e('Webhook URL', 'aqop-leads'); ?></th>
						<td>
							<div style="display: flex; align-items: center; gap: 10px;">
								<code
									style="padding: 8px; background: #f0f0f1; display: inline-block; border-radius: 4px; flex: 1;">
										<?php echo esc_url($webhook_url); ?>
									</code>
								<button type="button" class="button"
									onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_url); ?>').then(() => alert('<?php esc_html_e('URL copied to clipboard!', 'aqop-leads'); ?>'))">
									<?php esc_html_e('Copy', 'aqop-leads'); ?>
								</button>
							</div>
							<p class="description">
								<?php esc_html_e('Use this URL when setting up your Meta webhook.', 'aqop-leads'); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Configuration Settings -->
		<div class="aqop-card">
			<form method="post">
				<input type="hidden" id="_wpnonce_meta" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="update_meta_integration">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label
									for="meta_verify_token"><?php esc_html_e('Verify Token', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="text" id="meta_verify_token" name="meta_verify_token"
									value="<?php echo esc_attr(get_option('aqop_meta_verify_token', '')); ?>"
									class="regular-text"
									placeholder="<?php esc_attr_e('Enter your verify token', 'aqop-leads'); ?>" />
								<p class="description">
									<?php esc_html_e('Token used to verify webhook subscription from Meta.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="meta_app_secret"><?php esc_html_e('App Secret', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="password" id="meta_app_secret" name="meta_app_secret"
									value="<?php echo esc_attr(get_option('aqop_meta_app_secret', '')); ?>"
									class="regular-text"
									placeholder="<?php esc_attr_e('Enter your app secret', 'aqop-leads'); ?>" />
								<p class="description">
									<?php esc_html_e('Secret key for webhook signature verification.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save Meta Settings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- Test Webhook -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Test Webhook', 'aqop-leads'); ?></h3>
			<p><?php esc_html_e('Send a test webhook to verify your configuration is working.', 'aqop-leads'); ?>
			</p>

			<button type="button" id="test-meta-webhook" class="button button-secondary">
				<span class="dashicons dashicons-share"></span>
				<?php esc_html_e('Send Test Webhook', 'aqop-leads'); ?>
			</button>

			<div id="test-webhook-result" style="margin-top: 10px;"></div>
		</div>

		<!-- Recent Webhooks Log -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Recent Webhook Activity', 'aqop-leads'); ?></h3>
			<p><?php esc_html_e('Last 10 webhook events for debugging.', 'aqop-leads'); ?></p>

			<?php
			require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-meta-webhook-api.php';
			$logs = AQOP_Meta_Webhook_API::get_webhook_logs(10);

			if (empty($logs)) {
				echo '<p class="description">' . esc_html__('No webhook activity yet.', 'aqop-leads') . '</p>';
			} else {
				echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">';
				echo '<table class="widefat striped" style="border: none; margin: 0;">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="padding: 8px;">' . esc_html__('Time', 'aqop-leads') . '</th>';
				echo '<th style="padding: 8px;">' . esc_html__('Event', 'aqop-leads') . '</th>';
				echo '<th style="padding: 8px;">' . esc_html__('Details', 'aqop-leads') . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';

				foreach ($logs as $log) {
					$event_color = 'inherit';
					if (strpos($log['event'], 'error') !== false) {
						$event_color = '#dc3545';
					} elseif (strpos($log['event'], 'success') !== false || strpos($log['event'], 'created') !== false) {
						$event_color = '#28a745';
					}

					echo '<tr>';
					echo '<td style="padding: 8px; font-size: 12px;">' . esc_html(date_i18n('M j, H:i:s', strtotime($log['timestamp']))) . '</td>';
					echo '<td style="padding: 8px; font-weight: bold; color: ' . esc_attr($event_color) . ';">' . esc_html(ucfirst(str_replace('_', ' ', $log['event']))) . '</td>';
					echo '<td style="padding: 8px; font-size: 12px;">';

					if (!empty($log['data'])) {
						$details = $log['data'];
						if (isset($details['leads_created']) && $details['leads_created'] > 0) {
							echo '<strong style="color: #28a745;">✓ ' . esc_html($details['leads_created']) . ' leads created</strong>';
						} elseif (isset($details['error'])) {
							echo '<span style="color: #dc3545;">✗ ' . esc_html(substr($details['error'], 0, 100)) . '</span>';
						} elseif (isset($details['name'])) {
							echo esc_html('Lead: ' . $details['name']);
						} else {
							echo esc_html(substr(wp_json_encode($details), 0, 100) . '...');
						}
					} else {
						echo '<em>' . esc_html__('No details', 'aqop-leads') . '</em>';
					}

					echo '</td>';
					echo '</tr>';
				}

				echo '</tbody>';
				echo '</table>';
				echo '</div>';
			}
			?>
		</div>

		<!-- Help Section -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Setup Guide', 'aqop-leads'); ?></h3>
			<ol style="line-height: 1.8;">
				<li>
					<strong><?php esc_html_e('Create Meta App:', 'aqop-leads'); ?></strong><br>
					<?php esc_html_e('Go to Meta for Developers and create a new Business app.', 'aqop-leads'); ?>
				</li>
				<li>
					<strong><?php esc_html_e('Add Webhooks Product:', 'aqop-leads'); ?></strong><br>
					<?php esc_html_e('Add the Webhooks product to your app.', 'aqop-leads'); ?>
				</li>
				<li>
					<strong><?php esc_html_e('Configure Webhook:', 'aqop-leads'); ?></strong><br>
					<?php esc_html_e('Set the URL above and subscribe to "leadgen" events.', 'aqop-leads'); ?>
				</li>
				<li>
					<strong><?php esc_html_e('Create Lead Ads:', 'aqop-leads'); ?></strong><br>
					<?php esc_html_e('Create Facebook/Instagram lead ads with custom questions.', 'aqop-leads'); ?>
				</li>
			</ol>

			<div class="notice notice-info inline" style="margin-top: 15px;">
				<p>
					<strong><?php esc_html_e('Documentation:', 'aqop-leads'); ?></strong>
					<a href="#"
						target="_blank"><?php esc_html_e('Meta Lead Ads Integration Guide', 'aqop-leads'); ?></a>
				</p>
			</div>
		</div>
	</div>

	<!-- === COUNTRIES MANAGEMENT TAB === -->
	<div id="countries" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Manage Countries', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Manage countries for lead forms and filtering.', 'aqop-leads'); ?></p>
		</div>

		<!-- Add New Country Form -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Add New Country', 'aqop-leads'); ?></h3>
			<form method="post" class="aqop-settings-form">
				<input type="hidden" id="_wpnonce_countries" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="add_country">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label
									for="country_name_en"><?php esc_html_e('Country Name (English)', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="country_name_en" name="country_name_en" required
									placeholder="<?php esc_attr_e('e.g., United States', 'aqop-leads'); ?>"
									class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="country_name_ar"><?php esc_html_e('Country Name (Arabic)', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="country_name_ar" name="country_name_ar" required
									placeholder="<?php esc_attr_e('e.g., الولايات المتحدة', 'aqop-leads'); ?>"
									class="regular-text" dir="rtl">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="country_code"><?php esc_html_e('Country Code (ISO 2-letter)', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="country_code" name="country_code" required maxlength="2"
									placeholder="<?php esc_attr_e('e.g., US', 'aqop-leads'); ?>" class="small-text"
									style="text-transform: uppercase;">
								<p class="description">
									<?php esc_html_e('ISO 3166-1 alpha-2 country code.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e('Add Country', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- Existing Countries Table -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Existing Countries', 'aqop-leads'); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e('ID', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Name (EN)', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Name (AR)', 'aqop-leads'); ?></th>
						<th style="width: 80px;"><?php esc_html_e('Code', 'aqop-leads'); ?></th>
						<th style="width: 100px;"><?php esc_html_e('Leads Count', 'aqop-leads'); ?></th>
						<th style="width: 150px;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Query countries with lead counts
					$countries = $wpdb->get_results(
						"SELECT c.*, COUNT(l.id) as lead_count
							 FROM {$wpdb->prefix}aq_dim_countries c
							 LEFT JOIN {$wpdb->prefix}aq_leads l ON c.id = l.country_id
							 GROUP BY c.id
							 ORDER BY c.country_name_en ASC"
					);

					// Check for countries and render table
					if (empty($countries)) {
						echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">' . esc_html__('No countries found. Add your first country above.', 'aqop-leads') . '</td></tr>';
					} else {
						foreach ($countries as $country) {
							echo '<tr>';
							echo '<td>' . absint($country->id) . '</td>';
							echo '<td>' . esc_html($country->country_name_en) . '</td>';
							echo '<td dir="rtl">' . esc_html($country->country_name_ar) . '</td>';
							echo '<td><code>' . esc_html($country->country_code) . '</code></td>';
							echo '<td><strong>' . absint($country->lead_count) . '</strong></td>';
							echo '<td>';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Edit', 'aqop-leads');
							echo '</button> ';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Delete', 'aqop-leads');
							echo '</button>';
							echo '</td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- === CAMPAIGN GROUPS MANAGEMENT TAB === -->
	<div id="campaign-groups" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Manage Campaign Groups', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Organize campaigns into groups for better management and reporting.', 'aqop-leads'); ?>
			</p>
		</div>

		<!-- Add New Campaign Group Form -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Add New Campaign Group', 'aqop-leads'); ?></h3>
			<form method="post" class="aqop-settings-form">
				<input type="hidden" id="_wpnonce_campaign_groups" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="add_campaign_group">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="group_name_en"><?php esc_html_e('Group Name (English)', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="group_name_en" name="group_name_en" required
									placeholder="<?php esc_attr_e('e.g., Q4 2024 Marketing Campaigns', 'aqop-leads'); ?>"
									class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="group_name_ar"><?php esc_html_e('Group Name (Arabic)', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="group_name_ar" name="group_name_ar" required
									placeholder="<?php esc_attr_e('e.g., حملات التسويق ربع السنوية الرابعة 2024', 'aqop-leads'); ?>"
									class="regular-text" dir="rtl">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="group_description"><?php esc_html_e('Description', 'aqop-leads'); ?></label>
							</th>
							<td>
								<textarea id="group_description" name="group_description" rows="3"
									placeholder="<?php esc_attr_e('Optional description of this campaign group...', 'aqop-leads'); ?>"
									class="large-text"></textarea>
								<p class="description">
									<?php esc_html_e('Provide additional context about this group of campaigns.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e('Add Campaign Group', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- Existing Campaign Groups Table -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Existing Campaign Groups', 'aqop-leads'); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e('ID', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Name (EN)', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Name (AR)', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Campaigns', 'aqop-leads'); ?></th>
						<th style="width: 120px;"><?php esc_html_e('Created', 'aqop-leads'); ?></th>
						<th style="width: 150px;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Query campaign groups with campaign counts
					$campaign_groups = $wpdb->get_results(
						"SELECT g.*, COUNT(c.id) as campaigns_count
							 FROM {$wpdb->prefix}aq_campaign_groups g
							 LEFT JOIN {$wpdb->prefix}aq_leads_campaigns c ON g.id = c.group_id
							 GROUP BY g.id
							 ORDER BY g.created_at DESC"
					);

					// Check for campaign groups and render table
					if (empty($campaign_groups)) {
						echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">' . esc_html__('No campaign groups found. Add your first group above.', 'aqop-leads') . '</td></tr>';
					} else {
						foreach ($campaign_groups as $group) {
							$created_date = date_i18n('M j, Y', strtotime($group->created_at));

							echo '<tr>';
							echo '<td>' . absint($group->id) . '</td>';
							echo '<td>';
							echo '<strong>' . esc_html($group->group_name_en) . '</strong>';
							if (!empty($group->description)) {
								echo '<br><small>' . esc_html(wp_trim_words($group->description, 10)) . '</small>';
							}
							echo '</td>';
							echo '<td dir="rtl">' . esc_html($group->group_name_ar) . '</td>';
							echo '<td><strong>' . absint($group->campaigns_count) . '</strong></td>';
							echo '<td>' . esc_html($created_date) . '</td>';
							echo '<td>';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Edit', 'aqop-leads');
							echo '</button> ';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Delete', 'aqop-leads');
							echo '</button>';
							echo '</td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- === CAMPAIGNS MANAGEMENT TAB === -->
	<div id="campaigns" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Manage Campaigns', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Create and manage your marketing campaigns. Group campaigns for better organization and reporting.', 'aqop-leads'); ?>
			</p>
		</div>

		<!-- Add New Campaign Form -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Add New Campaign', 'aqop-leads'); ?></h3>
			<form method="post" class="aqop-settings-form">
				<input type="hidden" id="_wpnonce_campaigns" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="add_campaign">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="campaign_name"><?php esc_html_e('Campaign Name', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="campaign_name" name="campaign_name" required
									placeholder="<?php esc_attr_e('e.g., Facebook Q4 2024 Campaign', 'aqop-leads'); ?>"
									class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="campaign_group"><?php esc_html_e('Campaign Group', 'aqop-leads'); ?></label>
							</th>
							<td>
								<select id="campaign_group" name="campaign_group" class="regular-text">
									<option value=""><?php esc_html_e('No Group', 'aqop-leads'); ?></option>
									<?php
									$groups = $wpdb->get_results("SELECT id, group_name_en FROM {$wpdb->prefix}aq_campaign_groups ORDER BY group_name_en ASC");
									foreach ($groups as $group) {
										echo '<option value="' . esc_attr($group->id) . '">' . esc_html($group->group_name_en) . '</option>';
									}
									?>
								</select>
								<p class="description">
									<?php esc_html_e('Optional. Assign this campaign to a group for better organization.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="campaign_platform"><?php esc_html_e('Platform', 'aqop-leads'); ?></label>
							</th>
							<td>
								<select id="campaign_platform" name="campaign_platform" class="regular-text">
									<option value=""><?php esc_html_e('Select Platform', 'aqop-leads'); ?>
									</option>
									<option value="facebook">Facebook</option>
									<option value="instagram">Instagram</option>
									<option value="google">Google Ads</option>
									<option value="linkedin">LinkedIn</option>
									<option value="twitter">Twitter/X</option>
									<option value="tiktok">TikTok</option>
									<option value="snapchat">Snapchat</option>
									<option value="email">Email</option>
									<option value="sms">SMS</option>
									<option value="offline">Offline</option>
									<option value="other">Other</option>
								</select>
								<p class="description">
									<?php esc_html_e('Optional. Specify the marketing platform for this campaign.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="start_date"><?php esc_html_e('Start Date', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="date" id="start_date" name="start_date" class="regular-text">
								<p class="description">
									<?php esc_html_e('When does this campaign start?', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="end_date"><?php esc_html_e('End Date', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="date" id="end_date" name="end_date" class="regular-text">
								<p class="description">
									<?php esc_html_e('When does this campaign end?', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="campaign_budget"><?php esc_html_e('Budget', 'aqop-leads'); ?></label>
							</th>
							<td>
								<input type="number" id="campaign_budget" name="campaign_budget" step="0.01" min="0"
									placeholder="0.00" class="regular-text">
								<p class="description">
									<?php esc_html_e('Campaign budget for ROI tracking (optional).', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="campaign_description"><?php esc_html_e('Description', 'aqop-leads'); ?></label>
							</th>
							<td>
								<textarea id="campaign_description" name="campaign_description" rows="3"
									placeholder="<?php esc_attr_e('Optional description of this campaign...', 'aqop-leads'); ?>"
									class="large-text"></textarea>
								<p class="description">
									<?php esc_html_e('Provide additional details about this campaign.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="campaign_status"><?php esc_html_e('Status', 'aqop-leads'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="campaign_status" name="campaign_status" value="1"
										checked>
									<?php esc_html_e('Active', 'aqop-leads'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Uncheck to mark this campaign as inactive.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e('Add Campaign', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- Existing Campaigns Table -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Existing Campaigns', 'aqop-leads'); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e('ID', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Name', 'aqop-leads'); ?></th>
						<th><?php esc_html_e('Group', 'aqop-leads'); ?></th>
						<th style="width: 90px;"><?php esc_html_e('Platform', 'aqop-leads'); ?></th>
						<th style="width: 100px;"><?php esc_html_e('Start Date', 'aqop-leads'); ?></th>
						<th style="width: 100px;"><?php esc_html_e('End Date', 'aqop-leads'); ?></th>
						<th style="width: 100px;"><?php esc_html_e('Budget', 'aqop-leads'); ?></th>
						<th style="width: 100px;"><?php esc_html_e('Leads', 'aqop-leads'); ?></th>
						<th style="width: 80px;"><?php esc_html_e('Status', 'aqop-leads'); ?></th>
						<th style="width: 150px;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Query campaigns with group names and lead counts
					$campaigns = $wpdb->get_results(
						"SELECT c.*, g.group_name_en, COUNT(l.id) as leads_count
							 FROM {$wpdb->prefix}aq_leads_campaigns c
							 LEFT JOIN {$wpdb->prefix}aq_campaign_groups g ON c.group_id = g.id
							 LEFT JOIN {$wpdb->prefix}aq_leads l ON l.campaign_id = c.id
							 GROUP BY c.id
							 ORDER BY c.created_at DESC"
					);

					// Check for campaigns and render table
					if (empty($campaigns)) {
						echo '<tr><td colspan="9" style="text-align: center; padding: 20px;">' . esc_html__('No campaigns found. Add your first campaign above.', 'aqop-leads') . '</td></tr>';
					} else {
						foreach ($campaigns as $campaign) {
							$status_badge = $campaign->is_active
								? '<span class="aqop-badge aqop-badge-success">Active</span>'
								: '<span class="aqop-badge aqop-badge-default">Inactive</span>';

							$start_date = $campaign->start_date ? date_i18n('M j, Y', strtotime($campaign->start_date)) : '—';
							$end_date = $campaign->end_date ? date_i18n('M j, Y', strtotime($campaign->end_date)) : '—';
							$budget = $campaign->budget ? '$' . number_format($campaign->budget, 2) : '—';
							$group_name = $campaign->group_name_en ?: 'No Group';

							// Platform badge with color
							$platform_display = '—';
							if (!empty($campaign->platform)) {
								$platform_class = 'platform-' . esc_attr($campaign->platform);
								$platform_display = '<span class="aqop-platform-badge ' . esc_attr($platform_class) . '">' . esc_html(ucfirst($campaign->platform)) . '</span>';
							}

							echo '<tr>';
							echo '<td>' . absint($campaign->id) . '</td>';
							echo '<td>';
							echo '<strong>' . esc_html($campaign->name) . '</strong>';
							if (!empty($campaign->description)) {
								echo '<br><small>' . esc_html(wp_trim_words($campaign->description, 8)) . '</small>';
							}
							echo '</td>';
							echo '<td>' . esc_html($group_name) . '</td>';
							echo '<td>' . wp_kses_post($platform_display) . '</td>';
							echo '<td>' . esc_html($start_date) . '</td>';
							echo '<td>' . esc_html($end_date) . '</td>';
							echo '<td>' . esc_html($budget) . '</td>';
							echo '<td><strong>' . absint($campaign->leads_count) . '</strong></td>';
							echo '<td>' . wp_kses_post($status_badge) . '</td>';
							echo '<td>';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Edit', 'aqop-leads');
							echo '</button> ';
							echo '<button class="button button-small" disabled>';
							echo esc_html__('Delete', 'aqop-leads');
							echo '</button>';
							echo '</td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- === AIRTABLE MAPPING TAB === -->
	<div id="airtable-mapping" class="aqop-settings-tab">
		<div class="aqop-card">
			<h2><?php esc_html_e('Airtable Field Mapping', 'aqop-leads'); ?></h2>
			<p><?php esc_html_e('Map Airtable columns to WordPress lead fields for automatic import. Configure how your Airtable data should be imported into the leads system.', 'aqop-leads'); ?>
			</p>
		</div>

		<!-- Current Field Mappings -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Current Field Mappings', 'aqop-leads'); ?></h3>
			<form method="post" class="aqop-settings-form">
				<input type="hidden" id="_wpnonce_airtable_mapping" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="save_airtable_mappings">

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 25%;"><?php esc_html_e('Airtable Field', 'aqop-leads'); ?></th>
							<th style="width: 25%;"><?php esc_html_e('WordPress Field', 'aqop-leads'); ?></th>
							<th style="width: 15%;"><?php esc_html_e('Auto-Create', 'aqop-leads'); ?></th>
							<th style="width: 20%;"><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
						</tr>
					</thead>
					<tbody id="airtable-mappings-list">
						<?php
						// Get saved mappings or use smart defaults
						$saved_mappings = get_option('aqop_airtable_field_mapping', array());

						// Fix: Handle if stored as JSON string instead of array
						if (is_string($saved_mappings) && !empty($saved_mappings)) {
							$decoded = json_decode($saved_mappings, true);
							if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
								$saved_mappings = $decoded;
								// Also fix the stored value
								update_option('aqop_airtable_field_mapping', $saved_mappings);
							} else {
								$saved_mappings = array();
							}
						}

						// ✅ Use smart default mappings only if no saved mappings exist
						if (empty($saved_mappings) || !is_array($saved_mappings)) {
							$mappings = aqop_get_smart_default_mappings();
						} else {
							$mappings = $saved_mappings;
						}

						// ✅ Query executed ONCE before loop
						$wp_field_options = aqop_get_wp_field_options();

						foreach ($mappings as $index => $mapping):
							?>
							<tr>
								<td>
									<input type="text" name="mappings[<?php echo esc_attr($index); ?>][airtable_field]"
										value="<?php echo esc_attr($mapping['airtable_field']); ?>" class="regular-text"
										required>
								</td>
								<td>
									<select name="mappings[<?php echo esc_attr($index); ?>][wp_field]" class="regular-text"
										required>
										<?php foreach ($wp_field_options as $value => $label): ?>
											<option value="<?php echo esc_attr($value); ?>" <?php selected($mapping['wp_field'], $value); ?>>
												<?php echo esc_html($label); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<input type="checkbox" name="mappings[<?php echo esc_attr($index); ?>][auto_create]"
										value="1" <?php checked($mapping['auto_create'], true); ?>>
								</td>
								<td>
									<button type="button" class="button button-small button-link-delete remove-mapping"
										data-index="<?php echo esc_attr($index); ?>">
										<span class="dashicons dashicons-trash"></span>
										<?php esc_html_e('Remove', 'aqop-leads'); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit" style="margin-top: 20px;">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e('Save Field Mappings', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- Fetch Airtable Fields -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Fetch Airtable Fields', 'aqop-leads'); ?></h3>

			<div style="margin-bottom: 15px;">
				<button type="button" id="fetch-airtable-fields" class="button button-secondary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e('Fetch Fields from Airtable', 'aqop-leads'); ?>
				</button>
				<span id="fetch-spinner" class="spinner" style="float: none; margin-top: 0;"></span>
			</div>

			<div id="fetch-results" style="display: none;">
				<div class="notice notice-info inline">
					<p id="fetch-message"></p>
				</div>
			</div>

			<div id="airtable-fields-list" style="display: none;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
					<h4 style="margin: 0;">
						<?php esc_html_e('Available Fields with AI Suggestions:', 'aqop-leads'); ?>
					</h4>
					<span class="aqop-ai-powered">🤖 AI-Powered Matching</span>
				</div>
				<div id="fields-container"
					style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px; background: #fafafa;">
					<!-- Fields will be populated here -->
				</div>
				<div style="text-align: right;">
					<button type="button" id="apply-all-suggestions" class="button button-primary">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e('Apply All Suggestions', 'aqop-leads'); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Smart Field Matching -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Smart Field Matching', 'aqop-leads'); ?></h3>
			<p><?php esc_html_e('Use AI-powered matching to automatically suggest field mappings between Airtable and WordPress.', 'aqop-leads'); ?>
			</p>

			<div style="margin-bottom: 15px;">
				<button type="button" id="auto-match-fields" class="button button-primary">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e('🤖 Smart Match All Fields', 'aqop-leads'); ?>
				</button>
				<span id="match-spinner" class="spinner" style="float: none; margin-top: 0;"></span>
			</div>

			<div id="match-results" style="display: none;">
				<div class="notice notice-info inline">
					<p id="match-message"></p>
				</div>
			</div>

			<div id="mapping-suggestions" style="display: none;">
				<h4><?php esc_html_e('Suggested Mappings:', 'aqop-leads'); ?></h4>
				<div id="suggestions-container"
					style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 15px; background: #fafafa;">
					<!-- Suggestions will be populated here -->
				</div>
				<div style="text-align: right;">
					<button type="button" id="apply-suggestions" class="button button-primary">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e('Apply All Suggestions', 'aqop-leads'); ?>
					</button>
					<button type="button" id="cancel-suggestions" class="button">
						<?php esc_html_e('Cancel', 'aqop-leads'); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Add New Mapping -->
		<div class="aqop-card">
			<h3><?php esc_html_e('Add New Field Mapping', 'aqop-leads'); ?></h3>
			<form method="post" class="aqop-settings-form" id="add-mapping-form">
				<input type="hidden" id="_wpnonce_add_mapping" name="_wpnonce"
					value="<?php echo esc_attr(wp_create_nonce('aqop_settings_save')); ?>">
				<?php wp_referer_field(); ?>
				<input type="hidden" name="aqop_settings_action" value="add_airtable_mapping">

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="new_airtable_field"><?php esc_html_e('Airtable Field', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<select id="new_airtable_field" name="airtable_field" class="regular-text" required>
									<option value="">
										<?php esc_html_e('Select Airtable Field (fetch fields first)', 'aqop-leads'); ?>
									</option>
								</select>
								<p class="description">
									<?php esc_html_e('First click "Fetch Fields from Airtable" to populate this dropdown.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="new_wp_field"><?php esc_html_e('Maps to WordPress Field', 'aqop-leads'); ?>
									<span class="required">*</span></label>
							</th>
							<td>
								<select id="new_wp_field" name="wp_field" class="regular-text" required>
									<option value=""><?php esc_html_e('Select WordPress Field', 'aqop-leads'); ?>
									</option>
									<?php
									// ✅ Use helper function
									$wp_fields = aqop_get_wp_field_details();
									foreach ($wp_fields as $field):
										?>
										<option value="<?php echo esc_attr($field['value']); ?>"
											data-type="<?php echo esc_attr($field['type']); ?>">
											<?php echo esc_html($field['label']); ?>
										</option>
										<?php
									endforeach;
									?>
								</select>
								<p class="description">
									<?php esc_html_e('Choose which WordPress lead field this Airtable column should map to.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label
									for="new_auto_create"><?php esc_html_e('Auto-Create Missing Items', 'aqop-leads'); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="new_auto_create" name="auto_create" value="1">
									<?php esc_html_e('Automatically create missing countries, campaigns, sources, or groups', 'aqop-leads'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('For country_id, campaign_id, group_id, and source_id fields only. Creates new items if they don\'t exist.', 'aqop-leads'); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e('Add Field Mapping', 'aqop-leads'); ?>
					</button>
				</p>
			</form>
		</div>
	</div>

</div>
<!-- === END SETTINGS PAGE === -->

<script>
	jQuery(document).ready(function ($) {
		// Tab switching
		$('.nav-tab').on('click', function (e) {
			e.preventDefault();

			// Remove active class from all tabs and sections
			$('.nav-tab').removeClass('nav-tab-active');
			$('.aqop-settings-tab').removeClass('active');

			// Add active class to clicked tab
			$(this).addClass('nav-tab-active');

			// Show corresponding section
			var tabId = $(this).attr('data-tab');
			$('#' + tabId).addClass('active');

			// Update URL hash
			window.location.hash = tabId;
		});

		// Handle hash on page load
		if (window.location.hash) {
			var hash = window.location.hash.substring(1);
			$('.nav-tab[data-tab="' + hash + '"]').click();
		}

		// Airtable mapping functionality
		$('.remove-mapping').on('click', function () {
			if (confirm('Are you sure you want to remove this field mapping?')) {
				$(this).closest('tr').remove();
			}
		});

		// Reset add mapping form after successful submission
		$('#add-mapping-form').on('submit', function () {
			// Form will be submitted normally, but we'll reset it if successful
			// The page will reload with the new mapping added
		});

		// Airtable sync functionality - CHUNKED to avoid Cloudflare timeout
		$('#airtable-sync-now').on('click', function () {
			var $button = $(this);
			var $spinner = $('#sync-spinner');
			var $results = $('#sync-results');
			var $message = $('#sync-message');
			var chunkNumber = 0;
			var startTime = new Date();

			// Disable button and show spinner
			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$results.hide();

			// Show progress UI with live log
			var progressHtml = '<div id="sync-progress-container" style="margin: 10px 0;">';
			progressHtml += '<p id="sync-progress-text" style="font-weight: bold; margin-bottom: 10px;">🚀 Starting sync...</p>';
			progressHtml += '<div style="background: #e0e0e0; border-radius: 4px; height: 20px; width: 100%; max-width: 500px; margin-bottom: 10px;">';
			progressHtml += '<div id="sync-progress-bar" style="background: linear-gradient(90deg, #0073aa, #00a0d2); height: 100%; width: 0%; border-radius: 4px; transition: width 0.3s;"></div>';
			progressHtml += '</div>';
			progressHtml += '<p id="sync-progress-stats" style="font-size: 13px; color: #333; margin-bottom: 15px; font-weight: 500;">📊 Processed: 0 | Created: 0 | Updated: 0</p>';

			// Live Activity Log
			progressHtml += '<div style="border: 1px solid #ccd0d4; border-radius: 4px; background: #1e1e1e; max-width: 600px;">';
			progressHtml += '<div style="background: #23282d; color: #fff; padding: 8px 12px; border-radius: 4px 4px 0 0; font-size: 12px; font-weight: 600;">';
			progressHtml += '📋 Live Activity Log</div>';
			progressHtml += '<div id="sync-activity-log" style="height: 200px; overflow-y: auto; padding: 10px; font-family: monospace; font-size: 11px; color: #0f0; line-height: 1.6;"></div>';
			progressHtml += '</div>';
			progressHtml += '</div>';
			$message.html(progressHtml);
			$results.removeClass('notice-error notice-success').show();

			// Helper function to add log entry
			function addLogEntry(message, type) {
				var $log = $('#sync-activity-log');
				var timestamp = new Date().toLocaleTimeString('en-US', { hour12: false });
				var color = '#0f0'; // green
				var icon = '✓';

				if (type === 'error') {
					color = '#f55';
					icon = '✖';
				} else if (type === 'info') {
					color = '#0af';
					icon = 'ℹ';
				} else if (type === 'warning') {
					color = '#fa0';
					icon = '⚠';
				} else if (type === 'start') {
					color = '#fff';
					icon = '▶';
				}

				var entry = '<div style="color: ' + color + ';">[' + timestamp + '] ' + icon + ' ' + message + '</div>';
				$log.append(entry);
				$log.scrollTop($log[0].scrollHeight);
			}

			addLogEntry('Initializing Airtable sync...', 'start');
			addLogEntry('Connecting to Airtable API...', 'info');

			// Chunked sync function
			function syncChunk(offset) {
				chunkNumber++;
				var chunkStart = new Date();

				addLogEntry('Fetching chunk #' + chunkNumber + (offset ? ' (offset: ' + offset.substring(0, 10) + '...)' : ' (first batch)'), 'info');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					timeout: 90000, // 90 second timeout per chunk (below Cloudflare's 100s)
					data: {
						action: 'aqop_sync_airtable_chunk',
						nonce: '<?php echo esc_js(wp_create_nonce("aqop_airtable_sync")); ?>',
						offset: offset || ''
					},
					success: function (response) {
						var chunkTime = ((new Date() - chunkStart) / 1000).toFixed(1);

						if (response.success) {
							var data = response.data;
							var cumulative = data.cumulative || {};

							// Log chunk results
							addLogEntry('Chunk #' + chunkNumber + ' completed in ' + chunkTime + 's', 'success');
							addLogEntry('  → Processed: ' + (data.chunk_processed || 0) + ' records', 'info');

							if (data.chunk_created > 0) {
								addLogEntry('  → Created: ' + data.chunk_created + ' new leads', 'success');
							}
							if (data.chunk_updated > 0) {
								addLogEntry('  → Updated: ' + data.chunk_updated + ' existing leads', 'success');
							}
							if (data.chunk_processed > 0 && data.chunk_created === 0 && data.chunk_updated === 0) {
								addLogEntry('  → All records already up to date', 'info');
							}

							// Update progress display
							$('#sync-progress-text').html('⏳ Processed ' + (data.chunk_processed || 0) + ' records (total: ' + cumulative.leads_processed + '). ' + (data.is_complete ? 'Done!' : 'Continuing...'));
							$('#sync-progress-stats').html(
								'📊 Processed: <strong>' + (cumulative.leads_processed || 0) + '</strong>' +
								' | Created: <strong style="color: #46b450;">' + (cumulative.leads_created || 0) + '</strong>' +
								' | Updated: <strong style="color: #0073aa;">' + (cumulative.leads_updated || 0) + '</strong>'
							);

							// Animate progress bar (estimate based on typical Airtable limits)
							if (!data.is_complete && data.next_offset) {
								// Estimate progress - each chunk is ~50 records
								var estimatedProgress = Math.min(95, cumulative.leads_processed * 0.5);
								$('#sync-progress-bar').css('width', estimatedProgress + '%');
							}

							if (data.is_complete) {
								var totalTime = ((new Date() - startTime) / 1000).toFixed(1);

								// All done!
								$('#sync-progress-bar').css('width', '100%');
								$('#sync-progress-text').html('<strong style="color: #46b450;">✅ Sync completed successfully!</strong>');
								$results.addClass('notice-success');

								addLogEntry('────────────────────────────────', 'info');
								addLogEntry('SYNC COMPLETED in ' + totalTime + ' seconds', 'success');
								addLogEntry('Total chunks processed: ' + chunkNumber, 'info');
								addLogEntry('Total leads processed: ' + cumulative.leads_processed, 'success');
								addLogEntry('Total leads created: ' + cumulative.leads_created, 'success');
								addLogEntry('Total leads updated: ' + cumulative.leads_updated, 'success');
								addLogEntry('Page will reload in 3 seconds...', 'info');

								// Re-enable button and hide spinner
								$button.prop('disabled', false);
								$spinner.removeClass('is-active');

								// Reload after short delay to show updated stats
								setTimeout(function () {
									location.reload();
								}, 3000);
							} else {
								// Continue with next chunk
								addLogEntry('Waiting 500ms before next chunk...', 'info');
								setTimeout(function () {
									syncChunk(data.next_offset);
								}, 500);
							}
						} else {
							// Error
							addLogEntry('ERROR: ' + (response.data || 'Unknown error'), 'error');
							$message.prepend('<p style="color: red; font-weight: bold;">❌ Sync Error: ' + (response.data || 'Unknown error') + '</p>');
							$results.removeClass('notice-success').addClass('notice-error');
							$button.prop('disabled', false);
							$spinner.removeClass('is-active');
						}
					},
					error: function (xhr, status, error) {
						var errorMsg = error;
						if (status === 'timeout') {
							errorMsg = 'Request timed out after 90 seconds. The server may be overloaded.';
						}
						addLogEntry('AJAX ERROR: ' + errorMsg, 'error');
						addLogEntry('Status: ' + status, 'error');
						$message.prepend('<p style="color: red; font-weight: bold;">❌ AJAX Error: ' + errorMsg + '</p>');
						$results.removeClass('notice-success').addClass('notice-error');
						$button.prop('disabled', false);
						$spinner.removeClass('is-active');
					}
				});
			}

			// Start first chunk
			syncChunk('');
		});

		// Test Sync (10 records) functionality
		$('#test-sync-btn').on('click', function () {
			var $button = $(this);
			var $results = $('#test-sync-results');

			// Disable button and show loading
			$button.prop('disabled', true).text('Testing...');
			$results.html('<p><span class="spinner is-active" style="float:none;margin:0;"></span> Fetching 10 records from Airtable...</p>').show();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_test_sync_airtable',
					nonce: '<?php echo esc_js(wp_create_nonce("aqop_airtable_sync")); ?>'
				},
				success: function (response) {
					if (response.success) {
						var stats = response.data.stats;
						var samples = response.data.sample_records;

						var html = '<div class="notice notice-success" style="padding:10px;margin:0;">';
						html += '<h4 style="margin:0 0 10px;">✅ Test Sync Results</h4>';
						html += '<table class="widefat" style="width:auto;"><tbody>';
						html += '<tr><td><strong>Records Fetched:</strong></td><td>' + stats.records_fetched + '</td></tr>';
						html += '<tr><td><strong>With Name:</strong></td><td>' + stats.records_with_name + '</td></tr>';
						html += '<tr><td><strong>With Email:</strong></td><td>' + stats.records_with_email + '</td></tr>';
						html += '<tr><td><strong>With Phone:</strong></td><td>' + stats.records_with_phone + '</td></tr>';
						html += '<tr><td><strong>With Country:</strong></td><td>' + stats.records_with_country + '</td></tr>';
						html += '<tr><td><strong>With Campaign:</strong></td><td>' + stats.records_with_campaign + '</td></tr>';
						html += '</tbody></table>';

						// Show sample records
						if (samples && samples.length > 0) {
							html += '<h4 style="margin:15px 0 5px;">Sample Records (first 5):</h4>';
							html += '<table class="widefat striped" style="font-size:12px;"><thead><tr>';
							html += '<th>Airtable ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Country</th><th>Campaign</th>';
							html += '</tr></thead><tbody>';
							for (var i = 0; i < samples.length; i++) {
								var rec = samples[i];
								html += '<tr>';
								html += '<td><code>' + (rec.airtable_id || '-').substring(0, 15) + '...</code></td>';
								html += '<td>' + (rec.name || '<em>empty</em>') + '</td>';
								html += '<td>' + (rec.email || '<em>empty</em>') + '</td>';
								html += '<td>' + (rec.phone || '<em>empty</em>') + '</td>';
								html += '<td>' + (rec.country || '<em>empty</em>') + '</td>';
								html += '<td>' + (rec.campaign || '<em>empty</em>') + '</td>';
								html += '</tr>';
							}
							html += '</tbody></table>';
						}

						html += '</div>';
						$results.html(html);
					} else {
						$results.html('<div class="notice notice-error" style="padding:10px;margin:0;">❌ ' + (response.data || 'Unknown error') + '</div>');
					}
				},
				error: function (xhr, status, error) {
					$results.html('<div class="notice notice-error" style="padding:10px;margin:0;">❌ AJAX Error: ' + error + '</div>');
				},
				complete: function () {
					$button.prop('disabled', false).text('Test Sync (10 records)');
				}
			});
		});

		// Intelligent field mapping suggestions
		function suggestFieldMapping(airtableFieldName, airtableFieldType) {
			const fieldLower = airtableFieldName.toLowerCase().replace(/[^a-z0-9]/g, '');

			const wpFields = {
				'name': ['name', 'fullname', 'leadname', 'firstname', 'lastname', 'client', 'customer', 'contact', 'person'],
				'email': ['email', 'mail', 'emailaddress', 'e-mail', 'contactemail'],
				'phone': ['phone', 'mobile', 'telephone', 'tel', 'phonenumber', 'cell', 'whatsapp', 'contactphone'],
				'country_id': ['country', 'countryname', 'nation', 'location', 'state', 'region'],
				'campaign_id': ['campaign', 'campaignname', 'campaigntitle', 'adcampaign', 'ads', 'marketing'],
				'group_id': ['group', 'campaigngroup', 'groupname', 'category', 'segment'],
				'source_id': ['source', 'leadsource', 'referral', 'channel', 'medium', 'origin', 'referer'],
				'status_id': ['status', 'leadstatus', 'stage', 'phase', 'progress', 'state'],
				'priority': ['priority', 'importance', 'urgency', 'level', 'rank'],
				'notes': ['notes', 'comments', 'description', 'remarks', 'details', 'memo', 'message', 'feedback']
			};

			// Smart matching with scoring
			let bestMatch = null;
			let highestScore = 0;

			for (const [wpField, keywords] of Object.entries(wpFields)) {
				for (const keyword of keywords) {
					let score = 0;

					// Exact match = 100 points
					if (fieldLower === keyword) score = 100;

					// Contains match = 80 points
					else if (fieldLower.includes(keyword)) score = 80;

					// Starts with = 70 points
					else if (fieldLower.startsWith(keyword)) score = 70;

					// Partial match = calculate based on length
					else {
						const matchLength = longestCommonSubstring(fieldLower, keyword);
						if (matchLength > 3) {
							score = (matchLength / keyword.length) * 60;
						}
					}

					// Bonus for field type matching
					if (wpField === 'email' && airtableFieldType === 'email') score += 20;
					if (wpField === 'phone' && airtableFieldType === 'phoneNumber') score += 20;
					if (['country_id', 'campaign_id', 'source_id', 'status_id', 'group_id'].includes(wpField)
						&& ['singleSelect', 'multipleSelect'].includes(airtableFieldType)) score += 15;
					if (wpField === 'notes' && ['multilineText', 'richText'].includes(airtableFieldType)) score += 10;
					if (wpField === 'priority' && airtableFieldType === 'number') score += 10;

					if (score > highestScore) {
						highestScore = score;
						bestMatch = wpField;
					}
				}
			}

			// Return match if confidence > 50%
			return highestScore > 50 ? {
				wpField: bestMatch,
				confidence: highestScore,
				autoCreate: ['country_id', 'campaign_id', 'group_id', 'source_id'].includes(bestMatch)
			} : null;
		}

		function longestCommonSubstring(str1, str2) {
			let longest = 0;
			for (let i = 0; i < str1.length; i++) {
				for (let j = 0; j < str2.length; j++) {
					let k = 0;
					while (str1[i + k] === str2[j + k] && i + k < str1.length && j + k < str2.length) {
						k++;
					}
					longest = Math.max(longest, k);
				}
			}
			return longest;
		}

		// Smart field matching functionality
		function getAirtableFieldsList() {
			const fields = [];
			$('#fields-container .aqop-suggestion-badge').each(function () {
				const $badge = $(this);
				const fieldName = $badge.closest('div').find('strong').text();
				const fieldType = $badge.closest('div').find('em').text().replace(/[()]/g, '');
				fields.push({
					name: fieldName,
					type: fieldType
				});
			});
			return fields;
		}

		function getWpFieldsList() {
			const fields = [];
			$('#new_wp_field option').each(function () {
				if ($(this).val()) {
					fields.push({
						value: $(this).val(),
						label: $(this).text(),
						type: $(this).data('type')
					});
				}
			});
			return fields;
		}

		function showMappingSuggestions(suggestions) {
			const $container = $('#suggestions-container');
			$container.empty();

			if (suggestions.length === 0) {
				$container.html('<p>No suggestions found. Try fetching Airtable fields first.</p>');
				return;
			}

			suggestions.forEach((suggestion, index) => {
				const confidenceClass = suggestion.confidence >= 90 ? 'success' :
					suggestion.confidence >= 70 ? 'warning' : 'info';
				const confidenceIcon = suggestion.confidence >= 90 ? '⭐' :
					suggestion.confidence >= 70 ? '👍' : '💡';

				const $suggestionDiv = $(`
				<div style="padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px; background: white;">
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<div>
							<strong>${suggestion.airtable}</strong>
							<span style="color: #666;">→</span>
							<strong style="color: #007cba;">${suggestion.wp.replace('_', ' ').toUpperCase()}</strong>
							${suggestion.autoCreate ? '<small style="color: #666;">(Auto-create enabled)</small>' : ''}
						</div>
						<div>
							<span class="aqop-suggestion-badge aqop-suggestion-${confidenceClass}">
								${confidenceIcon} ${Math.round(suggestion.confidence)}%
							</span>
							<input type="checkbox" class="suggestion-checkbox" data-index="${index}" checked style="margin-left: 10px;">
						</div>
					</div>
				</div>
			`);

				$container.append($suggestionDiv);
			});

			$('#mapping-suggestions').show();
		}

		$('#auto-match-fields').on('click', function () {
			const $button = $(this);
			const $spinner = $('#match-spinner');
			const $results = $('#match-results');
			const $message = $('#match-message');

			const airtableFields = getAirtableFieldsList();
			const wpFields = getWpFieldsList();

			if (airtableFields.length === 0) {
				$message.html('Please fetch Airtable fields first.');
				$results.removeClass('notice-success').addClass('notice-error').show();
				return;
			}

			// Disable button and show spinner
			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$results.hide();
			$('#mapping-suggestions').hide();

			// Generate suggestions using the AI algorithm
			const suggestions = [];

			airtableFields.forEach(atField => {
				const suggestion = suggestFieldMapping(atField.name, atField.type);
				if (suggestion) {
					suggestions.push({
						airtable: atField.name,
						wp: suggestion.wpField,
						confidence: suggestion.confidence,
						autoCreate: suggestion.autoCreate
					});
				}
			});

			// Show suggestions
			showMappingSuggestions(suggestions);

			$message.html(`Generated ${suggestions.length} intelligent mapping suggestions.`);
			$results.removeClass('notice-error').addClass('notice-success').show();

			// Re-enable button and hide spinner
			$button.prop('disabled', false);
			$spinner.removeClass('is-active');
		});

		$('#apply-suggestions').on('click', function () {
			// Store suggestions data for processing
			const suggestions = [];
			$('.suggestion-checkbox:checked').each(function () {
				const $checkbox = $(this);
				const $parent = $checkbox.closest('div');
				const airtableField = $parent.find('strong').first().text();
				const wpField = $parent.find('strong').eq(1).text().toLowerCase().replace(' ', '_');
				const autoCreate = $parent.find('small').length > 0;

				suggestions.push({
					airtable_field: airtableField,
					wp_field: wpField,
					auto_create: autoCreate
				});
			});

			if (suggestions.length === 0) {
				alert('Please select at least one suggestion to apply.');
				return;
			}

			// Apply suggestions via AJAX
			let appliedCount = 0;
			suggestions.forEach((suggestion, index) => {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'aqop_add_airtable_mapping',
						airtable_field: suggestion.airtable_field,
						wp_field: suggestion.wp_field,
						auto_create: suggestion.auto_create ? '1' : '0',
						nonce: $('#add-mapping-form input[name="aqop_settings_save"]').val()
					},
					async: false, // Process sequentially
					success: function (response) {
						if (response.success) {
							appliedCount++;
						}
					}
				});
			});

			if (appliedCount > 0) {
				alert('Applied ' + appliedCount + ' suggested mappings. Page will refresh.');
				location.reload();
			}
		});

		$('#cancel-suggestions').on('click', function () {
			$('#mapping-suggestions').hide();
		});

		// Airtable fields fetching functionality
		$('#fetch-airtable-fields').on('click', function () {
			var $button = $(this);
			var $spinner = $('#fetch-spinner');
			var $results = $('#fetch-results');
			var $message = $('#fetch-message');
			var $fieldsList = $('#airtable-fields-list');
			var $fieldsContainer = $('#fields-container');
			var $airtableFieldSelect = $('#new_airtable_field');

			// Get Airtable credentials from the form
			var baseId = $('input[name="airtable_base_id"]').val();
			var tableName = $('input[name="airtable_table_name"]').val();
			var apiKey = $('input[name="airtable_token"]').val();

			if (!baseId || !tableName || !apiKey) {
				$message.html('Please fill in Base ID, Table Name, and API Token in the Airtable settings above.');
				$results.removeClass('notice-success notice-error').addClass('notice-error').show();
				return;
			}

			// Disable button and show spinner
			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$results.hide();
			$fieldsList.hide();

			// Make AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_fetch_airtable_fields',
					nonce: '<?php echo esc_js(wp_create_nonce("aqop_fetch_airtable_fields")); ?>',
					base_id: baseId,
					table_name: tableName,
					api_key: apiKey
				},
				success: function (response) {
					if (response.success) {
						// Clear existing options (keep the first one)
						$airtableFieldSelect.find('option:not(:first)').remove();

						// Clear fields container
						$fieldsContainer.empty();

						// Add fields to select and display with intelligent suggestions
						let suggestedMappings = 0;

						$.each(response.data.fields, function (index, field) {
							// Get intelligent mapping suggestion
							const suggestion = suggestFieldMapping(field.name, field.type);

							// Add to select dropdown with suggestion indicator
							let optionText = field.name + ' (' + field.type + ')';
							if (suggestion) {
								optionText += ' → ' + suggestion.wpField.replace('_', ' ').toUpperCase();
								optionText += ' (' + Math.round(suggestion.confidence) + '% confidence)';
								suggestedMappings++;
							}

							$airtableFieldSelect.append(
								$('<option></option>')
									.val(field.name)
									.text(optionText)
									.attr('data-suggested-wp-field', suggestion ? suggestion.wpField : '')
									.attr('data-auto-create', suggestion ? suggestion.autoCreate : false)
							);

							// Add to fields display with suggestion badge
							let fieldDisplay = '<div style="padding: 6px 0; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">' +
								'<div><strong>' + field.name + '</strong> <em>(' + field.type + ')</em></div>';

							if (suggestion) {
								const confidenceClass = suggestion.confidence >= 90 ? 'success' :
									suggestion.confidence >= 70 ? 'warning' : 'info';
								const confidenceIcon = suggestion.confidence >= 90 ? '⭐' :
									suggestion.confidence >= 70 ? '👍' : '💡';

								fieldDisplay += '<div style="text-align: right;">' +
									'<span class="aqop-suggestion-badge aqop-suggestion-' + confidenceClass + '">' +
									confidenceIcon + ' ' + suggestion.wpField.replace('_', ' ').toUpperCase() + ' (' + Math.round(suggestion.confidence) + '%)' +
									'</span>' +
									(suggestion.autoCreate ? '<br><small style="color: #666;">Auto-create enabled</small>' : '') +
									'</div>';
							} else {
								fieldDisplay += '<div><em style="color: #999;">No suggestion</em></div>';
							}

							fieldDisplay += '</div>';
							$fieldsContainer.append(fieldDisplay);
						});

						$message.html('Successfully fetched ' + response.data.fields.length + ' fields from Airtable. ' +
							suggestedMappings + ' intelligent mapping suggestions available.');
						$results.removeClass('notice-error').addClass('notice-success').show();
						$fieldsList.show();

					} else {
						$message.html(response.data || 'Unknown error occurred');
						$results.removeClass('notice-success').addClass('notice-error').show();
					}
				},
				error: function (xhr, status, error) {
					$message.html('AJAX Error: ' + error);
					$results.removeClass('notice-success').addClass('notice-error').show();
				},
				complete: function () {
					// Re-enable button and hide spinner
					$button.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		});

		// Auto-populate mapping suggestions when field is selected
		$('#new_airtable_field').on('change', function () {
			const $selectedOption = $(this).find('option:selected');
			const suggestedWpField = $selectedOption.attr('data-suggested-wp-field');
			const autoCreate = $selectedOption.attr('data-auto-create') === 'true';

			if (suggestedWpField) {
				// Auto-select the suggested WordPress field
				$('#new_wp_field').val(suggestedWpField);

				// Auto-set the auto-create checkbox for eligible fields
				$('#new_auto_create').prop('checked', autoCreate);

				// Add visual feedback
				const $wpFieldSelect = $('#new_wp_field');
				const originalBorder = $wpFieldSelect.css('border');
				$wpFieldSelect.css('border', '2px solid #46b450');

				setTimeout(function () {
					$wpFieldSelect.css('border', originalBorder);
				}, 2000);
			}
		});

		// Add "Apply All Suggestions" button functionality
		$('body').on('click', '#apply-all-suggestions', function (e) {
			e.preventDefault();

			if (!confirm('This will create mappings for all suggested fields. Continue?')) {
				return;
			}

			let appliedCount = 0;
			$('#fields-container .aqop-suggestion-badge').each(function () {
				const $badge = $(this);
				const fieldName = $badge.closest('div').find('strong').text();
				const wpFieldText = $badge.text().split(' (')[0].replace(/^[⭐👍💡]\s*/, '').toLowerCase().replace(' ', '_');

				if (wpFieldText && fieldName) {
					// Auto-create mapping via AJAX
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'aqop_add_airtable_mapping',
							airtable_field: fieldName,
							wp_field: wpFieldText,
							auto_create: $badge.parent().find('small').length > 0 ? '1' : '0',
							nonce: $('#add-mapping-form input[name="aqop_settings_save"]').val()
						},
						async: false, // Process sequentially
						success: function (response) {
							if (response.success) {
								appliedCount++;
								$badge.css('opacity', '0.5').text($badge.text() + ' ✓');
							}
						}
					});
				}
			});

			if (appliedCount > 0) {
				alert('Applied ' + appliedCount + ' suggested mappings. Page will refresh.');
				location.reload();
			}
		});
	});
</script>
</div>

<style>
	.aqop-settings h1 {
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.aqop-settings-tabs {
		margin-top: 20px;
	}

	/* Categorized tab sections */
	.aqop-tab-section {
		display: inline-block;
		margin-right: 30px;
		margin-bottom: 20px;
		min-width: 250px;
		vertical-align: top;
	}

	.aqop-section-title {
		background: #f1f1f1;
		color: #23282d;
		padding: 12px 16px;
		margin: 0 0 10px 0;
		border-radius: 6px;
		font-size: 14px;
		font-weight: 600;
		border: 1px solid #ddd;
	}

	.aqop-section-tabs {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.aqop-section-tabs .nav-tab {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 8px 12px;
		font-size: 13px;
		border-radius: 4px;
		margin: 0;
		width: 100%;
		justify-content: flex-start;
	}

	/* Responsive design */
	@media (max-width: 768px) {
		.aqop-tab-section {
			display: block;
			margin-right: 0;
			margin-bottom: 25px;
			min-width: auto;
		}

		.aqop-section-tabs {
			flex-direction: row;
			flex-wrap: wrap;
			gap: 4px;
		}

		.aqop-section-tabs .nav-tab {
			flex: 1;
			min-width: 200px;
			margin-bottom: 4px;
		}
	}

	.aqop-settings-tabs .nav-tab {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}

	.aqop-settings-tab {
		display: none;
		margin-top: 20px;
	}

	.aqop-settings-tab.active {
		display: block;
	}

	.aqop-integration-card {
		margin-bottom: 20px;
	}

	.aqop-integration-card h3 {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 0;
		padding-bottom: 10px;
		border-bottom: 2px solid #f0f0f1;
	}

	.aqop-badge {
		display: inline-block;
		padding: 4px 10px;
		border-radius: 12px;
		font-size: 11px;
		font-weight: 600;
		text-transform: uppercase;
	}

	.aqop-badge-success {
		background: #d4edda;
		color: #155724;
	}

	.aqop-badge-default {
		background: #e2e8f0;
		color: #646970;
	}

	/* Platform badges */
	.aqop-platform-badge {
		display: inline-block;
		padding: 4px 8px;
		border-radius: 8px;
		font-size: 11px;
		font-weight: 600;
		text-transform: capitalize;
	}

	.platform-facebook {
		color: #1877f2;
		background: rgba(24, 119, 242, 0.1);
	}

	.platform-instagram {
		color: #e4405f;
		background: rgba(228, 64, 95, 0.1);
	}

	.platform-google {
		color: #4285f4;
		background: rgba(66, 133, 244, 0.1);
	}

	.platform-linkedin {
		color: #0077b5;
		background: rgba(0, 119, 181, 0.1);
	}

	.platform-twitter {
		color: #1da1f2;
		background: rgba(29, 161, 242, 0.1);
	}

	.platform-tiktok {
		color: #000000;
		background: rgba(0, 0, 0, 0.1);
	}

	.platform-snapchat {
		color: #fffc00;
		background: rgba(255, 252, 0, 0.1);
	}

	.platform-email {
		color: #ea4335;
		background: rgba(234, 67, 53, 0.1);
	}

	.platform-sms {
		color: #25d366;
		background: rgba(37, 211, 102, 0.1);
	}

	.platform-offline {
		color: #6b7280;
		background: rgba(107, 114, 128, 0.1);
	}

	.platform-other {
		color: #8b5cf6;
		background: rgba(139, 92, 246, 0.1);
	}

	/* Intelligent mapping suggestions */
	.aqop-suggestion-badge {
		display: inline-block;
		padding: 2px 6px;
		border-radius: 10px;
		font-size: 10px;
		font-weight: bold;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}

	.aqop-suggestion-success {
		background: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}

	.aqop-suggestion-warning {
		background: #fff3cd;
		color: #856404;
		border: 1px solid #ffeaa7;
	}

	.aqop-suggestion-info {
		background: #cce5ff;
		color: #004085;
		border: 1px solid #99d6ff;
	}

	.aqop-ai-powered {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
		padding: 8px 16px;
		border-radius: 6px;
		font-size: 12px;
		font-weight: bold;
		display: inline-block;
		margin-bottom: 15px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}

	.source-type-paid {
		color: #ed8936;
		font-weight: 600;
	}

	.source-type-organic {
		color: #48bb78;
		font-weight: 600;
	}

	.source-type-referral {
		color: #4299e1;
		font-weight: 600;
	}

	.source-type-direct {
		color: #718096;
		font-weight: 600;
	}

	.required {
		color: #d63638;
	}

	input.code {
		font-family: 'Courier New', monospace;
		font-size: 13px;
		background: #f6f7f7;
	}

	/* Campaign Questions Styles */
	.campaign-questions-card {
		margin-bottom: 25px;
		border-left: 4px solid #2271b1;
	}

	.campaign-title {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-top: 0;
		padding-bottom: 15px;
		border-bottom: 2px solid #f0f0f1;
	}

	.campaign-id-badge {
		background: #f0f0f1;
		color: #646970;
		padding: 3px 10px;
		border-radius: 12px;
		font-size: 11px;
		font-weight: 600;
	}

	.field-type-badge {
		display: inline-block;
		padding: 4px 10px;
		border-radius: 12px;
		font-size: 11px;
		font-weight: 600;
		text-transform: uppercase;
	}

	.field-type-text {
		background: #e3f2fd;
		color: #1976d2;
	}

	.field-type-select,
	.field-type-radio {
		background: #f3e5f5;
		color: #7b1fa2;
	}

	.field-type-checkbox {
		background: #e8f5e9;
		color: #388e3c;
	}

	.field-type-textarea {
		background: #fff3e0;
		color: #f57c00;
	}

	.no-questions {
		padding: 20px;
		text-align: center;
		color: #646970;
		font-style: italic;
	}

	.question-row td {
		vertical-align: middle;
	}

	.add-question-form h4 {
		margin-top: 0;
		margin-bottom: 15px;
	}

	/* RTL Support */
	[dir="auto"] {
		text-align: start;
	}

	.edit-question,
	.delete-question {
		margin-right: 5px;
	}

	.edit-question .dashicons,
	.delete-question .dashicons,
	.add-question-btn .dashicons {
		font-size: 14px;
		vertical-align: middle;
		margin-right: 2px;
	}

	/* Fix table display */
	.aqop-settings .wp-list-table.fixed {
		table-layout: auto !important;
	}

	.aqop-settings .wp-list-table td {
		white-space: normal !important;
		word-wrap: break-word;
	}

	.aqop-settings .wp-list-table tbody tr {
		height: auto !important;
	}
</style>

<script>
	jQuery(document).ready(function ($) {
		'use strict';

		// === Campaign Questions AJAX Handlers ===

		// Add Question
		$(document).on('click', '.add-question-btn', function () {
			const btn = $(this);
			const card = btn.closest('.campaign-questions-card');
			const campaignId = btn.data('campaign');
			const qId = card.find('.new-question-id').val().trim();
			const qText = card.find('.new-question-text').val().trim();
			const qType = card.find('.new-question-type').val();

			// Validation
			if (!qId || !qText) {
				alert('<?php esc_html_e('Please fill in Question ID and Text.', 'aqop-leads'); ?>');
				return;
			}

			if (!/^q[0-9]+$/.test(qId)) {
				alert('<?php esc_html_e('Question ID must be in format: q1, q2, q3, etc.', 'aqop-leads'); ?>');
				return;
			}

			// Disable button
			btn.prop('disabled', true).text('<?php esc_html_e('Adding...', 'aqop-leads'); ?>');

			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_add_campaign_question',
					nonce: '<?php echo esc_js(wp_create_nonce('aqop_campaign_questions')); ?>',
					campaign_id: campaignId,
					question_id: qId,
					question_text: qText,
					question_type: qType
				},
				success: function (response) {
					if (response.success) {
						// Clear form
						card.find('.new-question-id, .new-question-text').val('');
						card.find('.new-question-type').val('text');

						// Reload page to show updated questions
						location.reload();
					} else {
						alert(response.data.message || '<?php esc_html_e('Failed to add question.', 'aqop-leads'); ?>');
					}
				},
				error: function () {
					alert('<?php esc_html_e('Error adding question. Please try again.', 'aqop-leads'); ?>');
				},
				complete: function () {
					btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add Question', 'aqop-leads'); ?>');
				}
			});
		});

		// Edit Question
		$(document).on('click', '.edit-question', function () {
			const btn = $(this);
			const campaignId = btn.data('campaign');
			const qId = btn.data('qid');
			const currentText = btn.data('text');
			const currentType = btn.data('type');

			// Prompt for new text
			const newText = prompt('<?php esc_html_e('Edit question text:', 'aqop-leads'); ?>', currentText);

			if (newText === null || newText.trim() === '') {
				return; // Cancelled
			}

			// Prompt for new type
			const newType = prompt(
				'<?php esc_html_e('Field type (text, select, radio, checkbox, textarea):', 'aqop-leads'); ?>',
				currentType
			);

			if (newType === null) {
				return; // Cancelled
			}

			// Validate type
			const validTypes = ['text', 'select', 'radio', 'checkbox', 'textarea'];
			if (!validTypes.includes(newType.toLowerCase())) {
				alert('<?php esc_html_e('Invalid field type.', 'aqop-leads'); ?>');
				return;
			}

			// Disable button
			btn.prop('disabled', true);

			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_edit_campaign_question',
					nonce: '<?php echo esc_js(wp_create_nonce('aqop_campaign_questions')); ?>',
					campaign_id: campaignId,
					question_id: qId,
					question_text: newText.trim(),
					question_type: newType.toLowerCase()
				},
				success: function (response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data.message || '<?php esc_html_e('Failed to update question.', 'aqop-leads'); ?>');
					}
				},
				error: function () {
					alert('<?php esc_html_e('Error updating question. Please try again.', 'aqop-leads'); ?>');
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});

		// Delete Question
		$(document).on('click', '.delete-question', function () {
			if (!confirm('<?php esc_html_e('Are you sure you want to delete this question?', 'aqop-leads'); ?>')) {
				return;
			}

			const btn = $(this);
			const campaignId = btn.data('campaign');
			const qId = btn.data('qid');

			// Disable button
			btn.prop('disabled', true);

			// AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_delete_campaign_question',
					nonce: '<?php echo esc_js(wp_create_nonce('aqop_campaign_questions')); ?>',
					campaign_id: campaignId,
					question_id: qId
				},
				success: function (response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data.message || '<?php esc_html_e('Failed to delete question.', 'aqop-leads'); ?>');
					}
				},
				error: function () {
					alert('<?php esc_html_e('Error deleting question. Please try again.', 'aqop-leads'); ?>');
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});

		// === Meta Integration Test Webhook ===

		$('#test-meta-webhook').on('click', function () {
			const btn = $(this);
			const resultDiv = $('#test-webhook-result');

			// Disable button and show loading
			btn.prop('disabled', true);
			resultDiv.html('<span style="color: #666;">Sending test webhook...</span>');

			// Get test payload from static method
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_get_meta_test_payload',
					nonce: '<?php echo esc_js(wp_create_nonce('aqop_meta_test')); ?>'
				},
				success: function (response) {
					if (response.success && response.data.test_payload) {
						const testPayload = response.data.test_payload;
						const webhookUrl = '<?php echo esc_js(get_rest_url(null, 'aqop/v1/meta/webhook')); ?>';

						// Send test webhook
						$.ajax({
							url: webhookUrl,
							type: 'POST',
							contentType: 'application/json',
							data: JSON.stringify(testPayload),
							success: function (webhookResponse) {
								resultDiv.html('<div style="color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">' +
									'<strong>✓ Test webhook sent successfully!</strong><br>' +
									'Check the webhook logs below to see if it was processed.' +
									'</div>');

								// Reload the page after 2 seconds to show new logs
								setTimeout(function () {
									location.reload();
								}, 2000);
							},
							error: function (xhr, status, error) {
								resultDiv.html('<div style="color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">' +
									'<strong>✗ Test webhook failed</strong><br>' +
									'Error: ' + error + '<br>' +
									'Status: ' + xhr.status +
									'</div>');
							}
						});
					} else {
						resultDiv.html('<div style="color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">' +
							'<strong>✗ Failed to get test payload</strong>' +
							'</div>');
					}
				},
				error: function () {
					resultDiv.html('<div style="color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">' +
						'<strong>✗ Failed to prepare test webhook</strong>' +
						'</div>');
				},
				complete: function () {
					btn.prop('disabled', false);
				}
			});
		});

		// === Auto-Sync Settings ===

		// Toggle interval and smart sync row visibility based on enable checkbox
		$('#auto-sync-toggle').on('change', function() {
			if ($(this).is(':checked')) {
				$('#auto-sync-interval-row').slideDown();
				$('#smart-sync-row').slideDown();
			} else {
				$('#auto-sync-interval-row').slideUp();
				$('#smart-sync-row').slideUp();
			}
		});

		// Save auto-sync settings via AJAX
		$('#save-auto-sync-settings').on('click', function() {
			const btn = $(this);
			const spinner = $('#auto-sync-save-spinner');
			const message = $('#auto-sync-save-message');

			// Get values
			const enabled = $('#auto-sync-toggle').is(':checked');
			const interval = $('#auto-sync-interval').val();
			const smart_sync = $('#smart-sync-toggle').is(':checked');

			// Disable button and show spinner
			btn.prop('disabled', true);
			spinner.addClass('is-active');
			message.text('');

			// Send AJAX request
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_save_auto_sync_settings',
					nonce: '<?php echo esc_js(wp_create_nonce('aqop_airtable_sync')); ?>',
					enabled: enabled,
					interval: interval,
					smart_sync: smart_sync
				},
				success: function(response) {
					if (response.success) {
						message.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
						if (response.data.next_run) {
							message.append(' <strong>Next sync: ' + response.data.next_run + '</strong>');
						}
						// Reload page after 2 seconds to update status display
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						message.html('<span style="color: #dc3232;">✗ ' + (response.data || 'Failed to save settings') + '</span>');
					}
				},
				error: function() {
					message.html('<span style="color: #dc3232;">✗ Err or saving settings. Please try again.</span>');
				},
				complete: function() {
					btn.prop('disabled', false);
					spinner.removeClass('is-active');
				}
			});
		});

		// Force Full Sync button
		$('#force-full-sync').on('click', function() {
			if (!confirm('<?php echo esc_js(__('This will sync ALL records from Airtable, ignoring the Smart Sync filter. This may take a while. Continue?', 'aqop-leads')); ?>')) {
				return;
			}

			const btn = $(this);
			const message = $('#auto-sync-save-message');

			// Disable button
			btn.prop('disabled', true);
			btn.html('<span class="dashicons dashicons-update dashicons-spin" style="vertical-align: middle; margin-top: -2px;"></span> <?php echo esc_js(__('Syncing...', 'aqop-leads')); ?>');
			message.html('<span style="color: #0073aa;">Starting full sync...</span>');

			// Start chunked sync with force_full_sync flag
			function syncChunk(offset) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'aqop_sync_airtable_chunk',
						nonce: '<?php echo esc_js(wp_create_nonce('aqop_airtable_sync')); ?>',
						offset: offset || '',
						force_full_sync: true
					},
					timeout: 90000,
					success: function(response) {
						if (response.success) {
							const data = response.data;
							if (data.is_complete) {
								message.html('<span style="color: #46b450;">✓ Full sync complete! ' + 
									data.cumulative.leads_processed + ' processed, ' +
									data.cumulative.leads_created + ' created, ' +
									data.cumulative.leads_updated + ' updated</span>');
								btn.html('<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span> <?php echo esc_js(__('Force Full Sync', 'aqop-leads')); ?>');
								btn.prop('disabled', false);
								// Reload after 2 seconds
								setTimeout(function() {
									location.reload();
								}, 2000);
							} else {
								message.html('<span style="color: #0073aa;">Syncing... ' + data.cumulative.leads_processed + ' records processed</span>');
								syncChunk(data.next_offset);
							}
						} else {
							message.html('<span style="color: #dc3232;">✗ ' + (response.data.message || 'Sync failed') + '</span>');
							btn.html('<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span> <?php echo esc_js(__('Force Full Sync', 'aqop-leads')); ?>');
							btn.prop('disabled', false);
						}
					},
					error: function() {
						message.html('<span style="color: #dc3232;">✗ Error during sync. Please try again.</span>');
						btn.html('<span class="dashicons dashicons-update" style="vertical-align: middle; margin-top: -2px;"></span> <?php echo esc_js(__('Force Full Sync', 'aqop-leads')); ?>');
						btn.prop('disabled', false);
					}
				});
			}

			syncChunk('');
		});
	});
</script>