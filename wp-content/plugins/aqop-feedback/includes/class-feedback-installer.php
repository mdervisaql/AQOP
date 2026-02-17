<?php
/**
 * Feedback System Installer
 *
 * Handles database table creation and initial data population.
 *
 * @package AQOP_Feedback
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Feedback_Installer
{

    /**
     * Activate plugin.
     *
     * @since 1.0.0
     */
    public static function activate()
    {
        self::create_tables();
        self::populate_initial_data();
        self::register_module();

        update_option('aqop_feedback_version', AQOP_FEEDBACK_VERSION);
        update_option('aqop_feedback_install_date', current_time('mysql'));
    }

    /**
     * Create database tables.
     *
     * @since 1.0.0
     */
    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Main feedback table.
        $sql_feedback = "CREATE TABLE {$wpdb->prefix}aq_feedback (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			module_code varchar(50) NOT NULL,
			feedback_type enum('bug','feature_request','improvement','question') NOT NULL,
			title varchar(255) NOT NULL,
			description text NOT NULL,
			priority enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
			status_id tinyint UNSIGNED NOT NULL DEFAULT 1,
			screenshot_url varchar(500) DEFAULT NULL,
			browser_info text DEFAULT NULL,
			page_url varchar(500) DEFAULT NULL,
			assigned_to bigint(20) UNSIGNED DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL,
			resolved_at datetime DEFAULT NULL,
			resolved_by bigint(20) UNSIGNED DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY idx_module (module_code),
			KEY idx_status (status_id),
			KEY idx_user (user_id),
			KEY idx_assigned (assigned_to),
			KEY idx_type (feedback_type),
			KEY idx_priority (priority),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_feedback);

        // Comments table.
        $sql_comments = "CREATE TABLE {$wpdb->prefix}aq_feedback_comments (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			feedback_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			comment_text text NOT NULL,
			is_internal tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_feedback (feedback_id),
			KEY idx_user (user_id),
			KEY idx_created (created_at)
		) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_comments);

        // Status table.
        $sql_status = "CREATE TABLE {$wpdb->prefix}aq_feedback_status (
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
    }

    /**
     * Populate initial data.
     *
     * @since 1.0.0
     */
    private static function populate_initial_data()
    {
        global $wpdb;

        // Populate statuses.
        $statuses = array(
            array('new', 'جديد', 'New', 1, '#718096', 1),
            array('in_progress', 'قيد المعالجة', 'In Progress', 2, '#4299e1', 1),
            array('resolved', 'تم الحل', 'Resolved', 3, '#48bb78', 1),
            array('closed', 'مغلق', 'Closed', 4, '#2d3748', 1),
            array('wont_fix', 'لن يتم الحل', 'Won\'t Fix', 5, '#f56565', 1),
        );

        foreach ($statuses as $status_data) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->prefix}aq_feedback_status 
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
        }
    }

    /**
     * Register module in core.
     *
     * @since 1.0.0
     */
    private static function register_module()
    {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_dim_modules WHERE module_code = %s",
                'feedback'
            )
        );

        if (!$exists) {
            $wpdb->insert(
                $wpdb->prefix . 'aq_dim_modules',
                array(
                    'module_code' => 'feedback',
                    'module_name' => 'Feedback System',
                    'is_active' => 1,
                ),
                array('%s', '%s', '%d')
            );
        }
    }
}
