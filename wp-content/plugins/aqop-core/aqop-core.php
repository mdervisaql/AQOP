<?php
/**
 * Plugin Name: Operation Platform Core
 * Plugin URI: https://aqleeat.com
 * Description: Core functionality for Operation Platform - Event System, Notifications, Integrations
 * Version: 1.0.0
 * Author: Muhammed Derviş
 * Author URI: https://aqleeat.com
 * Text Domain: aqop-core
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AQOP_Core
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define('AQOP_VERSION', '1.0.0');
define('AQOP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AQOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AQOP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load dependencies.
require_once AQOP_PLUGIN_DIR . 'includes/class-activator.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-aqop-core.php';
require_once AQOP_PLUGIN_DIR . 'includes/notifications/class-notification-system.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-session-manager.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-activity-tracker.php';
require_once plugin_dir_path(__FILE__) . 'api/class-core-api.php';
require_once plugin_dir_path(__FILE__) . 'api/class-monitoring-api.php';
require_once plugin_dir_path(__FILE__) . 'api/class-users-api.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-frontend-integration.php';
require_once AQOP_PLUGIN_DIR . 'admin/class-monitoring-admin.php';

/**
 * Initialize API.
 */
function aqop_core_api_init()
{
	$core_api = new AQOP_Core_API();
	add_action('rest_api_init', array($core_api, 'register_routes'));

	$monitoring_api = new AQOP_Monitoring_API();
	add_action('rest_api_init', array($monitoring_api, 'register_routes'));

	$users_api = new AQOP_Users_API();
	add_action('rest_api_init', array($users_api, 'register_routes'));
	// Frontend Integration.
	AQOP_Frontend_Integration::init();
}
add_action('plugins_loaded', 'aqop_core_api_init');

/**
 * Initialize Admin.
 */
function aqop_admin_init()
{
	if (is_admin()) {
		AQOP_Monitoring_Admin::init();
	}
}
add_action('init', 'aqop_admin_init');

/**
 * Activation hook.
 *
 * Runs when the plugin is activated.
 */
register_activation_hook(__FILE__, array('AQOP_Activator', 'activate'));

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated.
 */
register_deactivation_hook(__FILE__, array('AQOP_Deactivator', 'deactivate'));

/**
 * Initialize the plugin.
 *
 * Load the main plugin class and start the platform.
 *
 * @since 1.0.0
 */
function aqop_init()
{
	AQOP_Core::get_instance();
	AQOP_Activity_Tracker::init();
}
add_action('plugins_loaded', 'aqop_init');

