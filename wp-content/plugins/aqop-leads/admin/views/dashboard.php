<?php
/**
 * Analytics Dashboard
 *
 * Comprehensive dashboard with statistics, charts, and activity feed.
 *
 * @package AQOP_Leads
 * @since   1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check permission
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'aqop-leads' ) );
}

// === ANALYTICS DASHBOARD (Phase 4.2) ===

// Get statistics
global $wpdb;

// Total leads
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_leads = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads" );

// By status
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$status_counts = $wpdb->get_results(
	"SELECT s.status_code, s.status_name_en, s.color, COUNT(l.id) as count
	 FROM {$wpdb->prefix}aq_leads_status s
	 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.status_id
	 GROUP BY s.id
	 ORDER BY s.status_order ASC"
);

// By source
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$source_counts = $wpdb->get_results(
	"SELECT s.source_name, COUNT(l.id) as count
	 FROM {$wpdb->prefix}aq_leads_sources s
	 LEFT JOIN {$wpdb->prefix}aq_leads l ON s.id = l.source_id
	 GROUP BY s.id
	 ORDER BY count DESC
	 LIMIT 5"
);

// This month vs last month
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$this_month = $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads 
	 WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
	 AND YEAR(created_at) = YEAR(CURRENT_DATE())"
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$last_month = $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads 
	 WHERE MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
	 AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)"
);

$month_change = $last_month > 0 ? round( ( ( $this_month - $last_month ) / $last_month ) * 100, 1 ) : 0;

// Converted leads
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$converted = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads l
		 LEFT JOIN {$wpdb->prefix}aq_leads_status s ON l.status_id = s.id
		 WHERE s.status_code = %s",
		'converted'
	)
);

// Conversion rate
$conversion_rate = $total_leads > 0 ? round( ( $converted / $total_leads ) * 100, 1 ) : 0;

// Recent activity (using event logger if available)
$recent_activity = array();
if ( class_exists( 'AQOP_Event_Logger' ) ) {
	$recent_activity = AQOP_Event_Logger::query(
		array(
			'module'  => 'leads',
			'limit'   => 10,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		)
	);
}

// Leads per day (last 30 days)
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$daily_leads = $wpdb->get_results(
	"SELECT DATE(created_at) as date, COUNT(*) as count
	 FROM {$wpdb->prefix}aq_leads
	 WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
	 GROUP BY DATE(created_at)
	 ORDER BY date ASC"
);
?>

<div class="wrap aqop-dashboard">
	<h1>
		<span class="dashicons dashicons-chart-area"></span>
		<?php esc_html_e( 'Leads Analytics Dashboard', 'aqop-leads' ); ?>
	</h1>
	
	<p class="description">
		<?php esc_html_e( 'Real-time insights into your lead generation and conversion performance.', 'aqop-leads' ); ?>
	</p>

	<!-- KPI Cards -->
	<div class="aqop-kpi-cards">
		<div class="aqop-kpi-card">
			<div class="kpi-icon kpi-icon-primary">
				<span class="dashicons dashicons-groups"></span>
			</div>
			<div class="kpi-content">
				<div class="kpi-value"><?php echo number_format_i18n( $total_leads ); ?></div>
				<div class="kpi-label"><?php esc_html_e( 'Total Leads', 'aqop-leads' ); ?></div>
			</div>
		</div>

		<div class="aqop-kpi-card">
			<div class="kpi-icon kpi-icon-success">
				<span class="dashicons dashicons-calendar-alt"></span>
			</div>
			<div class="kpi-content">
				<div class="kpi-value"><?php echo number_format_i18n( $this_month ); ?></div>
				<div class="kpi-label"><?php esc_html_e( 'This Month', 'aqop-leads' ); ?></div>
				<?php if ( 0 !== $month_change ) : ?>
					<div class="kpi-change <?php echo $month_change > 0 ? 'positive' : 'negative'; ?>">
						<?php echo $month_change > 0 ? '↑' : '↓'; ?> <?php echo abs( $month_change ); ?>%
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="aqop-kpi-card">
			<div class="kpi-icon kpi-icon-info">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="kpi-content">
				<div class="kpi-value"><?php echo number_format_i18n( $converted ); ?></div>
				<div class="kpi-label"><?php esc_html_e( 'Converted', 'aqop-leads' ); ?></div>
			</div>
		</div>

		<div class="aqop-kpi-card">
			<div class="kpi-icon kpi-icon-warning">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="kpi-content">
				<div class="kpi-value"><?php echo esc_html( $conversion_rate ); ?>%</div>
				<div class="kpi-label"><?php esc_html_e( 'Conversion Rate', 'aqop-leads' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="aqop-charts-row">
		<!-- Leads Timeline Chart -->
		<div class="aqop-chart-container">
			<h2>
				<span class="dashicons dashicons-chart-line"></span>
				<?php esc_html_e( 'Leads Timeline (Last 30 Days)', 'aqop-leads' ); ?>
			</h2>
			<canvas id="leadsTimelineChart"></canvas>
		</div>

		<!-- Status Distribution Chart -->
		<div class="aqop-chart-container">
			<h2>
				<span class="dashicons dashicons-chart-pie"></span>
				<?php esc_html_e( 'Status Distribution', 'aqop-leads' ); ?>
			</h2>
			<canvas id="statusDistributionChart"></canvas>
		</div>
	</div>

	<!-- Second Charts Row -->
	<div class="aqop-charts-row">
		<!-- Top Sources Chart -->
		<div class="aqop-chart-container">
			<h2>
				<span class="dashicons dashicons-chart-bar"></span>
				<?php esc_html_e( 'Top 5 Lead Sources', 'aqop-leads' ); ?>
			</h2>
			<canvas id="topSourcesChart"></canvas>
		</div>

		<!-- Recent Activity Feed -->
		<div class="aqop-activity-container">
			<h2>
				<span class="dashicons dashicons-backup"></span>
				<?php esc_html_e( 'Recent Activity', 'aqop-leads' ); ?>
			</h2>
			<div class="aqop-activity-feed">
				<?php if ( empty( $recent_activity['results'] ) ) : ?>
					<p class="no-activity"><?php esc_html_e( 'No recent activity.', 'aqop-leads' ); ?></p>
				<?php else : ?>
					<?php foreach ( $recent_activity['results'] as $event ) : ?>
						<div class="activity-item">
							<span class="activity-icon">
								<?php
								$icon = 'admin-generic';
								if ( strpos( $event->event_code, 'created' ) !== false ) {
									$icon = 'plus-alt';
								} elseif ( strpos( $event->event_code, 'updated' ) !== false || strpos( $event->event_code, 'edited' ) !== false ) {
									$icon = 'edit';
								} elseif ( strpos( $event->event_code, 'deleted' ) !== false ) {
									$icon = 'trash';
								} elseif ( strpos( $event->event_code, 'status' ) !== false ) {
									$icon = 'flag';
								}
								?>
								<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
							</span>
							<div class="activity-content">
								<div class="activity-title">
									<?php echo esc_html( $event->event_name ); ?>
								</div>
								<div class="activity-meta">
									<?php
									printf(
										/* translators: 1: user name, 2: time ago */
										esc_html__( 'by %1$s • %2$s', 'aqop-leads' ),
										esc_html( $event->user_name ? $event->user_name : 'System' ),
										esc_html( human_time_diff( strtotime( $event->created_at ), current_time( 'timestamp' ) ) . ' ago' )
									);
									?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="aqop-quick-actions">
		<h2><?php esc_html_e( 'Quick Actions', 'aqop-leads' ); ?></h2>
		<div class="quick-actions-grid">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-leads' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'View All Leads', 'aqop-leads' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-leads&filter_status=pending' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-clock"></span>
				<?php esc_html_e( 'Pending Leads', 'aqop-leads' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-leads-form' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-plus-alt"></span>
				<?php esc_html_e( 'Add New Lead', 'aqop-leads' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-import-export' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export Data', 'aqop-leads' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-settings' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Settings', 'aqop-leads' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aqop-leads-api' ) ); ?>" class="quick-action">
				<span class="dashicons dashicons-rest-api"></span>
				<?php esc_html_e( 'API Documentation', 'aqop-leads' ); ?>
			</a>
		</div>
	</div>

</div>
<!-- === END ANALYTICS DASHBOARD === -->

<script>
jQuery(document).ready(function($) {
	// Check if Chart.js is loaded
	if (typeof Chart === 'undefined') {
		console.error('Chart.js is not loaded');
		return;
	}

	// Prepare data for charts
	var dailyLeadsData = <?php echo wp_json_encode( $daily_leads ); ?>;
	var statusCountsData = <?php echo wp_json_encode( $status_counts ); ?>;
	var sourceCountsData = <?php echo wp_json_encode( $source_counts ); ?>;

	// Timeline Chart
	if (document.getElementById('leadsTimelineChart')) {
		new Chart(document.getElementById('leadsTimelineChart'), {
			type: 'line',
			data: {
				labels: dailyLeadsData.map(function(d) { return d.date; }),
				datasets: [{
					label: '<?php echo esc_js( __( 'Leads', 'aqop-leads' ) ); ?>',
					data: dailyLeadsData.map(function(d) { return d.count; }),
					borderColor: '#2271b1',
					backgroundColor: 'rgba(34, 113, 177, 0.1)',
					tension: 0.4,
					fill: true,
					pointBackgroundColor: '#2271b1',
					pointBorderColor: '#fff',
					pointBorderWidth: 2,
					pointRadius: 4,
					pointHoverRadius: 6
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						mode: 'index',
						intersect: false
					}
				},
				scales: {
					y: { 
						beginAtZero: true,
						ticks: {
							stepSize: 1
						}
					},
					x: {
						grid: {
							display: false
						}
					}
				}
			}
		});
	}

	// Status Distribution Pie Chart
	if (document.getElementById('statusDistributionChart')) {
		new Chart(document.getElementById('statusDistributionChart'), {
			type: 'doughnut',
			data: {
				labels: statusCountsData.map(function(s) { return s.status_name_en; }),
				datasets: [{
					data: statusCountsData.map(function(s) { return s.count; }),
					backgroundColor: statusCountsData.map(function(s) { return s.color; }),
					borderWidth: 2,
					borderColor: '#fff'
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'right',
						labels: {
							padding: 15,
							usePointStyle: true
						}
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var label = context.label || '';
								var value = context.parsed || 0;
								var total = context.dataset.data.reduce((a, b) => a + b, 0);
								var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
								return label + ': ' + value + ' (' + percentage + '%)';
							}
						}
					}
				}
			}
		});
	}

	// Top Sources Bar Chart
	if (document.getElementById('topSourcesChart')) {
		new Chart(document.getElementById('topSourcesChart'), {
			type: 'bar',
			data: {
				labels: sourceCountsData.map(function(s) { return s.source_name; }),
				datasets: [{
					label: '<?php echo esc_js( __( 'Leads', 'aqop-leads' ) ); ?>',
					data: sourceCountsData.map(function(s) { return s.count; }),
					backgroundColor: [
						'#48bb78',
						'#4299e1',
						'#ed8936',
						'#f56565',
						'#9f7aea'
					],
					borderRadius: 6
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false }
				},
				scales: {
					y: { 
						beginAtZero: true,
						ticks: {
							stepSize: 1
						}
					},
					x: {
						grid: {
							display: false
						}
					}
				}
			}
		});
	}
});
</script>

