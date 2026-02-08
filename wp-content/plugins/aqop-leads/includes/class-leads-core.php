<?php
/**
 * Leads Module Core Class
 *
 * Main class for the Leads Module.
 * Manages initialization and dependency loading.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Core class.
 *
 * Main Leads Module class.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Core
{

	/**
	 * The single instance of the class.
	 *
	 * @var AQOP_Leads_Core
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Module version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $version;

	/**
	 * Main instance.
	 *
	 * Ensures only one instance is loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @return AQOP_Leads_Core Main instance.
	 */
	public static function get_instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct()
	{
		$this->version = AQOP_LEADS_VERSION;

		// Check core dependency.
		if (!$this->check_core_dependency()) {
			return;
		}

		$this->load_dependencies();
		$this->init_hooks();
		$this->register_module();
	}

	/**
	 * Check core dependency.
	 *
	 * Verifies that Operation Platform Core is available.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return bool True if core is available.
	 */
	private function check_core_dependency()
	{
		if (!class_exists('AQOP_Event_Logger')) {
			add_action(
				'admin_notices',
				function () {
					?>
				<div class="notice notice-error">
					<p><?php esc_html_e('Leads Module requires Operation Platform Core classes to be loaded.', 'aqop-leads'); ?></p>
				</div>
				<?php
				}
			);
			return false;
		}

		return true;
	}

	/**
	 * Load required dependencies.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies()
	{
		/**
		 * Load the installer.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-installer.php';

		/**
		 * Load the leads manager.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-manager.php';

		/**
		 * Load admin classes if in admin.
		 */
		if (is_admin()) {
			require_once AQOP_LEADS_PLUGIN_DIR . 'admin/class-leads-admin.php';
			// Initialize Leads Admin to register menus and hooks.
			new AQOP_Leads_Admin();

			require_once AQOP_LEADS_PLUGIN_DIR . 'admin/class-notifications-admin.php';
			$notifications_admin = new AQOP_Notifications_Admin();
			$notifications_admin->init();
		}

		// === PUBLIC FORM (Phase 3.2) ===
		/**
		 * Load public form class.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'public/class-public-form.php';
		// === END PUBLIC FORM ===

		/**
		 * Load notification installer.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-notification-installer.php';

		/**
		 * Load notification managers.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-notification-manager.php';
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-push-notification-manager.php';

		/**
		 * Load lead scoring.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-lead-scoring.php';

		/**
		 * Load automation engine.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-automation-engine.php';

		/**
		 * Load reports engine.
		 */
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-reports.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_hooks()
	{
		add_action('init', array($this, 'load_textdomain'));

		// Initialize Lead Scoring
		AQOP_Lead_Scoring::init();

		// Initialize Automation Engine
		$automation_engine = new AQOP_Automation_Engine();
		add_action('aqop_lead_created', array($automation_engine, 'process_trigger'), 10, 2);
		add_action('aqop_lead_updated', array($automation_engine, 'process_trigger'), 10, 3);
		add_action('aqop_lead_status_changed', array($automation_engine, 'process_trigger'), 10, 3);
		add_action('aqop_lead_score_changed', array($automation_engine, 'process_trigger'), 10, 3);
		add_action('aqop_lead_score_changed', array($automation_engine, 'process_trigger'), 10, 3);
		add_action('aqop_communication_logged', array($automation_engine, 'process_trigger'), 10, 2);

		// Cron Hooks
		add_action('aqop_hourly_cron', array($automation_engine, 'check_no_response_leads'));
		add_action('aqop_hourly_cron', array($automation_engine, 'check_overdue_follow_ups'));

		// Bulk WhatsApp Cron
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-bulk-whatsapp.php';
		$bulk_whatsapp = new AQOP_Bulk_WhatsApp();
		add_action('aqop_process_bulk_whatsapp_job', array($bulk_whatsapp, 'process_job'));

		// Schedule events if not scheduled
		if (!wp_next_scheduled('aqop_hourly_cron')) {
			wp_schedule_event(time(), 'hourly', 'aqop_hourly_cron');
		}

		// === REST API (Phase 3.1) ===
		add_action('rest_api_init', array($this, 'register_api_routes'));
		// === END REST API ===

		/**
		 * Fires after Leads Module core has been initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param AQOP_Leads_Core $this The main instance.
		 */
		do_action('aqop_leads_core_loaded', $this);

		// Check for DB updates
		$this->check_db_updates();
	}

	/**
	 * Check for database updates.
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function check_db_updates()
	{
		$installed_version = get_option('aqop_notification_version');
		if (version_compare($installed_version, '1.1.0', '<')) {
			AQOP_Notification_Installer::install();
			update_option('aqop_notification_version', '1.1.0');
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain(
			'aqop-leads',
			false,
			dirname(AQOP_LEADS_PLUGIN_BASENAME) . '/languages/'
		);
	}

	// === REST API (Phase 3.1) ===

	/**
	 * Register REST API routes.
	 *
	 * @since  1.0.6
	 * @access public
	 */
	public function register_api_routes()
	{
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-leads-api.php';
		$api = new AQOP_Leads_API();
		$api->register_routes();

		// Register Users API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-users-api.php';
		$users_api = new AQOP_Leads_Users_API();
		$users_api->register_routes();

		// Register Meta Webhook API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-meta-webhook-api.php';
		new AQOP_Meta_Webhook_API();

		// Register Activity API
		require_once WP_PLUGIN_DIR . '/aqop-core/includes/class-activity-tracker.php';
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-activity-api.php';
		$activity_api = new AQOP_Activity_API();
		$activity_api->register_routes();

		// Register Notifications API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-notifications-api.php';
		$notifications_api = new AQOP_Notifications_API();
		$notifications_api->register_routes();

		// Register Communications API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-communications-api.php';
		$communications_api = new AQOP_Communications_API();
		$communications_api->register_routes();

		// Register WhatsApp API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-whatsapp-api.php';
		$whatsapp_api = new AQOP_WhatsApp_API();
		$whatsapp_api->register_routes();

		// Register Facebook API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-facebook-api.php';
		$facebook_api = new AQOP_Facebook_API();
		$facebook_api->register_routes();

		// Register Bulk WhatsApp API
		require_once AQOP_LEADS_PLUGIN_DIR . 'api/class-bulk-whatsapp-api.php';
		$bulk_whatsapp_api = new AQOP_Bulk_WhatsApp_API();
		$bulk_whatsapp_api->register_routes();
	}

	// === END REST API ===

	/**
	 * Register module with core platform.
	 *
	 * Registers this module in the core's module registry.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function register_module()
	{
		// Log module activation.
		if (class_exists('AQOP_Event_Logger')) {
			AQOP_Event_Logger::log(
				'leads',
				'module_loaded',
				'module',
				0,
				array(
					'version' => $this->version,
					'status' => 'active',
				)
			);
		}

		/**
		 * Fires after module has been registered.
		 *
		 * @since 1.0.0
		 */
		do_action('aqop_leads_registered');
	}

	/**
	 * Get module version.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string Module version.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function __clone()
	{
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __wakeup()
	{
	}
}

