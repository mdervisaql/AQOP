<?php
/**
 * Leads Module Installer Class
 *
 * Handles database table creation and initial data population.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Installer class.
 *
 * Manages installation of the Leads Module.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Installer
{

	/**
	 * Run installer.
	 *
	 * Creates tables and populates initial data.
	 *
	 * @since  1.0.0
	 * @static
	 * @return array Installation status.
	 */
	public static function install()
	{
		global $wpdb;

		$status = array(
			'success' => false,
			'tables_created' => array(),
			'data_populated' => array(),
			'errors' => array(),
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
		update_option('aqop_leads_version', AQOP_LEADS_VERSION);
		update_option('aqop_leads_install_date', current_time('mysql'));

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
	private static function create_tables()
	{
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
			lead_score int DEFAULT 0,
			lead_rating enum('hot', 'warm', 'qualified', 'cold', 'not_interested') DEFAULT 'cold',
			score_updated_at datetime DEFAULT NULL,
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
			KEY idx_airtable (airtable_record_id),
			KEY idx_score (lead_score),
			KEY idx_rating (lead_rating)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_leads);
		$tables_created["{$wpdb->prefix}aq_leads"] = self::table_exists("{$wpdb->prefix}aq_leads");

		/**
		 * Lead Score History Table
		 */
		$sql_score_history = "CREATE TABLE {$wpdb->prefix}aq_lead_score_history (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED NOT NULL,
			previous_score int DEFAULT 0,
			new_score int DEFAULT 0,
			previous_rating varchar(20) DEFAULT NULL,
			new_rating varchar(20) DEFAULT NULL,
			change_reason varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_lead (lead_id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_score_history);
		$tables_created["{$wpdb->prefix}aq_lead_score_history"] = self::table_exists("{$wpdb->prefix}aq_lead_score_history");

		/**
		 * Scoring Rules Table
		 */
		$sql_scoring_rules = "CREATE TABLE {$wpdb->prefix}aq_scoring_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_name varchar(100) NOT NULL,
			rule_type enum('add', 'subtract', 'set') NOT NULL,
			condition_field varchar(50) NOT NULL,
			condition_operator enum('equals', 'not_equals', 'contains', 'greater_than', 'less_than', 'in_list') NOT NULL,
			condition_value text,
			score_points int NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			priority int NOT NULL DEFAULT 10,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_active (is_active),
			KEY idx_priority (priority)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_scoring_rules);
		$tables_created["{$wpdb->prefix}aq_scoring_rules"] = self::table_exists("{$wpdb->prefix}aq_scoring_rules");

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

		dbDelta($sql_status);
		$tables_created["{$wpdb->prefix}aq_leads_status"] = self::table_exists("{$wpdb->prefix}aq_leads_status");

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

		dbDelta($sql_sources);
		$tables_created["{$wpdb->prefix}aq_leads_sources"] = self::table_exists("{$wpdb->prefix}aq_leads_sources");

		/**
		 * Campaigns Table
		 */
		$sql_campaigns = "CREATE TABLE {$wpdb->prefix}aq_leads_campaigns (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text,
			group_id int UNSIGNED DEFAULT NULL,
			country_id smallint UNSIGNED DEFAULT NULL,
			platform varchar(50) DEFAULT NULL,
			start_date date DEFAULT NULL,
			end_date date DEFAULT NULL,
			budget decimal(10,2) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_active (is_active),
			KEY idx_dates (start_date, end_date),
			KEY idx_group (group_id),
			KEY idx_country (country_id),
			KEY idx_platform (platform),
			CONSTRAINT fk_campaign_group FOREIGN KEY (group_id) REFERENCES {$wpdb->prefix}aq_campaign_groups(id) ON DELETE SET NULL
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_campaigns);
		$tables_created["{$wpdb->prefix}aq_leads_campaigns"] = self::table_exists("{$wpdb->prefix}aq_leads_campaigns");

		/**
		 * Campaign Groups Table
		 */
		$sql_campaign_groups = "CREATE TABLE {$wpdb->prefix}aq_campaign_groups (
			id int UNSIGNED NOT NULL AUTO_INCREMENT,
			group_name_en varchar(255) NOT NULL,
			group_name_ar varchar(255) NOT NULL,
			description text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_campaign_groups);
		$tables_created["{$wpdb->prefix}aq_campaign_groups"] = self::table_exists("{$wpdb->prefix}aq_campaign_groups");

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

		dbDelta($sql_notes);
		$tables_created["{$wpdb->prefix}aq_leads_notes"] = self::table_exists("{$wpdb->prefix}aq_leads_notes");

		/**
		 * Questions Library Table
		 *
		 * Centralized repository of reusable questions.
		 */
		$sql_questions_library = "CREATE TABLE {$wpdb->prefix}aq_questions_library (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			question_key varchar(50) NOT NULL,
			question_text_ar text NOT NULL,
			question_text_en text NOT NULL,
			field_type enum('text','textarea','select','radio','checkbox','number','date','phone','email') NOT NULL DEFAULT 'text',
			field_options longtext DEFAULT NULL COMMENT 'JSON array of options for select/radio/checkbox',
			validation_rules longtext DEFAULT NULL COMMENT 'JSON validation rules: required, min, max, pattern',
			question_group varchar(100) DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY question_key (question_key),
			KEY idx_group (question_group),
			KEY idx_active (is_active),
			KEY idx_type (field_type)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_questions_library);
		$tables_created["{$wpdb->prefix}aq_questions_library"] = self::table_exists("{$wpdb->prefix}aq_questions_library");

		/**
		 * Campaign Questions Assignment Table
		 *
		 * Maps questions from library to specific campaigns.
		 */
		$sql_campaign_questions = "CREATE TABLE {$wpdb->prefix}aq_campaign_questions (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id int UNSIGNED NOT NULL,
			question_id bigint(20) UNSIGNED NOT NULL,
			question_order int UNSIGNED NOT NULL DEFAULT 0,
			is_required tinyint(1) NOT NULL DEFAULT 0,
			custom_label_ar text DEFAULT NULL COMMENT 'Override default question text',
			custom_label_en text DEFAULT NULL COMMENT 'Override default question text',
			conditional_logic longtext DEFAULT NULL COMMENT 'JSON conditional display rules',
			PRIMARY KEY  (id),
			KEY idx_campaign (campaign_id),
			KEY idx_question (question_id),
			KEY idx_order (question_order),
			UNIQUE KEY unique_campaign_question (campaign_id, question_id)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_campaign_questions);
		$tables_created["{$wpdb->prefix}aq_campaign_questions"] = self::table_exists("{$wpdb->prefix}aq_campaign_questions");

		/**
		 * Question Groups Table
		 *
		 * Organize questions into logical groups.
		 */
		$sql_question_groups = "CREATE TABLE {$wpdb->prefix}aq_question_groups (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_key varchar(50) NOT NULL,
			group_name_ar varchar(255) NOT NULL,
			group_name_en varchar(255) NOT NULL,
			display_order int UNSIGNED NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY group_key (group_key),
			KEY idx_order (display_order)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_question_groups);
		$tables_created["{$wpdb->prefix}aq_question_groups"] = self::table_exists("{$wpdb->prefix}aq_question_groups");

		/**
		 * Communications Log Table
		 * 
		 * Tracks all interactions with leads (calls, emails, etc.)
		 */
		$sql_communications = "CREATE TABLE {$wpdb->prefix}aq_communications (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			type enum('whatsapp', 'sms', 'email', 'call', 'meeting', 'note') NOT NULL,
			direction enum('inbound', 'outbound') NOT NULL DEFAULT 'outbound',
			subject varchar(255) DEFAULT NULL,
			content text,
			duration_seconds int UNSIGNED DEFAULT NULL,
			recording_url varchar(500) DEFAULT NULL,
			outcome enum('answered', 'no_answer', 'busy', 'voicemail', 'interested', 'not_interested', 'callback', 'completed') DEFAULT NULL,
			follow_up_date datetime DEFAULT NULL,
			follow_up_note varchar(500) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_lead (lead_id),
			KEY idx_user (user_id),
			KEY idx_type (type),
			KEY idx_follow_up (follow_up_date),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_communications);
		$tables_created["{$wpdb->prefix}aq_communications"] = self::table_exists("{$wpdb->prefix}aq_communications");

		/**
		 * Follow-ups Table
		 * 
		 * Manages scheduled follow-up tasks
		 */
		$sql_follow_ups = "CREATE TABLE {$wpdb->prefix}aq_follow_ups (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			communication_id bigint(20) UNSIGNED DEFAULT NULL,
			title varchar(255) NOT NULL,
			description text,
			due_date datetime NOT NULL,
			priority enum('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
			status enum('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_user_due (user_id, due_date),
			KEY idx_status (status),
			KEY idx_lead (lead_id)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_follow_ups);
		$tables_created["{$wpdb->prefix}aq_follow_ups"] = self::table_exists("{$wpdb->prefix}aq_follow_ups");

		/**
		 * WhatsApp Messages Table
		 * 
		 * Stores WhatsApp message history
		 */
		$sql_whatsapp = "CREATE TABLE {$wpdb->prefix}aq_whatsapp_messages (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) UNSIGNED DEFAULT NULL,
			wa_message_id varchar(100) DEFAULT NULL,
			phone_number varchar(20) NOT NULL,
			direction enum('inbound', 'outbound') NOT NULL,
			message_type enum('text', 'image', 'document', 'template', 'audio', 'video') DEFAULT 'text',
			content text,
			media_url varchar(500) DEFAULT NULL,
			template_name varchar(100) DEFAULT NULL,
			status enum('sent', 'delivered', 'read', 'failed') DEFAULT 'sent',
			error_message text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_lead (lead_id),
			KEY idx_phone (phone_number),
			KEY idx_wa_id (wa_message_id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_whatsapp);
		$tables_created["{$wpdb->prefix}aq_whatsapp_messages"] = self::table_exists("{$wpdb->prefix}aq_whatsapp_messages");

		/**
		 * Facebook Connections Table
		 */
		$sql_fb_connections = "CREATE TABLE {$wpdb->prefix}aq_facebook_connections (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			fb_user_id varchar(50) NOT NULL,
			fb_user_name varchar(100) NOT NULL,
			access_token text NOT NULL,
			token_expires_at datetime DEFAULT NULL,
			ad_account_id varchar(50) DEFAULT NULL,
			ad_account_name varchar(100) DEFAULT NULL,
			page_id varchar(50) DEFAULT NULL,
			page_name varchar(100) DEFAULT NULL,
			status enum('active', 'expired', 'disconnected') NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_user (user_id),
			KEY idx_fb_user (fb_user_id),
			KEY idx_status (status)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_fb_connections);
		$tables_created["{$wpdb->prefix}aq_facebook_connections"] = self::table_exists("{$wpdb->prefix}aq_facebook_connections");

		/**
		 * Facebook Forms Table
		 */
		$sql_fb_forms = "CREATE TABLE {$wpdb->prefix}aq_facebook_forms (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			connection_id bigint(20) UNSIGNED NOT NULL,
			fb_form_id varchar(50) NOT NULL,
			fb_form_name varchar(255) NOT NULL,
			campaign_group_id int UNSIGNED DEFAULT NULL,
			campaign_id int UNSIGNED DEFAULT NULL,
			source_id smallint UNSIGNED DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			leads_count int UNSIGNED NOT NULL DEFAULT 0,
			last_lead_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_connection (connection_id),
			KEY idx_fb_form (fb_form_id),
			KEY idx_campaign (campaign_id),
			KEY idx_active (is_active)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_fb_forms);
		$tables_created["{$wpdb->prefix}aq_facebook_forms"] = self::table_exists("{$wpdb->prefix}aq_facebook_forms");

		/**
		 * Facebook Field Mappings Table
		 */
		$sql_fb_mappings = "CREATE TABLE {$wpdb->prefix}aq_facebook_field_mappings (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id bigint(20) UNSIGNED NOT NULL,
			fb_field_name varchar(100) NOT NULL,
			fb_field_label varchar(255) NOT NULL,
			wp_field varchar(50) DEFAULT NULL,
			question_id bigint(20) UNSIGNED DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_form (form_id),
			KEY idx_question (question_id)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_fb_mappings);
		$tables_created["{$wpdb->prefix}aq_facebook_field_mappings"] = self::table_exists("{$wpdb->prefix}aq_facebook_field_mappings");

		/**
		 * Facebook Leads Log Table
		 */
		$sql_fb_log = "CREATE TABLE {$wpdb->prefix}aq_facebook_leads_log (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id bigint(20) UNSIGNED DEFAULT NULL,
			fb_lead_id varchar(50) NOT NULL,
			lead_id bigint(20) UNSIGNED DEFAULT NULL,
			raw_data longtext,
			status enum('received', 'processed', 'failed', 'duplicate') NOT NULL DEFAULT 'received',
			error_message text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_form (form_id),
			KEY idx_fb_lead (fb_lead_id),
			KEY idx_lead (lead_id),
			KEY idx_status (status),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_fb_log);
		$tables_created["{$wpdb->prefix}aq_facebook_leads_log"] = self::table_exists("{$wpdb->prefix}aq_facebook_leads_log");

		/**
		 * Automation Rules Table
		 */
		$sql_automation_rules = "CREATE TABLE {$wpdb->prefix}aq_automation_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_name varchar(100) NOT NULL,
			description text,
			trigger_event enum('lead_created', 'lead_updated', 'status_changed', 'no_response', 'follow_up_overdue', 'score_changed', 'communication_added') NOT NULL,
			conditions longtext DEFAULT NULL COMMENT 'JSON array of conditions',
			actions longtext DEFAULT NULL COMMENT 'JSON array of actions',
			is_active tinyint(1) NOT NULL DEFAULT 1,
			priority int NOT NULL DEFAULT 10,
			run_count int UNSIGNED NOT NULL DEFAULT 0,
			last_run_at datetime DEFAULT NULL,
			created_by bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_trigger (trigger_event),
			KEY idx_active (is_active),
			KEY idx_priority (priority)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_automation_rules);
		$tables_created["{$wpdb->prefix}aq_automation_rules"] = self::table_exists("{$wpdb->prefix}aq_automation_rules");

		/**
		 * Automation Logs Table
		 */
		$sql_automation_logs = "CREATE TABLE {$wpdb->prefix}aq_automation_logs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_id bigint(20) UNSIGNED NOT NULL,
			lead_id bigint(20) UNSIGNED DEFAULT NULL,
			trigger_event varchar(50) NOT NULL,
			conditions_matched longtext DEFAULT NULL COMMENT 'JSON',
			actions_executed longtext DEFAULT NULL COMMENT 'JSON',
			status enum('success', 'failed', 'partial') NOT NULL,
			error_message text,
			execution_time_ms int UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_rule (rule_id),
			KEY idx_lead (lead_id),
			KEY idx_status (status),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_automation_logs);
		$tables_created["{$wpdb->prefix}aq_automation_logs"] = self::table_exists("{$wpdb->prefix}aq_automation_logs");

		/**
		 * Bulk WhatsApp Jobs Table
		 */
		$sql_bulk_jobs = "CREATE TABLE {$wpdb->prefix}aq_bulk_whatsapp_jobs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_name varchar(100) NOT NULL,
			message_type enum('custom', 'template') NOT NULL,
			message_content text,
			template_name varchar(100) DEFAULT NULL,
			template_params longtext DEFAULT NULL COMMENT 'JSON',
			lead_ids longtext NOT NULL COMMENT 'JSON array of lead IDs',
			total_count int UNSIGNED NOT NULL DEFAULT 0,
			sent_count int UNSIGNED NOT NULL DEFAULT 0,
			failed_count int UNSIGNED NOT NULL DEFAULT 0,
			status enum('pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
			created_by bigint(20) UNSIGNED NOT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_status (status),
			KEY idx_created (created_at),
			KEY idx_user (created_by)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_bulk_jobs);
		$tables_created["{$wpdb->prefix}aq_bulk_whatsapp_jobs"] = self::table_exists("{$wpdb->prefix}aq_bulk_whatsapp_jobs");

		/**
		 * Bulk WhatsApp Results Table
		 */
		$sql_bulk_results = "CREATE TABLE {$wpdb->prefix}aq_bulk_whatsapp_results (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id bigint(20) UNSIGNED NOT NULL,
			lead_id bigint(20) UNSIGNED NOT NULL,
			phone_number varchar(20) NOT NULL,
			status enum('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
			error_message text,
			wa_message_id varchar(100) DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_job (job_id),
			KEY idx_lead (lead_id),
			KEY idx_status (status)
		) ENGINE=InnoDB $charset_collate;";

		dbDelta($sql_bulk_results);
		$tables_created["{$wpdb->prefix}aq_bulk_whatsapp_results"] = self::table_exists("{$wpdb->prefix}aq_bulk_whatsapp_results");

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
	private static function populate_initial_data()
	{
		global $wpdb;

		$status = array(
			'statuses' => 0,
			'sources' => 0,
		);

		// Populate lead statuses.
		$statuses = array(
			array('pending', 'معلق', 'Pending', 1, '#718096', 1),
			array('contacted', 'تم الاتصال', 'Contacted', 2, '#4299e1', 1),
			array('qualified', 'مؤهل', 'Qualified', 3, '#ed8936', 1),
			array('converted', 'محول', 'Converted', 4, '#48bb78', 1),
			array('lost', 'خاسر', 'Lost', 5, '#f56565', 1),
		);

		foreach ($statuses as $status_data) {
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
			if ($wpdb->insert_id > 0) {
				$status['statuses']++;
			}
		}

		// Populate lead sources.
		$sources = array(
			array('facebook', 'Facebook Ads', 'paid', 5.00, 1),
			array('google', 'Google Ads', 'paid', 7.50, 1),
			array('instagram', 'Instagram Ads', 'paid', 4.00, 1),
			array('website', 'Website Form', 'organic', 0.00, 1),
			array('referral', 'Referral', 'referral', 0.00, 1),
			array('direct', 'Direct Contact', 'direct', 0.00, 1),
		);

		foreach ($sources as $source_data) {
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
			if ($wpdb->insert_id > 0) {
				$status['sources']++;
			}
		}

		// Populate default scoring rules.
		$rules = array(
			array('Response within 1 hour', 'add', 'response_time', 'less_than', '60', 20, 10),
			array('Response within 24 hours', 'add', 'response_time', 'less_than', '1440', 10, 9),
			array('Has WhatsApp interactions', 'add', 'interactions_count', 'greater_than', '0', 5, 8),
			array('Has phone number', 'add', 'phone', 'not_equals', '', 10, 8),
			array('Has email', 'add', 'email', 'not_equals', '', 5, 8),
			array('Priority Country (SA, AE)', 'add', 'country', 'in_list', 'SA,AE', 15, 7),
			array('High Budget', 'add', 'budget', 'equals', 'high', 20, 6),
			array('Medium Budget', 'add', 'budget', 'equals', 'medium', 10, 6),
			array('Requested Callback', 'add', 'tags', 'contains', 'callback', 15, 5),
			array('Multiple Inquiries', 'add', 'inquiries_count', 'greater_than', '1', 10, 5),
			array('No response > 48h', 'subtract', 'no_response_time', 'greater_than', '2880', 15, 5),
			array('Invalid Phone', 'subtract', 'validation_status', 'equals', 'invalid_phone', 20, 10),
			array('Marked as Spam', 'subtract', 'status', 'equals', 'spam', 50, 10),
			array('Unsubscribed', 'subtract', 'status', 'equals', 'unsubscribed', 30, 10),
		);

		foreach ($rules as $rule) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->prefix}aq_scoring_rules 
					(rule_name, rule_type, condition_field, condition_operator, condition_value, score_points, priority) 
					VALUES (%s, %s, %s, %s, %s, %d, %d)",
					$rule[0],
					$rule[1],
					$rule[2],
					$rule[3],
					$rule[4],
					$rule[5],
					$rule[6]
				)
			);
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
	private static function register_module()
	{
		global $wpdb;

		// Check if already registered.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_modules WHERE module_code = %s",
				'leads'
			)
		);

		if (!$exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'aq_dim_modules',
				array(
					'module_code' => 'leads',
					'module_name' => 'Leads Module',
					'is_active' => 1,
				),
				array('%s', '%s', '%d')
			);
		}
	}

	/**
	 * Upgrade database - add missing columns.
	 *
	 * Ensures all required columns exist for Airtable sync.
	 *
	 * @since  1.0.1
	 * @static
	 */
	public static function upgrade_database()
	{
		global $wpdb;

		$table = $wpdb->prefix . 'aq_leads';

		// Check if table exists first
		if (!self::table_exists($table)) {
			return;
		}

		// List of columns to add if missing (column_name => column_definition)
		$columns_to_add = array(
			'group_id' => 'int UNSIGNED DEFAULT NULL AFTER campaign_id',
			'platform' => 'varchar(50) DEFAULT NULL AFTER group_id',
			'custom_data' => 'longtext DEFAULT NULL AFTER custom_fields',
			'lost_reason' => 'text DEFAULT NULL AFTER notes',
			'deal_stage' => "varchar(30) DEFAULT NULL AFTER lost_reason",
			'learning_path_id' => 'int UNSIGNED DEFAULT NULL AFTER deal_stage',
		);

		// Create learning_paths table if not exists
		$lp_table = $wpdb->prefix . 'aq_learning_paths';
		if (!self::table_exists($lp_table)) {
			$charset_collate = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_learning_paths = "CREATE TABLE {$lp_table} (
				id int UNSIGNED NOT NULL AUTO_INCREMENT,
				name_en varchar(255) NOT NULL,
				name_ar varchar(255) NOT NULL,
				description text DEFAULT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				display_order int UNSIGNED NOT NULL DEFAULT 0,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_active (is_active),
				KEY idx_order (display_order)
			) ENGINE=InnoDB {$charset_collate};";

			dbDelta($sql_learning_paths);

			// Insert default learning paths
			$defaults = array(
				array('Fluency (Quran)', 'الطلاقة (القرآن الكريم)', 1),
				array('Languages', 'اللغات', 2),
			);

			foreach ($defaults as $path) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$lp_table,
					array(
						'name_en' => $path[0],
						'name_ar' => $path[1],
						'display_order' => $path[2],
						'is_active' => 1,
					),
					array('%s', '%s', '%d', '%d')
				);
			}

			error_log('AQOP: Created learning_paths table with default data');
		}

		// Create FAQ table if not exists
		$faq_table = $wpdb->prefix . 'aq_faq';
		if (!self::table_exists($faq_table)) {
			$charset_collate = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_faq = "CREATE TABLE {$faq_table} (
				id int UNSIGNED NOT NULL AUTO_INCREMENT,
				country_id int UNSIGNED DEFAULT NULL,
				category varchar(100) DEFAULT NULL,
				question text NOT NULL,
				answer text NOT NULL,
				display_order int UNSIGNED NOT NULL DEFAULT 0,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_country (country_id),
				KEY idx_active (is_active),
				KEY idx_category (category),
				KEY idx_order (display_order)
			) ENGINE=InnoDB {$charset_collate};";

			dbDelta($sql_faq);
			error_log('AQOP: Created FAQ table');
		}

		// Conversion Targets table
		$targets_table = $wpdb->prefix . 'aq_conversion_targets';
		if (!self::table_exists($targets_table)) {
			$charset_collate = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_targets = "CREATE TABLE {$targets_table} (
				id int UNSIGNED NOT NULL AUTO_INCREMENT,
				country_id int UNSIGNED DEFAULT NULL COMMENT 'NULL = global targets',
				lead_to_response_target decimal(5,2) NOT NULL DEFAULT 30.00,
				response_to_qualified_target decimal(5,2) NOT NULL DEFAULT 25.00,
				qualified_to_converted_target decimal(5,2) NOT NULL DEFAULT 40.00,
				overall_target decimal(5,2) NOT NULL DEFAULT 5.00,
				created_by bigint(20) UNSIGNED DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY idx_country (country_id),
				KEY idx_created_by (created_by)
			) ENGINE=InnoDB {$charset_collate};";

			dbDelta($sql_targets);
			
			// Insert default global targets
			$wpdb->insert(
				$targets_table,
				array(
					'country_id' => null,
					'lead_to_response_target' => 30.00,
					'response_to_qualified_target' => 25.00,
					'qualified_to_converted_target' => 40.00,
					'overall_target' => 5.00,
					'created_by' => get_current_user_id(),
				),
				array('%d', '%f', '%f', '%f', '%f', '%d')
			);
			
			error_log('AQOP: Created Conversion Targets table with default global targets');
		}

		foreach ($columns_to_add as $column_name => $column_def) {
			// Check if column exists
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$column = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$table} LIKE %s",
					$column_name
				)
			);

			if (empty($column)) {
				// Add the missing column
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column_name} {$column_def}");

				// Add index for group_id if it was just added
				if ($column_name === 'group_id') {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->query("ALTER TABLE {$table} ADD INDEX idx_group_id ({$column_name})");
				}

				error_log("AQOP: Added missing column {$column_name} to {$table}");
			}
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
}

