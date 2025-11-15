<?php
/**
 * Leads Module Deactivator Class
 *
 * Fired during plugin deactivation.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Deactivator class.
 *
 * Handles Leads Module deactivation.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Performs cleanup tasks.
	 * NOTE: Does not delete data or tables.
	 *
	 * @since  1.0.0
	 * @static
	 */
	public static function deactivate() {
		// Clear temporary caches.
		self::clear_transients();

		// Log deactivation.
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'module_deactivated',
				'module',
				0,
				array(
					'version' => AQOP_LEADS_VERSION,
				)
			);
		}

		/**
		 * Fires after Leads Module has been deactivated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_leads_deactivated' );
	}

	/**
	 * Clear temporary transients.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all leads module transients.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_aqop_leads_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_aqop_leads_' ) . '%'
			)
		);
	}
}

