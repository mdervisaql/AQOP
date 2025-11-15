<?php
/**
 * Plugin Activator Class
 *
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Activator class.
 *
 * Handles plugin activation tasks.
 *
 * @since 1.0.0
 */
class AQOP_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Runs installation tasks and sets up the plugin for first use.
	 * This method is called when the plugin is activated.
	 *
	 * @since  1.0.0
	 * @static
	 */
	public static function activate() {
		// Load the installer class.
		require_once AQOP_PLUGIN_DIR . 'includes/class-installer.php';

		// Run installation.
		$install_result = AQOP_Installer::install();

		if ( ! $install_result ) {
			// Installation failed - requirements not met.
			deactivate_plugins( AQOP_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'Operation Platform Core could not be activated due to system requirements not being met.', 'aqop-core' ),
				esc_html__( 'Plugin Activation Error', 'aqop-core' ),
				array( 'back_link' => true )
			);
		}

		// Load roles manager and create custom roles.
		require_once AQOP_PLUGIN_DIR . 'includes/authentication/class-roles-manager.php';
		AQOP_Roles_Manager::create_roles();

		// Set activation flag for redirect to welcome page.
		set_transient( 'aqop_activation_redirect', true, 30 );

		/**
		 * Fires after the plugin has been successfully activated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_activated' );

		// Log activation event.
		self::log_activation();
	}

	/**
	 * Log plugin activation.
	 *
	 * Records the plugin activation in WordPress options for tracking.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function log_activation() {
		$activation_log = get_option( 'aqop_activation_log', array() );

		$activation_log[] = array(
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
			'version'   => AQOP_VERSION,
		);

		update_option( 'aqop_activation_log', $activation_log );
	}
}

