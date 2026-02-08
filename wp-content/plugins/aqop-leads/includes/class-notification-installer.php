<?php
/**
 * Notification System Installer Class
 *
 * Handles database table creation for the notification system.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Notification_Installer class.
 *
 * Manages installation of the Notification System tables.
 *
 * @since 1.1.0
 */
class AQOP_Notification_Installer
{

    /**
     * Run installer.
     *
     * Creates tables.
     *
     * @since  1.1.0
     * @static
     * @return array Installation status.
     */
    public static function install()
    {
        global $wpdb;

        $status = array(
            'success' => false,
            'tables_created' => array(),
            'errors' => array(),
        );

        // Create tables.
        $tables_result = self::create_tables();
        $status['tables_created'] = $tables_result;

        $status['success'] = true;

        return $status;
    }

    /**
     * Create database tables.
     *
     * @since  1.1.0
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
         * 1. Notification Templates Table
         */
        $sql_templates = "CREATE TABLE {$wpdb->prefix}aq_notification_templates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            title_template text NOT NULL,
            message_template text NOT NULL,
            notification_channels longtext NOT NULL COMMENT 'JSON array of channels',
            enabled tinyint(1) NOT NULL DEFAULT 1,
            target_roles longtext DEFAULT NULL COMMENT 'JSON array of roles',
            priority enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
            push_enabled tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY event_type (event_type),
            KEY idx_enabled (enabled)
        ) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_templates);
        $tables_created["{$wpdb->prefix}aq_notification_templates"] = self::table_exists("{$wpdb->prefix}aq_notification_templates");

        /**
         * 2. Notifications Table
         */
        $sql_notifications = "CREATE TABLE {$wpdb->prefix}aq_notifications (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            template_id bigint(20) UNSIGNED DEFAULT NULL,
            title text NOT NULL,
            message text NOT NULL,
            notification_type enum('info','success','warning','error') NOT NULL DEFAULT 'info',
            priority enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
            is_read tinyint(1) NOT NULL DEFAULT 0,
            read_at datetime DEFAULT NULL,
            data longtext DEFAULT NULL COMMENT 'JSON contextual data',
            action_url varchar(255) DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user (user_id),
            KEY idx_read (is_read),
            KEY idx_created (created_at),
            KEY idx_expires (expires_at)
        ) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_notifications);
        $tables_created["{$wpdb->prefix}aq_notifications"] = self::table_exists("{$wpdb->prefix}aq_notifications");

        /**
         * 3. Notification Settings Table
         */
        $sql_settings = "CREATE TABLE {$wpdb->prefix}aq_notification_settings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            event_type varchar(100) NOT NULL,
            in_app_enabled tinyint(1) NOT NULL DEFAULT 1,
            telegram_enabled tinyint(1) NOT NULL DEFAULT 0,
            email_enabled tinyint(1) NOT NULL DEFAULT 0,
            push_enabled tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_event (user_id, event_type),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_settings);
        $tables_created["{$wpdb->prefix}aq_notification_settings"] = self::table_exists("{$wpdb->prefix}aq_notification_settings");

        /**
         * 4. Push Subscriptions Table
         */
        $sql_push = "CREATE TABLE {$wpdb->prefix}aq_push_subscriptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            endpoint text NOT NULL,
            auth_key text NOT NULL,
            p256dh_key text NOT NULL,
            user_agent varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_used_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user (user_id),
            KEY idx_active (is_active)
        ) ENGINE=InnoDB $charset_collate;";

        dbDelta($sql_push);
        $tables_created["{$wpdb->prefix}aq_push_subscriptions"] = self::table_exists("{$wpdb->prefix}aq_push_subscriptions");

        return $tables_created;
    }

    /**
     * Check if table exists.
     *
     * @since  1.1.0
     * @static
     * @access private
     * @param  string $table_name Full table name.
     * @return bool True if exists.
     */
    private static function table_exists($table_name)
    {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        return $result === $table_name;
    }
}
