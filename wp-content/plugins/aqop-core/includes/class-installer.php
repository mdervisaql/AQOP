<?php
/**
 * Plugin Installer Class
 *
 * Handles plugin installation tasks including database table creation,
 * system requirements checks, and initial data population.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Installer class.
 *
 * Manages installation and setup of the Operation Platform Core.
 *
 * @since 1.0.0
 */
class AQOP_Installer {

	/**
	 * Run the installer.
	 *
	 * Performs all installation tasks including system checks,
	 * database table creation, and data population.
	 *
	 * @since  1.0.0
	 * @static
	 * @return bool True on success, false on failure.
	 */
	public static function install() {
		// Check system requirements.
		if ( ! self::check_requirements() ) {
			return false;
		}

		// Create database tables.
		self::create_tables();

		// Populate dimension tables.
		self::populate_dimension_tables();

		// Set plugin version in options.
		update_option( 'aqop_version', AQOP_VERSION );
		update_option( 'aqop_install_date', current_time( 'mysql' ) );

		// Flush rewrite rules.
		flush_rewrite_rules();

		return true;
	}

	/**
	 * Check system requirements.
	 *
	 * Verifies that the server meets minimum requirements for the plugin.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return bool True if requirements are met, false otherwise.
	 */
	private static function check_requirements() {
		global $wp_version;

		$errors = array();

		// Check PHP version (>= 7.4).
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: Required PHP version */
				__( 'Operation Platform Core requires PHP version %s or higher.', 'aqop-core' ),
				'7.4'
			);
		}

		// Check WordPress version (>= 5.8).
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: Required WordPress version */
				__( 'Operation Platform Core requires WordPress version %s or higher.', 'aqop-core' ),
				'5.8'
			);
		}

		// Check for required PHP extensions.
		$required_extensions = array( 'json', 'mysqli', 'curl' );
		foreach ( $required_extensions as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$errors[] = sprintf(
					/* translators: %s: Required PHP extension name */
					__( 'Operation Platform Core requires the %s PHP extension.', 'aqop-core' ),
					$extension
				);
			}
		}

		// Display errors if any.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
			}
			return false;
		}

		return true;
	}

	/**
	 * Create database tables.
	 *
	 * Creates all required database tables for the Operation Platform.
	 * Uses dbDelta for safe table creation and updates.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Tables will be created in future iterations.
		// This is a placeholder for the database schema.
		$tables_sql = array();

		/**
		 * Events Log Table (Core Fact Table).
		 *
		 * This table will store all events from all modules.
		 * Will be implemented in Phase 2 of development.
		 */
		$tables_sql[] = "CREATE TABLE {$wpdb->prefix}aq_events_log (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			module VARCHAR(50) NOT NULL,
			event_type VARCHAR(100) NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			object_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			country VARCHAR(100),
			payload_json LONGTEXT,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY module (module),
			KEY event_type (event_type),
			KEY object_type (object_type),
			KEY object_id (object_id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		/**
		 * Notification Rules Table.
		 *
		 * Stores dynamic notification rules created by admins.
		 * Will be implemented in Phase 6 of development.
		 */
		$tables_sql[] = "CREATE TABLE {$wpdb->prefix}aq_notification_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_name VARCHAR(255) NOT NULL,
			module VARCHAR(50) NOT NULL,
			event_type VARCHAR(100) NOT NULL,
			conditions_json LONGTEXT,
			actions_json LONGTEXT,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY module (module),
			KEY event_type (event_type),
			KEY enabled (enabled)
		) $charset_collate;";

		// Execute table creation.
		foreach ( $tables_sql as $sql ) {
			dbDelta( $sql );
		}

		/**
		 * Fires after database tables have been created.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_tables_created' );
	}

	/**
	 * Populate dimension tables.
	 *
	 * Populates lookup tables with initial data.
	 * This includes date dimensions, countries, modules, etc.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function populate_dimension_tables() {
		/**
		 * Dimension tables will be populated in future phases.
		 * This includes:
		 * - Date dimension (2024-01-01 to 2025-12-31)
		 * - Countries lookup
		 * - Modules lookup
		 * - Event types lookup
		 */

		/**
		 * Fires after dimension tables have been populated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_dimension_tables_populated' );
	}

	/**
	 * Check if tables exist.
	 *
	 * Verifies that all required database tables exist.
	 *
	 * @since  1.0.0
	 * @static
	 * @return bool True if all tables exist, false otherwise.
	 */
	public static function tables_exist() {
		global $wpdb;

		$required_tables = array(
			$wpdb->prefix . 'aq_events_log',
			$wpdb->prefix . 'aq_notification_rules',
		);

		foreach ( $required_tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				return false;
			}
		}

		return true;
	}
}

