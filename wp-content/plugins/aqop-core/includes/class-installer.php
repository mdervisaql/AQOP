<?php
/**
 * Plugin Installer Class
 *
 * Handles plugin installation tasks including database table creation,
 * system requirements checks, and initial data population.
 * Creates analytics-ready database schema with dimension tables.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Installer class.
 *
 * Manages installation and setup of the Operation Platform Core.
 * Implements Star Schema for analytics with fact and dimension tables.
 *
 * @since 1.0.0
 */
class AQOP_Installer
{

	/**
	 * Run the installer.
	 *
	 * Performs all installation tasks including system checks,
	 * database table creation, and data population.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array Installation status with detailed results.
	 */
	public static function install()
	{
		$status = array(
			'success' => false,
			'requirements' => false,
			'tables_created' => array(),
			'data_populated' => array(),
			'verification' => array(),
			'errors' => array(),
		);

		// Check PHP version (>= 7.4).
		if (version_compare(PHP_VERSION, '7.4', '<')) {
			$status['errors'][] = sprintf(
				/* translators: %s: Required PHP version */
				__('Operation Platform Core requires PHP version %s or higher. Current version: %s', 'aqop-core'),
				'7.4',
				PHP_VERSION
			);
			return $status;
		}

		// Check WordPress version (>= 5.8).
		global $wp_version;
		if (version_compare($wp_version, '5.8', '<')) {
			$status['errors'][] = sprintf(
				/* translators: %s: Required WordPress version */
				__('Operation Platform Core requires WordPress version %s or higher. Current version: %s', 'aqop-core'),
				'5.8',
				$wp_version
			);
			return $status;
		}

		// Check for required PHP extensions.
		$required_extensions = array('json', 'mysqli', 'curl');
		foreach ($required_extensions as $extension) {
			if (!extension_loaded($extension)) {
				$status['errors'][] = sprintf(
					/* translators: %s: Required PHP extension name */
					__('Operation Platform Core requires the %s PHP extension.', 'aqop-core'),
					$extension
				);
			}
		}

		if (!empty($status['errors'])) {
			return $status;
		}

		$status['requirements'] = true;

		// Create database tables.
		$tables_result = self::create_tables();
		$status['tables_created'] = $tables_result;

		// Populate dimension tables.
		$populate_result = self::populate_dimension_tables();
		$status['data_populated'] = $populate_result;

		// Verify installation.
		$verification = self::verify_installation();
		$status['verification'] = $verification;

		// Set database version.
		update_option('aqop_db_version', AQOP_VERSION);
		update_option('aqop_install_date', current_time('mysql'));

		// Check if all tables exist.
		$all_verified = !in_array(false, $verification, true);
		$status['success'] = $all_verified;

		/**
		 * Fires after the installation process completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $status Installation status array.
		 */
		do_action('aqop_installation_complete', $status);

		return $status;
	}

	/**
	 * Create database tables.
	 *
	 * Creates all required database tables for the Operation Platform.
	 * Uses dbDelta for safe table creation and updates.
	 * Implements Star Schema with fact and dimension tables.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return array List of created table names and their status.
	 */
	private static function create_tables()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$tables_created = array();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Table 1: Events Log (Main Fact Table)
		 *
		 * Central event logging table with temporal dimensions for analytics.
		 */
		$sql_events = "CREATE TABLE {$wpdb->prefix}aq_events_log (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			module_id tinyint UNSIGNED NOT NULL,
			event_type_id smallint UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			country_id smallint UNSIGNED DEFAULT NULL,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) UNSIGNED NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_key int UNSIGNED NOT NULL,
			time_key int UNSIGNED NOT NULL,
			hour tinyint UNSIGNED NOT NULL,
			day_of_week tinyint UNSIGNED NOT NULL,
			week_of_year tinyint UNSIGNED NOT NULL,
			month tinyint UNSIGNED NOT NULL,
			quarter tinyint UNSIGNED NOT NULL,
			year smallint UNSIGNED NOT NULL,
			duration_ms int UNSIGNED DEFAULT NULL,
			payload_json longtext,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text,
			PRIMARY KEY  (id),
			KEY idx_analysis_main (date_key, module_id, event_type_id),
			KEY idx_time_analysis (created_at, module_id),
			KEY idx_user_activity (user_id, created_at),
			KEY idx_object (object_type, object_id)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_events);
		$tables_created["{$wpdb->prefix}aq_events_log"] = self::table_exists("{$wpdb->prefix}aq_events_log");

		/**
		 * Table 2: Modules Dimension
		 *
		 * Lookup table for platform modules.
		 */
		$sql_modules = "CREATE TABLE {$wpdb->prefix}aq_dim_modules (
			id tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
			module_code varchar(20) NOT NULL,
			module_name varchar(100) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY module_code (module_code)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_modules);
		$tables_created["{$wpdb->prefix}aq_dim_modules"] = self::table_exists("{$wpdb->prefix}aq_dim_modules");

		/**
		 * Table 3: Event Types Dimension
		 *
		 * Lookup table for event types with severity levels.
		 */
		$sql_event_types = "CREATE TABLE {$wpdb->prefix}aq_dim_event_types (
			id smallint UNSIGNED NOT NULL AUTO_INCREMENT,
			module_id tinyint UNSIGNED NOT NULL,
			event_code varchar(50) NOT NULL,
			event_name varchar(100) NOT NULL,
			event_category varchar(50) DEFAULT NULL,
			severity enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_event (module_id, event_code),
			KEY idx_category (event_category)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_event_types);
		$tables_created["{$wpdb->prefix}aq_dim_event_types"] = self::table_exists("{$wpdb->prefix}aq_dim_event_types");

		/**
		 * Table 4: Countries Dimension
		 *
		 * Lookup table for countries with Arabic names.
		 */
		$sql_countries = "CREATE TABLE {$wpdb->prefix}aq_dim_countries (
			id smallint UNSIGNED NOT NULL AUTO_INCREMENT,
			country_code varchar(3) NOT NULL,
			country_name_en varchar(100) NOT NULL,
			country_name_ar varchar(100) NOT NULL,
			region varchar(50) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY country_code (country_code)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_countries);
		$tables_created["{$wpdb->prefix}aq_dim_countries"] = self::table_exists("{$wpdb->prefix}aq_dim_countries");

		/**
		 * Table 5: Date Dimension
		 *
		 * Calendar dimension for temporal analytics with Arabic names.
		 */
		$sql_date = "CREATE TABLE {$wpdb->prefix}aq_dim_date (
			date_key int UNSIGNED NOT NULL,
			full_date date NOT NULL,
			year smallint UNSIGNED NOT NULL,
			quarter tinyint UNSIGNED NOT NULL,
			month tinyint UNSIGNED NOT NULL,
			month_name varchar(20) NOT NULL,
			week_of_year tinyint UNSIGNED NOT NULL,
			day_of_month tinyint UNSIGNED NOT NULL,
			day_of_week tinyint UNSIGNED NOT NULL,
			day_name varchar(20) NOT NULL,
			is_weekend tinyint(1) NOT NULL DEFAULT 0,
			is_holiday tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (date_key),
			KEY idx_date (full_date),
			KEY idx_month (year, month)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_date);
		$tables_created["{$wpdb->prefix}aq_dim_date"] = self::table_exists("{$wpdb->prefix}aq_dim_date");

		/**
		 * Table 6: Time Dimension
		 *
		 * Time-of-day dimension for hourly analytics.
		 */
		$sql_time = "CREATE TABLE {$wpdb->prefix}aq_dim_time (
			time_key int UNSIGNED NOT NULL,
			hour tinyint UNSIGNED NOT NULL,
			minute tinyint UNSIGNED NOT NULL,
			second tinyint UNSIGNED NOT NULL,
			time_period enum('morning','afternoon','evening','night') NOT NULL,
			is_business_hours tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (time_key)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_time);
		$tables_created["{$wpdb->prefix}aq_dim_time"] = self::table_exists("{$wpdb->prefix}aq_dim_time");

		/**
		 * Table 7: Notifications Log
		 *
		 * Central notification log for all sent notifications with analytics.
		 */
		$sql_notifications = "CREATE TABLE {$wpdb->prefix}aq_notifications (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			module_code varchar(50) NOT NULL,
			event_type varchar(100) NOT NULL,
			event_id bigint(20) UNSIGNED DEFAULT NULL,
			priority enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
			channel varchar(50) NOT NULL,
			recipient_type enum('user','role','admin','custom') NOT NULL,
			recipient_id varchar(255) DEFAULT NULL,
			message_subject varchar(500) DEFAULT NULL,
			message_body text NOT NULL,
			metadata longtext DEFAULT NULL COMMENT 'JSON metadata',
			status enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
			sent_at datetime DEFAULT NULL,
			failed_reason text DEFAULT NULL,
			retry_count tinyint UNSIGNED DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_module (module_code),
			KEY idx_event (event_type),
			KEY idx_status (status),
			KEY idx_channel (channel),
			KEY idx_created (created_at),
			KEY idx_priority (priority),
			KEY idx_analysis (module_code, event_type, status, created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_notifications);
		$tables_created["{$wpdb->prefix}aq_notifications"] = self::table_exists("{$wpdb->prefix}aq_notifications");

		/**
		 * Table 8: Notification Rules
		 *
		 * Rule-based notification routing with conditions.
		 */
		$sql_notification_rules = "CREATE TABLE {$wpdb->prefix}aq_notification_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_name varchar(255) NOT NULL,
			module_code varchar(50) NOT NULL,
			event_type varchar(100) NOT NULL,
			conditions longtext DEFAULT NULL COMMENT 'JSON conditions',
			channels longtext NOT NULL COMMENT 'JSON array of channels',
			recipient_config longtext NOT NULL COMMENT 'JSON recipient configuration',
			message_template_subject varchar(500) DEFAULT NULL,
			message_template_body text NOT NULL,
			priority enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
			is_active tinyint(1) NOT NULL DEFAULT 1,
			execution_order int UNSIGNED NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_module (module_code),
			KEY idx_event (event_type),
			KEY idx_active (is_active),
			KEY idx_order (execution_order)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_notification_rules);
		$tables_created["{$wpdb->prefix}aq_notification_rules"] = self::table_exists("{$wpdb->prefix}aq_notification_rules");

		/**
		 * Table 9: Notification Templates
		 *
		 * Reusable message templates with variable support.
		 */
		$sql_notification_templates = "CREATE TABLE {$wpdb->prefix}aq_notification_templates (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			template_key varchar(100) NOT NULL,
			template_name varchar(255) NOT NULL,
			module_code varchar(50) NOT NULL,
			event_type varchar(100) NOT NULL,
			subject_template varchar(500) DEFAULT NULL,
			body_template text NOT NULL,
			available_variables longtext DEFAULT NULL COMMENT 'JSON array of variables',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY template_key (template_key),
			KEY idx_module (module_code),
			KEY idx_event (event_type)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_notification_templates);
		$tables_created["{$wpdb->prefix}aq_notification_templates"] = self::table_exists("{$wpdb->prefix}aq_notification_templates");

		// User Sessions Table - Track active user sessions
		$sql_user_sessions = "CREATE TABLE {$wpdb->prefix}aq_user_sessions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			session_token varchar(64) NOT NULL,
			current_module varchar(50) DEFAULT NULL,
			current_page varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			last_activity datetime NOT NULL,
			login_at datetime NOT NULL,
			logout_at datetime DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY session_token (session_token),
			KEY idx_user (user_id),
			KEY idx_active (is_active, last_activity),
			KEY idx_login (login_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_user_sessions);
		$tables_created["{$wpdb->prefix}aq_user_sessions"] = self::table_exists("{$wpdb->prefix}aq_user_sessions");

		// User Activity Table - Detailed activity log
		$sql_user_activity = "CREATE TABLE {$wpdb->prefix}aq_user_activity (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			module_code varchar(50) NOT NULL,
			action_type varchar(50) NOT NULL,
			action_details text DEFAULT NULL,
			page_url varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_session (session_id),
			KEY idx_user (user_id),
			KEY idx_module (module_code),
			KEY idx_time (created_at),
			KEY idx_analysis (user_id, module_code, created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_user_activity);
		$tables_created["{$wpdb->prefix}aq_user_activity"] = self::table_exists("{$wpdb->prefix}aq_user_activity");

		/**
		 * Fires after database tables have been created.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tables_created Array of table names and their creation status.
		 */
		do_action('aqop_tables_created', $tables_created);

		return $tables_created;
	}

	/**
	 * Populate dimension tables.
	 *
	 * Populates lookup tables with initial data.
	 * This includes modules, countries, date dimension, and time dimension.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return array Population status for each dimension table.
	 */
	private static function populate_dimension_tables()
	{
		global $wpdb;

		$status = array(
			'modules' => 0,
			'countries' => 0,
			'dates' => 0,
			'times' => 0,
		);

		// Populate modules.
		$modules = array(
			array('core', 'Core Platform', 1),
			array('leads', 'Leads Module', 1),
			array('training', 'Training Module', 1),
			array('kb', 'Knowledge Base', 1),
		);

		foreach ($modules as $module) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_dim_modules (module_code, module_name, is_active) VALUES (%s, %s, %d)",
					$module[0],
					$module[1],
					$module[2]
				)
			);
			if ($wpdb->insert_id > 0) {
				$status['modules']++;
			}
		}

		// Populate countries.
		$countries = array(
			array('SA', 'Saudi Arabia', 'السعودية', 'GCC', 1),
			array('AE', 'UAE', 'الإمارات', 'GCC', 1),
			array('EG', 'Egypt', 'مصر', 'North Africa', 1),
			array('QA', 'Qatar', 'قطر', 'GCC', 1),
			array('KW', 'Kuwait', 'الكويت', 'GCC', 1),
			array('BH', 'Bahrain', 'البحرين', 'GCC', 1),
			array('OM', 'Oman', 'عمان', 'GCC', 1),
			array('JO', 'Jordan', 'الأردن', 'Levant', 1),
			array('TR', 'Turkey', 'تركيا', 'MENA', 1),
		);

		foreach ($countries as $country) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_dim_countries (country_code, country_name_en, country_name_ar, region, is_active) VALUES (%s, %s, %s, %s, %d)",
					$country[0],
					$country[1],
					$country[2],
					$country[3],
					$country[4]
				)
			);
			if ($wpdb->insert_id > 0) {
				$status['countries']++;
			}
		}

		// Generate date dimension.
		$status['dates'] = self::generate_date_dimension('2024-01-01', '2025-12-31');

		// Generate time dimension.
		$status['times'] = self::generate_time_dimension();

		/**
		 * Fires after dimension tables have been populated.
		 *
		 * @since 1.0.0
		 *
		 * @param array $status Population status array.
		 */
		do_action('aqop_dimension_tables_populated', $status);

		return $status;
	}

	/**
	 * Generate date dimension.
	 *
	 * Generates a complete date dimension table with all temporal attributes.
	 * Includes Arabic month and day names, weekend flags, and calendar calculations.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $start_date Start date in Y-m-d format.
	 * @param  string $end_date   End date in Y-m-d format.
	 * @return int Number of dates inserted.
	 */
	private static function generate_date_dimension($start_date = '2024-01-01', $end_date = '2025-12-31')
	{
		global $wpdb;

		// Arabic month names.
		$arabic_months = array(
			1 => 'يناير',
			2 => 'فبراير',
			3 => 'مارس',
			4 => 'أبريل',
			5 => 'مايو',
			6 => 'يونيو',
			7 => 'يوليو',
			8 => 'أغسطس',
			9 => 'سبتمبر',
			10 => 'أكتوبر',
			11 => 'نوفمبر',
			12 => 'ديسمبر',
		);

		// Arabic day names (Sunday=1, Saturday=7).
		$arabic_days = array(
			1 => 'الأحد',
			2 => 'الإثنين',
			3 => 'الثلاثاء',
			4 => 'الأربعاء',
			5 => 'الخميس',
			6 => 'الجمعة',
			7 => 'السبت',
		);

		$start = new DateTime($start_date);
		$end = new DateTime($end_date);
		$interval = new DateInterval('P1D');
		$date_range = new DatePeriod($start, $interval, $end->modify('+1 day'));

		$batch = array();
		$count = 0;
		$batch_size = 100;

		foreach ($date_range as $date) {
			$date_key = (int) $date->format('Ymd');
			$full_date = $date->format('Y-m-d');
			$year = (int) $date->format('Y');
			$quarter = (int) ceil((int) $date->format('n') / 3);
			$month = (int) $date->format('n');
			$month_name = $arabic_months[$month];
			$week_of_year = (int) $date->format('W');
			$day_of_month = (int) $date->format('j');
			$day_of_week = (int) $date->format('N'); // 1=Monday, 7=Sunday in ISO-8601.

			// Adjust to 1=Sunday, 7=Saturday.
			$day_of_week_adjusted = ($day_of_week === 7) ? 1 : $day_of_week + 1;
			$day_name = $arabic_days[$day_of_week_adjusted];

			// Weekend: Friday (6) and Saturday (7).
			$is_weekend = ($day_of_week_adjusted === 6 || $day_of_week_adjusted === 7) ? 1 : 0;

			$batch[] = $wpdb->prepare(
				'(%d, %s, %d, %d, %d, %s, %d, %d, %d, %s, %d, 0)',
				$date_key,
				$full_date,
				$year,
				$quarter,
				$month,
				$month_name,
				$week_of_year,
				$day_of_month,
				$day_of_week_adjusted,
				$day_name,
				$is_weekend
			);

			$count++;

			// Insert in batches of 100.
			if (count($batch) >= $batch_size) {
				$values = implode(', ', $batch);
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_dim_date 
					(date_key, full_date, year, quarter, month, month_name, week_of_year, day_of_month, day_of_week, day_name, is_weekend, is_holiday)
					VALUES {$values}"
				);
				$batch = array();
			}
		}

		// Insert remaining records.
		if (!empty($batch)) {
			$values = implode(', ', $batch);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query(
				"INSERT IGNORE INTO {$wpdb->prefix}aq_dim_date 
				(date_key, full_date, year, quarter, month, month_name, week_of_year, day_of_month, day_of_week, day_name, is_weekend, is_holiday)
				VALUES {$values}"
			);
		}

		return $count;
	}

	/**
	 * Generate time dimension.
	 *
	 * Generates hourly time samples for a 24-hour period.
	 * Includes time period classification and business hours flag.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return int Number of time records inserted.
	 */
	private static function generate_time_dimension()
	{
		global $wpdb;

		$count = 0;

		for ($hour = 0; $hour < 24; $hour++) {
			$time_key = $hour * 10000; // Format: HHMMSS (e.g., 140000 for 14:00:00).
			$minute = 0;
			$second = 0;

			// Determine time period.
			if ($hour >= 6 && $hour < 12) {
				$time_period = 'morning';
			} elseif ($hour >= 12 && $hour < 18) {
				$time_period = 'afternoon';
			} elseif ($hour >= 18 && $hour < 22) {
				$time_period = 'evening';
			} else {
				$time_period = 'night';
			}

			// Business hours: 9 AM to 6 PM (9-17 inclusive).
			$is_business_hours = ($hour >= 9 && $hour < 18) ? 1 : 0;

			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_dim_time (time_key, hour, minute, second, time_period, is_business_hours) VALUES (%d, %d, %d, %d, %s, %d)",
					$time_key,
					$hour,
					$minute,
					$second,
					$time_period,
					$is_business_hours
				)
			);

			if ($wpdb->insert_id > 0) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Verify installation.
	 *
	 * Checks that all required database tables exist.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return array Table existence status array.
	 */
	private static function verify_installation()
	{
		global $wpdb;

		$required_tables = array(
			'aq_events_log',
			'aq_dim_modules',
			'aq_dim_event_types',
			'aq_dim_countries',
			'aq_dim_date',
			'aq_dim_time',
			'aq_notifications',
			'aq_notification_rules',
			'aq_notification_templates',
			'aq_user_sessions',
			'aq_user_activity',
		);

		$verification = array();

		foreach ($required_tables as $table) {
			$full_table_name = $wpdb->prefix . $table;
			$verification[$table] = self::table_exists($full_table_name);
		}

		return $verification;
	}

	/**
	 * Check if a table exists.
	 *
	 * Verifies that a specific database table exists.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $table_name Full table name including prefix.
	 * @return bool True if table exists, false otherwise.
	 */
	private static function table_exists($table_name)
	{
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

	/**
	 * Check if tables exist (legacy method).
	 *
	 * Verifies that all required database tables exist.
	 * Kept for backward compatibility.
	 *
	 * @since  1.0.0
	 * @static
	 * @return bool True if all tables exist, false otherwise.
	 */
	public static function tables_exist()
	{
		$verification = self::verify_installation();
		return !in_array(false, $verification, true);
	}
}
