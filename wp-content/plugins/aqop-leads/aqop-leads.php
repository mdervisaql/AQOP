<?php
/**
 * Plugin Name: Operation Platform - Leads Module
 * Plugin URI: https://aqleeat.com
 * Description: Comprehensive leads management system with analytics, Airtable sync, and notifications
 * Version: 1.0.0
 * Author: Muhammed Derviş
 * Author URI: https://aqleeat.com
 * Requires Plugins: aqop-core
 * Text Domain: aqop-leads
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AQOP_Leads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Check if Core is active.
if ( ! class_exists( 'AQOP_Core' ) ) {
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Plugin name */
							__( '<strong>Operation Platform - Leads Module</strong> requires <strong>Operation Platform Core</strong> to be installed and activated.', 'aqop-leads' )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

// Define plugin constants.
define( 'AQOP_LEADS_VERSION', '1.0.0' );
define( 'AQOP_LEADS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AQOP_LEADS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AQOP_LEADS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load dependencies.
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-activator.php';
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-core.php';

/**
 * Activation hook.
 *
 * Runs when the plugin is activated.
 */
register_activation_hook( __FILE__, array( 'AQOP_Leads_Activator', 'activate' ) );

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated.
 */
register_deactivation_hook( __FILE__, array( 'AQOP_Leads_Deactivator', 'deactivate' ) );

/**
 * Initialize the Leads Module.
 *
 * Loads after core platform (priority 20).
 *
 * @since 1.0.0
 */
function aqop_leads_init() {
	AQOP_Leads_Core::get_instance();
}
add_action( 'plugins_loaded', 'aqop_leads_init', 20 );

