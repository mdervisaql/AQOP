<?php
/**
 * Leads Module Activator Class
 *
 * Fired during plugin activation.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Activator class.
 *
 * Handles Leads Module activation.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Activator
{

	/**
	 * Activate the plugin.
	 *
	 * Runs installation and setup tasks.
	 *
	 * @since  1.0.0
	 * @static
	 */
	public static function activate()
	{
		// Check if core is active.
		if (!class_exists('AQOP_Core')) {
			deactivate_plugins(AQOP_LEADS_PLUGIN_BASENAME);
			wp_die(
				esc_html__('Operation Platform - Leads Module requires Operation Platform Core to be installed and activated.', 'aqop-leads'),
				esc_html__('Plugin Activation Error', 'aqop-leads'),
				array('back_link' => true)
			);
		}

		// Load installer.
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-installer.php';

		// Run installation.
		$install_result = AQOP_Leads_Installer::install();



		// Install Notification System
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-notification-installer.php';
		AQOP_Notification_Installer::install();

		// Log activation.
		if (class_exists('AQOP_Event_Logger')) {
			AQOP_Event_Logger::log(
				'leads',
				'module_activated',
				'module',
				0,
				array(
					'version' => AQOP_LEADS_VERSION,
					'install_result' => $install_result,
				)
			);
		}

		/**
		 * Fires after Leads Module has been activated.
		 *
		 * @since 1.0.0
		 *
		 * @param array $install_result Installation result.
		 */
		do_action('aqop_leads_activated', $install_result);
	}
}

