<?php
/**
 * Main Plugin Class
 *
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Core class.
 *
 * Main class that bootstraps the Operation Platform Core plugin.
 *
 * @since 1.0.0
 */
class AQOP_Core {

	/**
	 * The single instance of the class.
	 *
	 * @var AQOP_Core
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $version;

	/**
	 * Main AQOP_Core Instance.
	 *
	 * Ensures only one instance of AQOP_Core is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @return AQOP_Core Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
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
	private function __construct() {
		$this->version = AQOP_VERSION;
		$this->load_dependencies();
		$this->init_hooks();
		$this->run();
	}

	/**
	 * Load required dependencies.
	 *
	 * Include required core files used in admin and on the frontend.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		/**
		 * Load the installer class responsible for database setup.
		 */
		require_once AQOP_PLUGIN_DIR . 'includes/class-installer.php';

		/**
		 * Load the Event Logger class for event tracking.
		 */
		require_once AQOP_PLUGIN_DIR . 'includes/events/class-event-logger.php';

		/**
		 * Load authentication classes for roles and permissions.
		 */
		require_once AQOP_PLUGIN_DIR . 'includes/authentication/class-roles-manager.php';
		require_once AQOP_PLUGIN_DIR . 'includes/authentication/class-permissions.php';

		/**
		 * Load security classes for frontend protection.
		 */
		require_once AQOP_PLUGIN_DIR . 'includes/security/class-frontend-guard.php';

		/**
		 * Load integrations hub for external services.
		 */
		require_once AQOP_PLUGIN_DIR . 'includes/integrations/class-integrations-hub.php';

	/**
	 * Load admin classes.
	 */
	if ( is_admin() ) {
		require_once AQOP_PLUGIN_DIR . 'admin/control-center/class-control-center.php';
		// Initialize Control Center to register admin menu.
		AQOP_Control_Center::init();
	}

		/**
		 * Additional dependencies will be loaded here as we build the platform.
		 * Examples:
		 * - Notification Engine
		 */
	}

	/**
	 * Initialize hooks.
	 *
	 * Hook into WordPress actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_hooks() {
		// Load plugin textdomain for translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Add custom actions here as needed.
		do_action( 'aqop_core_loaded', $this );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'aqop-core',
			false,
			dirname( AQOP_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Run the plugin.
	 *
	 * Execute the plugin's main functionality.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function run() {
		/**
		 * Fires after the core plugin has been initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param AQOP_Core $this The main plugin instance.
		 */
		do_action( 'aqop_core_run', $this );
	}

	/**
	 * Get plugin version.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return string Plugin version number.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __wakeup() {}
}

