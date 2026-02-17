<?php
/**
 * JWT Installer Class
 *
 * Handles plugin activation, deactivation, and database setup.
 *
 * === JWT AUTHENTICATION SYSTEM (Hour 1) ===
 * Generated: 2025-11-17
 * Security Level: Enterprise Grade
 * Algorithm: HS256 (HMAC-SHA256)
 * Token Expiry: Access 15min, Refresh 7days
 * === END JWT AUTHENTICATION ===
 *
 * @package AQOP_JWT_Auth
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_JWT_Installer class.
 *
 * Manages installation and database setup.
 *
 * @since 1.0.0
 */
class AQOP_JWT_Installer {

	/**
	 * Activate plugin.
	 *
	 * Creates database tables and schedules cleanup cron job.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cleanup();
		self::set_version();
	}

	/**
	 * Deactivate plugin.
	 *
	 * Clears scheduled cron jobs.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		self::clear_scheduled_hooks();
	}

	/**
	 * Create database tables.
	 *
	 * Creates the JWT blacklist table.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * JWT Blacklist Table
		 *
		 * Stores blacklisted tokens (hashed) for revocation.
		 */
		$sql_blacklist = "CREATE TABLE {$wpdb->prefix}aqop_jwt_blacklist (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			token_hash varchar(64) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY token_hash (token_hash),
			KEY user_id (user_id),
			KEY expires_at (expires_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_blacklist );
	}

	/**
	 * Schedule cleanup cron job.
	 *
	 * Schedules daily cleanup of expired blacklist entries.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_cleanup() {
		if ( ! wp_next_scheduled( 'aqop_jwt_cleanup_blacklist' ) ) {
			wp_schedule_event( time(), 'daily', 'aqop_jwt_cleanup_blacklist' );
		}

		// Hook the cleanup function.
		add_action( 'aqop_jwt_cleanup_blacklist', array( 'AQOP_JWT_Handler', 'cleanup_blacklist' ) );
	}

	/**
	 * Clear scheduled hooks.
	 *
	 * Removes scheduled cron jobs on deactivation.
	 *
	 * @since 1.0.0
	 */
	private static function clear_scheduled_hooks() {
		$timestamp = wp_next_scheduled( 'aqop_jwt_cleanup_blacklist' );
		
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aqop_jwt_cleanup_blacklist' );
		}
	}

	/**
	 * Set plugin version.
	 *
	 * Stores the plugin version in the database.
	 *
	 * @since 1.0.0
	 */
	private static function set_version() {
		update_option( 'aqop_jwt_version', AQOP_JWT_VERSION, false );
		update_option( 'aqop_jwt_install_date', current_time( 'mysql' ), false );
	}
}

