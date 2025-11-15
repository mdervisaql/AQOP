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
		if ( ! current_user_can( 'view_control_center' ) ) {
			return;
		}

		add_submenu_page(
			'aqop-control-center',
			__( 'Leads Management', 'aqop-leads' ),
			__( 'Leads', 'aqop-leads' ),
			'view_control_center',
			'aqop-leads',
			array( $this, 'render_leads_page' )
		);
	}

	/**
	 * Render leads page.
	 *
	 * @since 1.0.0
	 */
	public function render_leads_page() {
		?>
		<div class="wrap aqop-leads-admin">
			<h1>
				<span class="dashicons dashicons-businessman"></span>
				<?php esc_html_e( 'Leads Management', 'aqop-leads' ); ?>
			</h1>
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
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $results['results'] as $lead ) : ?>
					<tr>
						<td><?php echo esc_html( $lead->id ); ?></td>
						<td><strong><?php echo esc_html( $lead->name ); ?></strong></td>
						<td><?php echo esc_html( $lead->email ); ?></td>
						<td><?php echo esc_html( $lead->phone ); ?></td>
						<td>
							<span class="status-badge" style="background-color: <?php echo esc_attr( $lead->status_color ); ?>;">
								<?php echo esc_html( $lead->status_name_en ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $lead->country_name_en ); ?></td>
						<td><?php echo esc_html( $lead->created_at ); ?></td>
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

		wp_enqueue_style(
			'aqop-leads-admin',
			AQOP_LEADS_PLUGIN_URL . 'admin/css/leads-admin.css',
			array(),
			AQOP_LEADS_VERSION
		);

		wp_enqueue_script(
			'aqop-leads-admin',
			AQOP_LEADS_PLUGIN_URL . 'admin/js/leads-admin.js',
			array( 'jquery' ),
			AQOP_LEADS_VERSION,
			true
		);
	}
}

// Initialize admin.
new AQOP_Leads_Admin();

