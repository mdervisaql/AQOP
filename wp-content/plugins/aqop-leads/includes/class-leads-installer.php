<?php
/**
 * Leads Module Installer Class
 *
 * Handles database table creation and initial data population.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Installer class.
 *
 * Manages installation of the Leads Module.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Installer {

	/**
	 * Run installer.
	 *
	 * Creates tables and populates initial data.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array Installation status.
	 */
	public static function install() {
		global $wpdb;

		$status = array(
			'success'        => false,
			'tables_created' => array(),
			'data_populated' => array(),
			'errors'         => array(),
		);

		// Create tables.
		$tables_result = self::create_tables();
		$status['tables_created'] = $tables_result;

		// Populate initial data.
		$populate_result = self::populate_initial_data();
		$status['data_populated'] = $populate_result;

		// Register module in core.
		self::register_module();

		// Set version.
		update_option( 'aqop_leads_version', AQOP_LEADS_VERSION );
		update_option( 'aqop_leads_install_date', current_time( 'mysql' ) );

		$status['success'] = true;

		return $status;
	}

	/**
	 * Create database tables.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return array Created tables status.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$tables_created = array();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Main Leads Table
		 */
		$sql_leads = "CREATE TABLE {$wpdb->prefix}aq_leads (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) DEFAULT NULL,
			phone varchar(50) DEFAULT NULL,
			whatsapp varchar(50) DEFAULT NULL,
			country_id smallint UNSIGNED DEFAULT NULL,
			source_id smallint UNSIGNED DEFAULT NULL,
			campaign_id int UNSIGNED DEFAULT NULL,
			status_id tinyint UNSIGNED NOT NULL DEFAULT 1,
			assigned_to bigint(20) UNSIGNED DEFAULT NULL,
			priority enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			last_contact_at datetime DEFAULT NULL,
			airtable_record_id varchar(50) DEFAULT NULL,
			notes text,
			custom_fields longtext,
			PRIMARY KEY  (id),
			KEY idx_status (status_id),
			KEY idx_assigned (assigned_to),
			KEY idx_country (country_id),
			KEY idx_source (source_id),
			KEY idx_campaign (campaign_id),
			KEY idx_created (created_at),
			KEY idx_airtable (airtable_record_id)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_leads );
		$tables_created["{$wpdb->prefix}aq_leads"] = self::table_exists( "{$wpdb->prefix}aq_leads" );

		/**
		 * Lead Status Table
		 */
		$sql_status = "CREATE TABLE {$wpdb->prefix}aq_leads_status (
			id tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
			status_code varchar(20) NOT NULL,
			status_name_ar varchar(50) NOT NULL,
			status_name_en varchar(50) NOT NULL,
			status_order tinyint UNSIGNED NOT NULL,
			color varchar(7) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY status_code (status_code)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_status );
		$tables_created["{$wpdb->prefix}aq_leads_status"] = self::table_exists( "{$wpdb->prefix}aq_leads_status" );

		/**
		 * Lead Sources Table
		 */
		$sql_sources = "CREATE TABLE {$wpdb->prefix}aq_leads_sources (
			id smallint UNSIGNED NOT NULL AUTO_INCREMENT,
			source_code varchar(20) NOT NULL,
			source_name varchar(50) NOT NULL,
			source_type enum('paid','organic','referral','direct') NOT NULL DEFAULT 'direct',
			cost_per_lead decimal(10,2) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY source_code (source_code)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_sources );
		$tables_created["{$wpdb->prefix}aq_leads_sources"] = self::table_exists( "{$wpdb->prefix}aq_leads_sources" );

		/**
		 * Campaigns Table
		 */
		$sql_campaigns = "CREATE TABLE {$wpdb->prefix}aq_leads_campaigns (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text,
			start_date date DEFAULT NULL,
			end_date date DEFAULT NULL,
			budget decimal(10,2) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_active (is_active),
			KEY idx_dates (start_date, end_date)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_campaigns );
		$tables_created["{$wpdb->prefix}aq_leads_campaigns"] = self::table_exists( "{$wpdb->prefix}aq_leads_campaigns" );

		/**
		 * Lead Notes Table
		 */
		$sql_notes = "CREATE TABLE {$wpdb->prefix}aq_leads_notes (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			note_text text NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_lead (lead_id),
			KEY idx_user (user_id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql_notes );
		$tables_created["{$wpdb->prefix}aq_leads_notes"] = self::table_exists( "{$wpdb->prefix}aq_leads_notes" );

		return $tables_created;
	}

	/**
	 * Populate initial data.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return array Population status.
	 */
	private static function populate_initial_data() {
		global $wpdb;

		$status = array(
			'statuses' => 0,
			'sources'  => 0,
		);

		// Populate lead statuses.
		$statuses = array(
			array( 'pending', 'معلق', 'Pending', 1, '#718096', 1 ),
			array( 'contacted', 'تم الاتصال', 'Contacted', 2, '#4299e1', 1 ),
			array( 'qualified', 'مؤهل', 'Qualified', 3, '#ed8936', 1 ),
			array( 'converted', 'محول', 'Converted', 4, '#48bb78', 1 ),
			array( 'lost', 'خاسر', 'Lost', 5, '#f56565', 1 ),
		);

		foreach ( $statuses as $status_data ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_leads_status 
					(status_code, status_name_ar, status_name_en, status_order, color, is_active) 
					VALUES (%s, %s, %s, %d, %s, %d)",
					$status_data[0],
					$status_data[1],
					$status_data[2],
					$status_data[3],
					$status_data[4],
					$status_data[5]
				)
			);
			if ( $wpdb->insert_id > 0 ) {
				$status['statuses']++;
			}
		}

		// Populate lead sources.
		$sources = array(
			array( 'facebook', 'Facebook Ads', 'paid', 5.00, 1 ),
			array( 'google', 'Google Ads', 'paid', 7.50, 1 ),
			array( 'instagram', 'Instagram Ads', 'paid', 4.00, 1 ),
			array( 'website', 'Website Form', 'organic', 0.00, 1 ),
			array( 'referral', 'Referral', 'referral', 0.00, 1 ),
			array( 'direct', 'Direct Contact', 'direct', 0.00, 1 ),
		);

		foreach ( $sources as $source_data ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_leads_sources 
					(source_code, source_name, source_type, cost_per_lead, is_active) 
					VALUES (%s, %s, %s, %f, %d)",
					$source_data[0],
					$source_data[1],
					$source_data[2],
					$source_data[3],
					$source_data[4]
				)
			);
			if ( $wpdb->insert_id > 0 ) {
				$status['sources']++;
			}
		}

		return $status;
	}

	/**
	 * Register module in core.
	 *
	 * Ensures leads module is registered in core's dim_modules table.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 */
	private static function register_module() {
		global $wpdb;

		// Check if already registered.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_modules WHERE module_code = %s",
				'leads'
			)
		);

		if ( ! $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'aq_dim_modules',
				array(
					'module_code' => 'leads',
					'module_name' => 'Leads Module',
					'is_active'   => 1,
				),
				array( '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Check if table exists.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $table_name Full table name.
	 * @return bool True if exists.
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $result === $table_name;
	}
}

