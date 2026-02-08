<?php
/**
 * Notifications API Class
 *
 * REST API endpoints for user notifications and settings.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Notifications_API class.
 *
 * @since 1.1.0
 */
class AQOP_Notifications_API extends WP_REST_Controller
{

    /**
     * Register routes.
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        $namespace = 'aqop/v1';
        $base = 'notifications';

        // Get user notifications
        register_rest_route($namespace, '/' . $base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_notifications'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Get unread count
        register_rest_route($namespace, '/' . $base . '/unread-count', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_unread_count'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Mark as read
        register_rest_route($namespace, '/' . $base . '/(?P<id>\d+)/read', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'mark_as_read'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Mark all as read
        register_rest_route($namespace, '/' . $base . '/mark-all-read', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'mark_all_read'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Get settings
        register_rest_route($namespace, '/' . $base . '/settings', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_auth'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Push Subscribe
        register_rest_route($namespace, '/push/subscribe', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'push_subscribe'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));

        // Get VAPID Key
        register_rest_route($namespace, '/push/vapid-key', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_vapid_key'),
                'permission_callback' => array($this, 'check_auth'),
            ),
        ));
    }

    /**
     * Check authentication.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if authorized.
     */
    public function check_auth($request)
    {
        return is_user_logged_in();
    }

    /**
     * Get notifications.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_notifications($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $page = $request->get_param('page') ? absint($request->get_param('page')) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notifications 
                WHERE user_id = %d 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                $offset
            )
        );

        foreach ($notifications as $note) {
            $note->data = json_decode($note->data);
            $note->is_read = (bool) $note->is_read;
        }

        return rest_ensure_response($notifications);
    }

    /**
     * Get unread count.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_unread_count($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aq_notifications WHERE user_id = %d AND is_read = 0",
                $user_id
            )
        );

        return rest_ensure_response(array('count' => (int) $count));
    }

    /**
     * Mark as read.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function mark_as_read($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $id = $request->get_param('id');

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_notifications',
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ),
            array(
                'id' => $id,
                'user_id' => $user_id,
            ),
            array('%d', '%s'),
            array('%d', '%d')
        );

        return rest_ensure_response(array('success' => (bool) $updated));
    }

    /**
     * Mark all as read.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function mark_all_read($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_notifications',
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ),
            array(
                'user_id' => $user_id,
                'is_read' => 0,
            ),
            array('%d', '%s'),
            array('%d', '%d')
        );

        return rest_ensure_response(array('success' => true, 'updated' => $updated));
    }

    /**
     * Get settings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_settings($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        $settings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_notification_settings WHERE user_id = %d",
                $user_id
            )
        );

        return rest_ensure_response($settings);
    }

    /**
     * Update settings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function update_settings($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $settings = $request->get_param('settings'); // Array of settings

        if (!is_array($settings)) {
            return new WP_Error('invalid_params', 'Settings must be an array', array('status' => 400));
        }

        foreach ($settings as $setting) {
            $event_type = sanitize_text_field($setting['event_type']);

            // Check if exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}aq_notification_settings WHERE user_id = %d AND event_type = %s",
                    $user_id,
                    $event_type
                )
            );

            $data = array(
                'user_id' => $user_id,
                'event_type' => $event_type,
                'in_app_enabled' => isset($setting['in_app_enabled']) ? (int) $setting['in_app_enabled'] : 1,
                'telegram_enabled' => isset($setting['telegram_enabled']) ? (int) $setting['telegram_enabled'] : 0,
                'email_enabled' => isset($setting['email_enabled']) ? (int) $setting['email_enabled'] : 0,
                'push_enabled' => isset($setting['push_enabled']) ? (int) $setting['push_enabled'] : 0,
            );

            if ($exists) {
                $wpdb->update($wpdb->prefix . 'aq_notification_settings', $data, array('id' => $exists));
            } else {
                $wpdb->insert($wpdb->prefix . 'aq_notification_settings', $data);
            }
        }

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Push Subscribe.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function push_subscribe($request)
    {
        if (!class_exists('AQOP_Push_Notification_Manager')) {
            return new WP_Error('push_disabled', 'Push notifications not enabled', array('status' => 501));
        }

        $user_id = get_current_user_id();
        $subscription = $request->get_json_params();

        $id = AQOP_Push_Notification_Manager::save_subscription($user_id, $subscription);

        if ($id) {
            return rest_ensure_response(array('success' => true, 'id' => $id));
        }

        return new WP_Error('save_failed', 'Failed to save subscription', array('status' => 500));
    }

    /**
     * Get VAPID Public Key.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_vapid_key($request)
    {
        $key = get_option('aqop_push_vapid_public_key');
        return rest_ensure_response(array('key' => $key));
    }
}
