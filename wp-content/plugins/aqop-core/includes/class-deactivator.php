<?php
/**
 * Plugin Deactivator Class
 *
 * Fired during plugin deactivation.
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Deactivator class.
 *
 * Handles plugin deactivation tasks.
 *
 * @since 1.0.0
 */
class AQOP_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Performs cleanup tasks when the plugin is deactivated.
	 * NOTE: We only clean temporary data, not permanent data or tables.
	 *
	 * @since  1.0.0
	 * @static
	 */
	public static function deactivate() {
		// Clear temporary transients.
		self::clear_transients();

		// Remove custom roles (optional - uncomment if you want to remove roles on deactivation).
		// require_once AQOP_PLUGIN_DIR . 'includes/authentication/class-roles-manager.php';
		// AQOP_Roles_Manager::remove_roles();

		// Flush rewrite rules.
		flush_rewrite_rules();

		/**
		 * Fires after the plugin has been deactivated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_deactivated' );

		// Log deactivation event.
		self::log_deactivation();
	}

	/**
	 * Clear temporary transients.
	 *
	 * Removes all temporary cached data created by the plugin.
	 * Permanent data and database tables are preserved.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all transients with aqop prefix.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_aqop_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_aqop_' ) . '%'
			)
		);

		// Clear object cache.
		wp_cache_flush();
	}

	/**
	 * Log plugin deactivation.
	 *
	 * Records the plugin deactivation in WordPress options for tracking.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function log_deactivation() {
		$deactivation_log = get_option( 'aqop_deactivation_log', array() );

		$deactivation_log[] = array(
			'timestamp' => current_time( 'mysql' ),
			'user_id'   => get_current_user_id(),
			'version'   => AQOP_VERSION,
		);

		update_option( 'aqop_deactivation_log', $deactivation_log );
	}
}

