<?php
/**
 * Settings Page
 *
 * Manage lead sources, statuses, integrations, and notifications.
 *
 * @package AQOP_Leads
 * @since   1.0.9
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

<div class="wrap aqop-settings">
	<h1>
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Leads Settings', 'aqop-leads' ); ?>
	</h1>
	
	<p class="description">
		<?php esc_html_e( 'Configure lead sources, statuses, integrations, and notification preferences.', 'aqop-leads' ); ?>
	</p>

	<!-- === SETTINGS PAGE (Phase 4.1) === -->
	<div class="aqop-settings-tabs">
		<nav class="nav-tab-wrapper">
			<a href="#sources" class="nav-tab nav-tab-active" data-tab="sources">
				<span class="dashicons dashicons-category"></span>
				<?php esc_html_e( 'Lead Sources', 'aqop-leads' ); ?>
			</a>
			<a href="#statuses" class="nav-tab" data-tab="statuses">
				<span class="dashicons dashicons-flag"></span>
				<?php esc_html_e( 'Lead Statuses', 'aqop-leads' ); ?>
			</a>
			<a href="#integrations" class="nav-tab" data-tab="integrations">
				<span class="dashicons dashicons-admin-plugins"></span>
				<?php esc_html_e( 'Integrations', 'aqop-leads' ); ?>
			</a>
			<a href="#notifications" class="nav-tab" data-tab="notifications">
				<span class="dashicons dashicons-email"></span>
				<?php esc_html_e( 'Notifications', 'aqop-leads' ); ?>
			</a>
		</nav>

		<!-- === LEAD SOURCES TAB === -->
		<div id="sources" class="aqop-settings-tab active">
			<div class="card">
				<h2><?php esc_html_e( 'Manage Lead Sources', 'aqop-leads' ); ?></h2>
				<p><?php esc_html_e( 'Track where your leads come from. Add custom sources for better attribution and ROI analysis.', 'aqop-leads' ); ?></p>
			</div>
			
			<!-- Add New Source Form -->
			<div class="card">
				<h3><?php esc_html_e( 'Add New Source', 'aqop-leads' ); ?></h3>
				<form method="post" class="aqop-settings-form">
					<?php wp_nonce_field( 'aqop_settings_save' ); ?>
					<input type="hidden" name="aqop_settings_action" value="add_source">
					
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="source_name"><?php esc_html_e( 'Source Name', 'aqop-leads' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" id="source_name" name="source_name" required placeholder="<?php esc_attr_e( 'e.g., Facebook Ads, LinkedIn, Referral', 'aqop-leads' ); ?>" class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="source_type"><?php esc_html_e( 'Source Type', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<select id="source_type" name="source_type" class="regular-text">
										<option value="paid"><?php esc_html_e( 'Paid', 'aqop-leads' ); ?></option>
										<option value="organic"><?php esc_html_e( 'Organic', 'aqop-leads' ); ?></option>
										<option value="referral"><?php esc_html_e( 'Referral', 'aqop-leads' ); ?></option>
										<option value="direct"><?php esc_html_e( 'Direct', 'aqop-leads' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cost_per_lead"><?php esc_html_e( 'Cost Per Lead', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="number" id="cost_per_lead" name="cost_per_lead" step="0.01" min="0" placeholder="0.00" class="small-text">
									<p class="description"><?php esc_html_e( 'Optional. For ROI tracking.', 'aqop-leads' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-plus-alt"></span>
							<?php esc_html_e( 'Add Source', 'aqop-leads' ); ?>
						</button>
					</p>
				</form>
			</div>

			<!-- Existing Sources Table -->
			<div class="card">
				<h3><?php esc_html_e( 'Existing Sources', 'aqop-leads' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 50px;"><?php esc_html_e( 'ID', 'aqop-leads' ); ?></th>
							<th><?php esc_html_e( 'Source Name', 'aqop-leads' ); ?></th>
							<th><?php esc_html_e( 'Type', 'aqop-leads' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Leads Count', 'aqop-leads' ); ?></th>
							<th style="width: 80px;"><?php esc_html_e( 'Status', 'aqop-leads' ); ?></th>
							<th style="width: 150px;"><?php esc_html_e( 'Actions', 'aqop-leads' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$sources = $wpdb->get_results(
							"SELECT s.*, COUNT(l.id) as lead_count
							 FROM {$wpdb->prefix}aq_leads_sources s
							 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.source_id
							 GROUP BY s.id
							 ORDER BY s.source_name ASC"
						);

						if ( empty( $sources ) ) {
							echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">' . esc_html__( 'No sources found. Add your first source above.', 'aqop-leads' ) . '</td></tr>';
						} else {
							foreach ( $sources as $source ) {
								$status_badge = $source->is_active 
									? '<span class="aqop-badge aqop-badge-success">Active</span>' 
									: '<span class="aqop-badge aqop-badge-default">Inactive</span>';
								
								printf(
									'<tr>
										<td>%d</td>
										<td><strong>%s</strong><br><small>Code: %s</small></td>
										<td><span class="source-type-%s">%s</span></td>
										<td><strong>%d</strong></td>
										<td>%s</td>
										<td>
											<button class="button button-small toggle-source-status" data-id="%d" data-current="%d">
												%s
											</button>
										</td>
									</tr>',
									absint( $source->id ),
									esc_html( $source->source_name ),
									esc_html( $source->source_code ),
									esc_attr( $source->source_type ),
									esc_html( ucfirst( $source->source_type ) ),
									absint( $source->lead_count ),
									$status_badge,
									esc_attr( $source->id ),
									esc_attr( $source->is_active ),
									$source->is_active ? esc_html__( 'Deactivate', 'aqop-leads' ) : esc_html__( 'Activate', 'aqop-leads' )
								);
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- === LEAD STATUSES TAB === -->
		<div id="statuses" class="aqop-settings-tab">
			<div class="card">
				<h2><?php esc_html_e( 'Manage Lead Statuses', 'aqop-leads' ); ?></h2>
				<p><?php esc_html_e( 'Define the stages of your lead pipeline. Status colors help visualize lead progress.', 'aqop-leads' ); ?></p>
			</div>
			
			<div class="card">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Status Name', 'aqop-leads' ); ?></th>
							<th style="width: 120px;"><?php esc_html_e( 'Code', 'aqop-leads' ); ?></th>
							<th style="width: 150px;"><?php esc_html_e( 'Color', 'aqop-leads' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Leads Count', 'aqop-leads' ); ?></th>
							<th style="width: 80px;"><?php esc_html_e( 'Order', 'aqop-leads' ); ?></th>
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

						foreach ( $statuses as $status ) {
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
								esc_html( $status->status_name_en ),
								esc_html( $status->status_name_ar ),
								esc_html( $status->status_code ),
								esc_attr( $status->color ),
								esc_html( $status->status_name_en ),
								absint( $status->lead_count ),
								absint( $status->status_order )
							);
						}
						?>
					</tbody>
				</table>
				<p class="description">
					<?php esc_html_e( 'Note: Status management is currently view-only. Contact administrator to add custom statuses.', 'aqop-leads' ); ?>
				</p>
			</div>
		</div>

		<!-- === INTEGRATIONS TAB === -->
		<div id="integrations" class="aqop-settings-tab">
			<div class="card">
				<h2><?php esc_html_e( 'External Integrations', 'aqop-leads' ); ?></h2>
				<p><?php esc_html_e( 'Configure external services to sync leads automatically.', 'aqop-leads' ); ?></p>
			</div>
			
			<form method="post">
				<?php wp_nonce_field( 'aqop_settings_save' ); ?>
				<input type="hidden" name="aqop_settings_action" value="update_integrations">
				
				<!-- Airtable Integration -->
				<div class="card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-database"></span>
						<?php esc_html_e( 'Airtable', 'aqop-leads' ); ?>
					</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="airtable_token"><?php esc_html_e( 'API Token', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="password" id="airtable_token" name="airtable_token" value="<?php echo esc_attr( get_option( 'aqop_airtable_token', '' ) ); ?>" class="large-text code">
									<p class="description"><?php esc_html_e( 'Get your API token from Airtable account settings.', 'aqop-leads' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="airtable_base_id"><?php esc_html_e( 'Base ID', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="text" id="airtable_base_id" name="airtable_base_id" value="<?php echo esc_attr( get_option( 'aqop_airtable_base_id', '' ) ); ?>" class="regular-text code" placeholder="appXXXXXXXXXXXXXX">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="airtable_table_name"><?php esc_html_e( 'Table Name', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="text" id="airtable_table_name" name="airtable_table_name" value="<?php echo esc_attr( get_option( 'aqop_airtable_table_name', 'Leads' ) ); ?>" class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Auto-Sync', 'aqop-leads' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="airtable_auto_sync" value="1" <?php checked( get_option( 'aqop_airtable_auto_sync' ), '1' ); ?>>
										<?php esc_html_e( 'Automatically sync new and updated leads to Airtable', 'aqop-leads' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Telegram Integration -->
				<div class="card aqop-integration-card">
					<h3>
						<span class="dashicons dashicons-format-chat"></span>
						<?php esc_html_e( 'Telegram', 'aqop-leads' ); ?>
					</h3>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="telegram_bot_token"><?php esc_html_e( 'Bot Token', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="text" id="telegram_bot_token" name="telegram_bot_token" value="<?php echo esc_attr( get_option( 'aqop_telegram_bot_token', '' ) ); ?>" class="large-text code" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
									<p class="description">
										<?php esc_html_e( 'Get bot token from @BotFather on Telegram.', 'aqop-leads' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="telegram_chat_id"><?php esc_html_e( 'Chat ID', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="text" id="telegram_chat_id" name="telegram_chat_id" value="<?php echo esc_attr( get_option( 'aqop_telegram_chat_id', '' ) ); ?>" class="regular-text" placeholder="-1001234567890">
									<p class="description">
										<?php esc_html_e( 'Channel or group chat ID where notifications will be sent.', 'aqop-leads' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Notifications', 'aqop-leads' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="telegram_notify_new" value="1" <?php checked( get_option( 'aqop_telegram_notify_new' ), '1' ); ?>>
										<?php esc_html_e( 'Send Telegram message when new lead is submitted', 'aqop-leads' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Integration Settings', 'aqop-leads' ); ?>
					</button>
				</p>
			</form>
		</div>

		<!-- === NOTIFICATIONS TAB === -->
		<div id="notifications" class="aqop-settings-tab">
			<div class="card">
				<h2><?php esc_html_e( 'Notification Settings', 'aqop-leads' ); ?></h2>
				<p><?php esc_html_e( 'Configure email notifications for lead events.', 'aqop-leads' ); ?></p>
			</div>
			
			<div class="card">
				<form method="post">
					<?php wp_nonce_field( 'aqop_settings_save' ); ?>
					<input type="hidden" name="aqop_settings_action" value="update_notifications">
					
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="notification_email"><?php esc_html_e( 'Notification Email', 'aqop-leads' ); ?></label>
								</th>
								<td>
									<input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( get_option( 'aqop_notification_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
									<p class="description"><?php esc_html_e( 'Receive lead notifications at this email address.', 'aqop-leads' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Email Notifications', 'aqop-leads' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="notify_new_lead" value="1" <?php checked( get_option( 'aqop_notify_new_lead', '1' ), '1' ); ?>>
										<?php esc_html_e( 'New lead submitted (via public form or API)', 'aqop-leads' ); ?>
									</label><br>
									<label>
										<input type="checkbox" name="notify_status_change" value="1" <?php checked( get_option( 'aqop_notify_status_change' ), '1' ); ?>>
										<?php esc_html_e( 'Lead status changed to "Converted"', 'aqop-leads' ); ?>
									</label><br>
									<label>
										<input type="checkbox" name="notify_assignment" value="1" <?php checked( get_option( 'aqop_notify_assignment' ), '1' ); ?>>
										<?php esc_html_e( 'Lead assigned to user', 'aqop-leads' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-saved"></span>
							<?php esc_html_e( 'Save Notification Settings', 'aqop-leads' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>

	</div>
	<!-- === END SETTINGS PAGE === -->
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
</style>

