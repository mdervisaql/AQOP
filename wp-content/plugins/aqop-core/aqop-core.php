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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'AQOP_VERSION', '1.0.0' );
define( 'AQOP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AQOP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AQOP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load dependencies.
require_once AQOP_PLUGIN_DIR . 'includes/class-activator.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once AQOP_PLUGIN_DIR . 'includes/class-aqop-core.php';

/**
 * Activation hook.
 *
 * Runs when the plugin is activated.
 */
register_activation_hook( __FILE__, array( 'AQOP_Activator', 'activate' ) );

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated.
 */
register_deactivation_hook( __FILE__, array( 'AQOP_Deactivator', 'deactivate' ) );

/**
 * Initialize the plugin.
 *
 * Load the main plugin class and start the platform.
 *
 * @since 1.0.0
 */
function aqop_init() {
	AQOP_Core::get_instance();
}
add_action( 'plugins_loaded', 'aqop_init' );

