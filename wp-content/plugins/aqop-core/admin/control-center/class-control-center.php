<?php
/**
 * Control Center Class
 *
 * Main dashboard for Operation Platform administration.
 * Provides system overview, analytics, and monitoring.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Control_Center class.
 *
 * Manages the Operation Control Center dashboard.
 * Displays system stats, module health, and integration status.
 *
 * @since 1.0.0
 */
class AQOP_Control_Center {

	/**
	 * Initialize Control Center.
	 *
	 * @since 1.0.0
	 * @static
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register menu page.
	 *
	 * Adds the Control Center to WordPress admin menu.
	 *
	 * @since 1.0.0
	 * @static
	 */
	public static function register_menu_page() {
		add_menu_page(
			__( 'مركز العمليات', 'aqop-core' ),           // Page title.
			__( 'مركز العمليات', 'aqop-core' ),           // Menu title.
			'manage_options',                               // Capability.
			'aqop-control-center',                          // Menu slug.
			array( __CLASS__, 'render_overview' ),         // Callback.
			'dashicons-dashboard',                          // Icon.
			2                                               // Position.
		);
	}

	/**
	 * Render overview page.
	 *
	 * Displays the main Control Center dashboard.
	 *
	 * @since 1.0.0
	 * @static
	 */
	public static function render_overview() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'عذراً، ليس لديك صلاحية للوصول لهذه الصفحة', 'aqop-core' ),
				esc_html__( 'Access Denied', 'aqop-core' ),
				array( 'response' => 403 )
			);
		}

		// Get system stats.
		$stats = self::get_system_stats();

		// Load template.
		include AQOP_PLUGIN_DIR . 'admin/views/control-center-overview.php';
	}

	/**
	 * Get system statistics.
	 *
	 * Retrieves comprehensive system stats for the dashboard.
	 * Results are cached for 30 seconds.
	 *
	 * @since 1.0.0
	 * @static
	 * @return array System statistics array.
	 */
	public static function get_system_stats() {
		// Check cache.
		$cache_key = 'aqop_system_stats';
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$stats = array();

		// Platform status.
		$errors_24h = class_exists( 'AQOP_Event_Logger' ) ? AQOP_Event_Logger::count_errors_24h() : 0;
		if ( $errors_24h > 10 ) {
			$stats['platform_status'] = 'error';
		} elseif ( $errors_24h > 0 ) {
			$stats['platform_status'] = 'warning';
		} else {
			$stats['platform_status'] = 'active';
		}

		// Uptime (days since installation).
		$install_date = get_option( 'aqop_install_date' );
		if ( $install_date ) {
			$now = new DateTime();
			$install = new DateTime( $install_date );
			$interval = $now->diff( $install );
			$stats['uptime_days'] = $interval->days;
		} else {
			$stats['uptime_days'] = 0;
		}

		// Events today.
		$stats['events_today'] = class_exists( 'AQOP_Event_Logger' ) ? AQOP_Event_Logger::count_events_today() : 0;

		// Active users (last 24h).
		global $wpdb;
		$time_24h_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['active_users'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) 
				FROM {$wpdb->prefix}aq_events_log 
				WHERE created_at >= %s AND user_id > 0",
				$time_24h_ago
			)
		);

		// Errors and warnings.
		$stats['errors_24h'] = $errors_24h;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats['warnings_count'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$wpdb->prefix}aq_events_log e
				LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
				WHERE e.created_at >= %s AND et.severity = 'warning'",
				$time_24h_ago
			)
		);

		// Database size.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$db_size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
				FROM information_schema.TABLES 
				WHERE table_schema = %s 
				AND table_name LIKE %s",
				DB_NAME,
				$wpdb->prefix . 'aq_%'
			)
		);
		$stats['database_size'] = $db_size ? $db_size : 0;

		// Last backup.
		$stats['last_backup'] = get_option( 'aqop_last_backup', null );

		// Modules health.
		$stats['modules_health'] = self::get_modules_health();

		// Integrations status.
		$stats['integrations_status'] = self::get_integrations_health();

		// Cache for 30 seconds.
		set_transient( $cache_key, $stats, 30 );

		return $stats;
	}

	/**
	 * Get modules health status.
	 *
	 * Checks status of all Operation Platform modules.
	 *
	 * @since 1.0.0
	 * @static
	 * @return array Modules health array.
	 */
	public static function get_modules_health() {
		$modules = array();

		// Core module.
		$modules[] = array(
			'name'        => __( 'Core Platform', 'aqop-core' ),
			'slug'        => 'core',
			'status'      => 'ok',
			'version'     => AQOP_VERSION,
			'description' => __( 'Core functionality active', 'aqop-core' ),
		);

		// Check for other modules (plugins).
		$all_plugins = get_plugins();

		// Leads module.
		if ( isset( $all_plugins['aqop-leads/aqop-leads.php'] ) ) {
			$is_active = is_plugin_active( 'aqop-leads/aqop-leads.php' );
			$modules[] = array(
				'name'        => __( 'Leads Module', 'aqop-core' ),
				'slug'        => 'leads',
				'status'      => $is_active ? 'ok' : 'inactive',
				'version'     => $all_plugins['aqop-leads/aqop-leads.php']['Version'] ?? '0.0.0',
				'description' => $is_active ? __( 'Active and operational', 'aqop-core' ) : __( 'Not activated', 'aqop-core' ),
			);
		}

		// Training module.
		if ( isset( $all_plugins['aqop-training/aqop-training.php'] ) ) {
			$is_active = is_plugin_active( 'aqop-training/aqop-training.php' );
			$modules[] = array(
				'name'        => __( 'Training Module', 'aqop-core' ),
				'slug'        => 'training',
				'status'      => $is_active ? 'ok' : 'inactive',
				'version'     => $all_plugins['aqop-training/aqop-training.php']['Version'] ?? '0.0.0',
				'description' => $is_active ? __( 'Active and operational', 'aqop-core' ) : __( 'Not activated', 'aqop-core' ),
			);
		}

		return $modules;
	}

	/**
	 * Get integrations health status.
	 *
	 * Checks connection status for all external integrations.
	 *
	 * @since 1.0.0
	 * @static
	 * @return array Integrations health array.
	 */
	public static function get_integrations_health() {
		$integrations = array();

		if ( ! class_exists( 'AQOP_Integrations_Hub' ) ) {
			return $integrations;
		}

		// Airtable.
		$airtable_status = AQOP_Integrations_Hub::get_integration_status( 'airtable' );
		$integrations['airtable'] = array(
			'name'         => 'Airtable',
			'status'       => $airtable_status['status'],
			'message'      => $airtable_status['message'],
			'last_checked' => $airtable_status['last_checked'],
		);

		// Dropbox.
		$dropbox_status = AQOP_Integrations_Hub::get_integration_status( 'dropbox' );
		$integrations['dropbox'] = array(
			'name'         => 'Dropbox',
			'status'       => $dropbox_status['status'],
			'message'      => $dropbox_status['message'],
			'last_checked' => $dropbox_status['last_checked'],
		);

		// Telegram.
		$telegram_status = AQOP_Integrations_Hub::get_integration_status( 'telegram' );
		$integrations['telegram'] = array(
			'name'         => 'Telegram',
			'status'       => $telegram_status['status'],
			'message'      => $telegram_status['message'],
			'last_checked' => $telegram_status['last_checked'],
		);

		return $integrations;
	}

	/**
	 * Enqueue assets.
	 *
	 * Loads CSS and JavaScript for the Control Center.
	 *
	 * @since 1.0.0
	 * @static
	 * @param  string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		// Only load on Control Center page.
		if ( 'toplevel_page_aqop-control-center' !== $hook ) {
			return;
		}

		// Chart.js from CDN.
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// ApexCharts from CDN.
		wp_enqueue_script(
			'apexcharts',
			'https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js',
			array(),
			'3.44.0',
			true
		);

		// Control Center CSS.
		wp_enqueue_style(
			'aqop-control-center',
			AQOP_PLUGIN_URL . 'admin/css/control-center.css',
			array(),
			AQOP_VERSION
		);

		// Control Center JS.
		wp_enqueue_script(
			'aqop-control-center',
			AQOP_PLUGIN_URL . 'admin/js/control-center.js',
			array( 'jquery', 'chartjs' ),
			AQOP_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'aqop-control-center',
			'aqopControlCenter',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'aqop_control_center' ),
				'restUrl'   => rest_url( 'aqop/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

