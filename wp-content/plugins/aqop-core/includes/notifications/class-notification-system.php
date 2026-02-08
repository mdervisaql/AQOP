<?php
/**
 * Notification System Class
 *
 * Central notification management system with rule-based routing.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Notification_System
{

    /**
     * Send notification.
     *
     * Main entry point for all notifications across the platform.
     *
     * @since  1.0.0
     * @param  array $params Notification parameters.
     * @return array Result with success status and notification IDs.
     */
    public static function send($params)
    {
        global $wpdb;

        try {
            // Validate required parameters.
            if (empty($params['module']) || empty($params['event'])) {
                throw new Exception('Module and event are required');
            }

            $module = sanitize_text_field($params['module']);
            $event = sanitize_text_field($params['event']);
            $event_id = isset($params['event_id']) ? absint($params['event_id']) : null;
            $data = isset($params['data']) ? $params['data'] : array();
            $priority = isset($params['priority']) ? $params['priority'] : 'medium';

            // Check for forced channels/recipients (bypass rules).
            $force_channels = isset($params['force_channels']) ? $params['force_channels'] : null;
            $force_recipients = isset($params['force_recipients']) ? $params['force_recipients'] : null;

            $notification_ids = array();

            if ($force_channels && $force_recipients) {
                // Bypass rules - send directly.
                foreach ($force_channels as $channel) {
                    $notification_id = self::create_notification(
                        $module,
                        $event,
                        $event_id,
                        $priority,
                        $channel,
                        $force_recipients,
                        $data
                    );

                    if ($notification_id) {
                        $notification_ids[] = $notification_id;
                        self::dispatch_notification($notification_id);
                    }
                }
            } else {
                // Apply rules.
                $matching_rules = self::get_matching_rules($module, $event, $data);

                foreach ($matching_rules as $rule) {
                    $channels = json_decode($rule->channels, true);
                    $recipient_config = json_decode($rule->recipient_config, true);

                    foreach ($channels as $channel) {
                        $recipients = self::resolve_recipients($recipient_config[$channel] ?? array());

                        if (empty($recipients)) {
                            continue;
                        }

                        $message = self::render_template(
                            $rule->message_template_body,
                            $data
                        );

                        $notification_id = self::create_notification(
                            $module,
                            $event,
                            $event_id,
                            $rule->priority,
                            $channel,
                            $recipients,
                            $data,
                            $rule->message_template_subject,
                            $message
                        );

                        if ($notification_id) {
                            $notification_ids[] = $notification_id;
                            self::dispatch_notification($notification_id);
                        }
                    }
                }
            }

            // Log event.
            if (class_exists('AQOP_Event_Logger')) {
                AQOP_Event_Logger::log(
                    'core',
                    'notification_sent',
                    'notification',
                    0,
                    array(
                        'module' => $module,
                        'event' => $event,
                        'notifications' => count($notification_ids),
                        'notification_ids' => $notification_ids,
                    )
                );
            }

            return array(
                'success' => true,
                'notification_ids' => $notification_ids,
                'count' => count($notification_ids),
            );

        } catch (Exception $e) {
            error_log('AQOP Notification System: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage(),
            );
        }
    }

    /**
     * Get matching rules.
     *
     * @since  1.0.0
     * @param  string $module Module code.
     * @param  string $event  Event type.
     * @param  array  $data   Event data.
     * @return array Matching rules.
     */
    private static function get_matching_rules($module, $event, $data)
    {
        global $wpdb;

        $rules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notification_rules 
				WHERE module_code = %s 
				AND event_type = %s 
				AND is_active = 1 
				ORDER BY execution_order ASC",
                $module,
                $event
            )
        );

        $matching_rules = array();

        foreach ($rules as $rule) {
            if (self::evaluate_conditions($rule->conditions, $data)) {
                $matching_rules[] = $rule;
            }
        }

        return $matching_rules;
    }

    /**
     * Evaluate conditions.
     *
     * @since  1.0.0
     * @param  string $conditions_json JSON conditions.
     * @param  array  $data            Event data.
     * @return bool True if conditions match.
     */
    private static function evaluate_conditions($conditions_json, $data)
    {
        if (empty($conditions_json)) {
            return true; // No conditions = always match.
        }

        $conditions = json_decode($conditions_json, true);

        if (!is_array($conditions)) {
            return true;
        }

        foreach ($conditions as $key => $value) {
            if (!isset($data[$key]) || $data[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve recipients.
     *
     * @since  1.0.0
     * @param  array $config Recipient configuration.
     * @return array Recipients array.
     */
    private static function resolve_recipients($config)
    {
        $recipients = array();

        if (empty($config)) {
            return $recipients;
        }

        $type = $config['type'] ?? 'role';

        switch ($type) {
            case 'role':
                $roles = $config['roles'] ?? array();
                foreach ($roles as $role) {
                    $users = get_users(array('role' => $role));
                    foreach ($users as $user) {
                        $recipients[] = 'user:' . $user->ID;
                    }
                }
                break;

            case 'user':
                $user_ids = $config['user_ids'] ?? array();
                foreach ($user_ids as $user_id) {
                    $recipients[] = 'user:' . $user_id;
                }
                break;

            case 'custom':
                $recipients = $config['recipients'] ?? array();
                break;
        }

        return array_unique($recipients);
    }

    /**
     * Render template.
     *
     * @since  1.0.0
     * @param  string $template Template string.
     * @param  array  $data     Data for variables.
     * @return string Rendered message.
     */
    private static function render_template($template, $data)
    {
        $message = $template;

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $message = str_replace('{{' . $key . '}}', $value, $message);
            }
        }

        return $message;
    }

    /**
     * Create notification record.
     *
     * @since  1.0.0
     * @param  string $module   Module code.
     * @param  string $event    Event type.
     * @param  int    $event_id Event ID.
     * @param  string $priority Priority.
     * @param  string $channel  Channel.
     * @param  array  $recipients Recipients.
     * @param  array  $data     Metadata.
     * @param  string $subject  Subject.
     * @param  string $message  Message.
     * @return int|false Notification ID.
     */
    private static function create_notification($module, $event, $event_id, $priority, $channel, $recipients, $data, $subject = null, $message = null)
    {
        global $wpdb;

        // For multiple recipients, create one notification per recipient.
        $first_recipient = is_array($recipients) ? $recipients[0] : $recipients;
        list($recipient_type, $recipient_id) = explode(':', $first_recipient, 2);

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'aq_notifications',
            array(
                'module_code' => $module,
                'event_type' => $event,
                'event_id' => $event_id,
                'priority' => $priority,
                'channel' => $channel,
                'recipient_type' => $recipient_type,
                'recipient_id' => $recipient_id,
                'message_subject' => $subject,
                'message_body' => $message ?? json_encode($data),
                'metadata' => json_encode($data),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Dispatch notification.
     *
     * @since  1.0.0
     * @param  int $notification_id Notification ID.
     * @return bool Success status.
     */
    private static function dispatch_notification($notification_id)
    {
        global $wpdb;

        $notification = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notifications WHERE id = %d",
                $notification_id
            )
        );

        if (!$notification) {
            return false;
        }

        $result = false;

        switch ($notification->channel) {
            case 'telegram':
                $result = self::send_telegram($notification);
                break;

            case 'email':
                $result = self::send_email($notification);
                break;
        }

        // Update notification status.
        $wpdb->update(
            $wpdb->prefix . 'aq_notifications',
            array(
                'status' => $result ? 'sent' : 'failed',
                'sent_at' => $result ? current_time('mysql') : null,
                'failed_reason' => $result ? null : 'Dispatch failed',
            ),
            array('id' => $notification_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        return $result;
    }

    /**
     * Send Telegram notification.
     *
     * @since  1.0.0
     * @param  object $notification Notification object.
     * @return bool Success status.
     */
    private static function send_telegram($notification)
    {
        if (!class_exists('AQOP_Integrations_Hub')) {
            return false;
        }

        return AQOP_Integrations_Hub::send_telegram(
            $notification->message_body,
            $notification->module_code
        );
    }

    /**
     * Send Email notification.
     *
     * @since  1.0.0
     * @param  object $notification Notification object.
     * @return bool Success status.
     */
    private static function send_email($notification)
    {
        // Get recipient email.
        if ('user' === $notification->recipient_type) {
            $user = get_userdata($notification->recipient_id);
            if (!$user) {
                return false;
            }
            $to = $user->user_email;
        } else {
            $to = $notification->recipient_id; // Custom email.
        }

        $subject = $notification->message_subject ?? 'AQOP Platform Notification';
        $message = $notification->message_body;

        return wp_mail($to, $subject, $message);
    }

    /**
     * Get notification statistics.
     *
     * @since  1.0.0
     * @param  array $params Query parameters.
     * @return array Statistics.
     */
    public static function get_stats($params = array())
    {
        global $wpdb;

        $module = isset($params['module']) ? sanitize_text_field($params['module']) : null;
        $date_from = isset($params['date_from']) ? $params['date_from'] : null;
        $date_to = isset($params['date_to']) ? $params['date_to'] : null;

        $where = array();
        $where_values = array();

        if ($module) {
            $where[] = 'module_code = %s';
            $where_values[] = $module;
        }

        if ($date_from) {
            $where[] = 'created_at >= %s';
            $where_values[] = $date_from;
        }

        if ($date_to) {
            $where[] = 'created_at <= %s';
            $where_values[] = $date_to;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total notifications.
        $total_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}aq_notifications {$where_sql}";
        $total = $wpdb->get_var(!empty($where_values) ? $wpdb->prepare($total_sql, $where_values) : $total_sql);

        // By status.
        $by_status_sql = "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}aq_notifications {$where_sql} GROUP BY status";
        $by_status = $wpdb->get_results(!empty($where_values) ? $wpdb->prepare($by_status_sql, $where_values) : $by_status_sql);

        // By channel.
        $by_channel_sql = "SELECT channel, COUNT(*) as count FROM {$wpdb->prefix}aq_notifications {$where_sql} GROUP BY channel";
        $by_channel = $wpdb->get_results(!empty($where_values) ? $wpdb->prepare($by_channel_sql, $where_values) : $by_channel_sql);

        // By module.
        $by_module_sql = "SELECT module_code, COUNT(*) as count FROM {$wpdb->prefix}aq_notifications {$where_sql} GROUP BY module_code";
        $by_module = $wpdb->get_results(!empty($where_values) ? $wpdb->prepare($by_module_sql, $where_values) : $by_module_sql);

        return array(
            'total' => (int) $total,
            'by_status' => $by_status,
            'by_channel' => $by_channel,
            'by_module' => $by_module,
        );
    }
}
