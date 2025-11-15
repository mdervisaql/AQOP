<?php
/**
 * Control Center Overview Template
 *
 * Main dashboard view for Operation Platform.
 *
 * @package AQOP_Core
 * @since   1.0.0
 *
 * @var array $stats System statistics array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="wrap aqop-control-center">
	
	<!-- Dashboard Header -->
	<div class="aqop-header">
		<div class="aqop-header-content">
			<h1 class="aqop-title">
				<span class="dashicons dashicons-dashboard"></span>
				<?php esc_html_e( 'مركز العمليات', 'aqop-core' ); ?>
			</h1>
			<p class="aqop-subtitle"><?php esc_html_e( 'Real-time Operations Monitoring & Analytics', 'aqop-core' ); ?></p>
		</div>
		
		<div class="aqop-header-meta">
			<span class="aqop-live-indicator">
				<span class="pulse"></span>
				<?php esc_html_e( 'Live Updates', 'aqop-core' ); ?>
			</span>
			<span class="aqop-last-updated">
				<?php
				/* translators: %s: Time */
				printf( esc_html__( 'Last updated: %s', 'aqop-core' ), esc_html( current_time( 'H:i:s' ) ) );
				?>
			</span>
		</div>
	</div>

	<!-- Stats Grid -->
	<div class="aqop-stats-grid">
		
		<!-- Total Events -->
		<div class="aqop-stat-card">
			<div class="stat-icon stat-icon-primary">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="stat-content">
				<div class="stat-label"><?php esc_html_e( 'Events (24h)', 'aqop-core' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $stats['events_today'] ) ); ?></div>
				<div class="stat-trend trend-up">
					<span class="dashicons dashicons-arrow-up-alt"></span>
					<?php esc_html_e( 'Active', 'aqop-core' ); ?>
				</div>
			</div>
		</div>

		<!-- Active Users -->
		<div class="aqop-stat-card">
			<div class="stat-icon stat-icon-success">
				<span class="dashicons dashicons-groups"></span>
			</div>
			<div class="stat-content">
				<div class="stat-label"><?php esc_html_e( 'Active Users', 'aqop-core' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $stats['active_users'] ) ); ?></div>
				<div class="stat-trend">
					<?php esc_html_e( 'Last 24 hours', 'aqop-core' ); ?>
				</div>
			</div>
		</div>

		<!-- Warnings -->
		<div class="aqop-stat-card">
			<div class="stat-icon stat-icon-warning">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="stat-content">
				<div class="stat-label"><?php esc_html_e( 'Warnings', 'aqop-core' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $stats['warnings_count'] ) ); ?></div>
				<div class="stat-trend">
					<?php esc_html_e( 'Requires attention', 'aqop-core' ); ?>
				</div>
			</div>
		</div>

		<!-- Critical Errors -->
		<div class="aqop-stat-card">
			<div class="stat-icon stat-icon-danger">
				<span class="dashicons dashicons-dismiss"></span>
			</div>
			<div class="stat-content">
				<div class="stat-label"><?php esc_html_e( 'Critical Errors', 'aqop-core' ); ?></div>
				<div class="stat-value"><?php echo esc_html( number_format_i18n( $stats['errors_24h'] ) ); ?></div>
				<div class="stat-trend <?php echo 0 === $stats['errors_24h'] ? 'trend-down' : ''; ?>">
					<?php
					if ( 0 === $stats['errors_24h'] ) {
						echo '<span class="dashicons dashicons-yes-alt"></span> ';
						esc_html_e( 'All clear', 'aqop-core' );
					} else {
						esc_html_e( 'Needs review', 'aqop-core' );
					}
					?>
				</div>
			</div>
		</div>

	</div>

	<!-- Platform Status Section -->
	<div class="aqop-section">
		<h2 class="aqop-section-title"><?php esc_html_e( 'Platform Status', 'aqop-core' ); ?></h2>
		
		<div class="aqop-platform-status">
			<div class="platform-status-card status-<?php echo esc_attr( $stats['platform_status'] ); ?>">
				<div class="status-indicator">
					<?php
					if ( 'active' === $stats['platform_status'] ) {
						echo '<span class="dashicons dashicons-yes-alt"></span>';
						esc_html_e( 'All Systems Operational', 'aqop-core' );
					} elseif ( 'warning' === $stats['platform_status'] ) {
						echo '<span class="dashicons dashicons-warning"></span>';
						esc_html_e( 'Minor Issues Detected', 'aqop-core' );
					} else {
						echo '<span class="dashicons dashicons-dismiss"></span>';
						esc_html_e( 'Critical Issues', 'aqop-core' );
					}
					?>
				</div>
			</div>

			<div class="platform-info-grid">
				<div class="info-item">
					<strong><?php esc_html_e( 'Uptime:', 'aqop-core' ); ?></strong>
					<?php
					/* translators: %d: Number of days */
					printf( esc_html( _n( '%d day', '%d days', $stats['uptime_days'], 'aqop-core' ) ), esc_html( $stats['uptime_days'] ) );
					?>
				</div>
				<div class="info-item">
					<strong><?php esc_html_e( 'Database Size:', 'aqop-core' ); ?></strong>
					<?php echo esc_html( number_format_i18n( $stats['database_size'], 2 ) ); ?> MB
				</div>
				<div class="info-item">
					<strong><?php esc_html_e( 'Version:', 'aqop-core' ); ?></strong>
					<?php echo esc_html( AQOP_VERSION ); ?>
				</div>
				<div class="info-item">
					<strong><?php esc_html_e( 'Last Backup:', 'aqop-core' ); ?></strong>
					<?php echo $stats['last_backup'] ? esc_html( $stats['last_backup'] ) : esc_html__( 'Not available', 'aqop-core' ); ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Modules Health -->
	<div class="aqop-section">
		<h2 class="aqop-section-title"><?php esc_html_e( 'Modules Health', 'aqop-core' ); ?></h2>
		
		<div class="aqop-modules-grid">
			<?php foreach ( $stats['modules_health'] as $module ) : ?>
				<div class="aqop-module-card module-status-<?php echo esc_attr( $module['status'] ); ?>">
					<div class="module-header">
						<h3 class="module-name"><?php echo esc_html( $module['name'] ); ?></h3>
						<span class="module-badge badge-<?php echo esc_attr( $module['status'] ); ?>">
							<?php
							if ( 'ok' === $module['status'] ) {
								echo '<span class="dashicons dashicons-yes-alt"></span> ';
								esc_html_e( 'Active', 'aqop-core' );
							} else {
								echo '<span class="dashicons dashicons-marker"></span> ';
								esc_html_e( 'Inactive', 'aqop-core' );
							}
							?>
						</span>
					</div>
					<div class="module-body">
						<p class="module-description"><?php echo esc_html( $module['description'] ); ?></p>
						<p class="module-version">
							<strong><?php esc_html_e( 'Version:', 'aqop-core' ); ?></strong>
							<?php echo esc_html( $module['version'] ); ?>
						</p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Integrations Status -->
	<div class="aqop-section">
		<h2 class="aqop-section-title"><?php esc_html_e( 'Integrations Status', 'aqop-core' ); ?></h2>
		
		<div class="aqop-integrations-grid">
			<?php foreach ( $stats['integrations_status'] as $key => $integration ) : ?>
				<div class="aqop-integration-card integration-status-<?php echo esc_attr( $integration['status'] ); ?>">
					<div class="integration-icon">
						<?php
						switch ( $key ) {
							case 'airtable':
								echo '<span class="dashicons dashicons-database"></span>';
								break;
							case 'dropbox':
								echo '<span class="dashicons dashicons-cloud"></span>';
								break;
							case 'telegram':
								echo '<span class="dashicons dashicons-email"></span>';
								break;
							default:
								echo '<span class="dashicons dashicons-admin-plugins"></span>';
						}
						?>
					</div>
					<div class="integration-content">
						<h3 class="integration-name"><?php echo esc_html( $integration['name'] ); ?></h3>
						<p class="integration-status">
							<?php
							if ( 'ok' === $integration['status'] ) {
								echo '<span class="status-dot status-ok"></span>';
								esc_html_e( 'Connected', 'aqop-core' );
							} else {
								echo '<span class="status-dot status-error"></span>';
								echo esc_html( $integration['message'] );
							}
							?>
						</p>
						<p class="integration-meta">
							<?php
							/* translators: %s: Time */
							printf( esc_html__( 'Last checked: %s', 'aqop-core' ), esc_html( $integration['last_checked'] ) );
							?>
						</p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Charts Section -->
	<div class="aqop-section">
		<h2 class="aqop-section-title"><?php esc_html_e( 'Analytics Overview', 'aqop-core' ); ?></h2>
		
		<div class="aqop-charts-grid">
			
			<!-- Events Timeline -->
			<div class="aqop-chart-card chart-large">
				<div class="chart-header">
					<h3 class="chart-title"><?php esc_html_e( 'Events Timeline', 'aqop-core' ); ?></h3>
					<div class="chart-actions">
						<button type="button" class="button button-small" id="refresh-timeline">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Refresh', 'aqop-core' ); ?>
						</button>
					</div>
				</div>
				<div class="chart-body">
					<canvas id="eventsTimelineChart"></canvas>
				</div>
			</div>

			<!-- Module Distribution -->
			<div class="aqop-chart-card">
				<div class="chart-header">
					<h3 class="chart-title"><?php esc_html_e( 'Module Distribution', 'aqop-core' ); ?></h3>
				</div>
				<div class="chart-body">
					<canvas id="moduleDistributionChart"></canvas>
				</div>
			</div>

			<!-- Event Types -->
			<div class="aqop-chart-card">
				<div class="chart-header">
					<h3 class="chart-title"><?php esc_html_e( 'Top Event Types', 'aqop-core' ); ?></h3>
				</div>
				<div class="chart-body">
					<canvas id="eventTypesChart"></canvas>
				</div>
			</div>

		</div>
	</div>

	<!-- Quick Actions -->
	<div class="aqop-section">
		<h2 class="aqop-section-title"><?php esc_html_e( 'Quick Actions', 'aqop-core' ); ?></h2>
		
		<div class="aqop-quick-actions">
			<button type="button" class="button button-primary" id="clear-cache">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Clear Caches', 'aqop-core' ); ?>
			</button>
			
			<button type="button" class="button" id="check-integrations">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Test Integrations', 'aqop-core' ); ?>
			</button>
			
			<?php if ( current_user_can( 'export_analytics' ) ) : ?>
				<button type="button" class="button" id="export-data">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Data', 'aqop-core' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

</div>

