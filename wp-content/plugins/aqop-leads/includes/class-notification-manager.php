<?php
/**
 * Notification Manager Class
 *
 * Handles triggering, rendering, and dispatching notifications across all channels.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Notification_Manager class.
 *
 * Central logic for the notification system.
 *
 * @since 1.1.0
 */
class AQOP_Notification_Manager
{

    /**
     * Trigger a notification event.
     *
     * @since  1.1.0
     * @static
     * @param  string $event_type Event type (e.g., 'lead_assigned').
     * @param  array  $data       Contextual data for the event.
     * @param  array  $target_users Optional. Specific user IDs to notify. If null, determined by template roles.
     * @return int    Number of notifications sent/queued.
     */
    public static function trigger_notification($event_type, $data = array(), $target_users = null)
    {
        global $wpdb;

        // 1. Get active template for this event
        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notification_templates WHERE event_type = %s AND enabled = 1",
                $event_type
            )
        );

        if (!$template) {
            return 0;
        }

        // 2. Determine target users if not provided
        if (is_null($target_users)) {
            $target_roles = json_decode($template->target_roles, true);
            $target_users = self::get_target_users($event_type, $target_roles, $data);
        }

        if (empty($target_users)) {
            return 0;
        }

        $sent_count = 0;
        $channels = json_decode($template->notification_channels, true);
        $push_enabled = (bool) $template->push_enabled;

        // 3. Send to each user
        foreach ($target_users as $user_id) {
            // Get user settings
            $settings = self::get_user_settings($user_id, $event_type);

            // Render content
            $title = self::render_template($template->title_template, $data, $user_id);
            $message = self::render_template($template->message_template, $data, $user_id);

            // Channel: In-App
            if (in_array('in_app', $channels) && $settings['in_app_enabled']) {
                self::send_in_app($user_id, $template->id, $title, $message, $template->priority, $data);
                $sent_count++;
            }

            // Channel: Push
            if ($push_enabled && $settings['push_enabled']) {
                if (class_exists('AQOP_Push_Notification_Manager')) {
                    AQOP_Push_Notification_Manager::send_push_notification($user_id, $title, $message, $data);
                }
            }

            // Channel: Telegram
            if (in_array('telegram', $channels) && $settings['telegram_enabled']) {
                self::send_telegram($user_id, $message);
            }

            // Channel: Email
            if (in_array('email', $channels) && $settings['email_enabled']) {
                self::send_email($user_id, $title, $message);
            }
        }

        return $sent_count;
    }

    /**
     * Send In-App Notification.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function send_in_app($user_id, $template_id, $title, $message, $priority, $data)
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'aq_notifications',
            array(
                'user_id' => $user_id,
                'template_id' => $template_id,
                'title' => $title,
                'message' => $message,
                'priority' => $priority,
                'data' => json_encode($data),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Send Telegram Notification.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function send_telegram($user_id, $message)
    {
        // Check if core integration exists
        if (!class_exists('AQOP_Integrations_Hub')) {
            return;
        }

        // Get user's telegram chat ID (assuming stored in user meta)
        $chat_id = get_user_meta($user_id, 'aq_telegram_chat_id', true);

        if ($chat_id) {
            AQOP_Integrations_Hub::send_telegram($chat_id, $message);
        }
    }

    /**
     * Send Email Notification.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function send_email($user_id, $subject, $message)
    {
        $user = get_userdata($user_id);
        if ($user) {
            wp_mail($user->user_email, $subject, $message);
        }
    }

    /**
     * Get target users based on roles and context.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function get_target_users($event_type, $roles, $data)
    {
        $user_ids = array();

        // Special handling for dynamic roles
        if ($event_type === 'lead_assigned' && isset($data['assigned_to'])) {
            $user_ids[] = $data['assigned_to'];
        }

        // Standard role-based fetching
        if (!empty($roles)) {
            $query_args = array(
                'role__in' => $roles,
                'fields' => 'ID',
            );
            $users = get_users($query_args);
            $user_ids = array_merge($user_ids, $users);
        }

        return array_unique($user_ids);
    }

    /**
     * Get user notification settings.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function get_user_settings($user_id, $event_type)
    {
        global $wpdb;

        // Default settings
        $settings = array(
            'in_app_enabled' => true,
            'telegram_enabled' => false,
            'email_enabled' => false,
            'push_enabled' => false,
        );

        $user_settings = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notification_settings WHERE user_id = %d AND event_type = %s",
                $user_id,
                $event_type
            ),
            ARRAY_A
        );

        if ($user_settings) {
            $settings['in_app_enabled'] = (bool) $user_settings['in_app_enabled'];
            $settings['telegram_enabled'] = (bool) $user_settings['telegram_enabled'];
            $settings['email_enabled'] = (bool) $user_settings['email_enabled'];
            $settings['push_enabled'] = (bool) $user_settings['push_enabled'];
        }

        return $settings;
    }

    /**
     * Render template variables.
     *
     * @since  1.1.0
     * @static
     * @access private
     */
    private static function render_template($template, $data, $user_id)
    {
        $replacements = array();

        // Lead Data
        if (isset($data['lead_id'])) {
            $replacements['{lead_id}'] = $data['lead_id'];
            $replacements['{lead_name}'] = $data['name'] ?? '';
            $replacements['{lead_email}'] = $data['email'] ?? '';
            $replacements['{lead_phone}'] = $data['phone'] ?? '';
            $replacements['{lead_country}'] = $data['country_name'] ?? '';
            $replacements['{lead_status}'] = $data['status_name'] ?? '';
        }

        // User Data
        $user = get_userdata($user_id);
        if ($user) {
            $replacements['{user_name}'] = $user->display_name;
            $replacements['{user_email}'] = $user->user_email;
        }

        // System Data
        $replacements['{site_name}'] = get_bloginfo('name');
        $replacements['{site_url}'] = site_url();

        return strtr($template, $replacements);
    }
}
