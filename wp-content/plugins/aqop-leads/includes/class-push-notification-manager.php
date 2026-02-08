<?php
/**
 * Push Notification Manager Class
 *
 * Handles browser push notifications using Web Push.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * AQOP_Push_Notification_Manager class.
 *
 * Manages VAPID keys and sending push notifications.
 *
 * @since 1.1.0
 */
class AQOP_Push_Notification_Manager
{

    /**
     * Send push notification to a user.
     *
     * @since  1.1.0
     * @static
     * @param  int    $user_id User ID.
     * @param  string $title   Notification title.
     * @param  string $body    Notification body.
     * @param  array  $data    Additional data (url, etc.).
     * @return bool Success status.
     */
    public static function send_push_notification($user_id, $title, $body, $data = array())
    {
        global $wpdb;

        // Get active subscriptions for user
        $subscriptions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_push_subscriptions WHERE user_id = %d AND is_active = 1",
                $user_id
            )
        );

        if (empty($subscriptions)) {
            return false;
        }

        // Get VAPID keys
        $auth = array(
            'VAPID' => array(
                'subject' => get_option('aqop_push_subject', 'mailto:admin@example.com'),
                'publicKey' => get_option('aqop_push_vapid_public_key'),
                'privateKey' => get_option('aqop_push_vapid_private_key'),
            ),
        );

        if (empty($auth['VAPID']['publicKey']) || empty($auth['VAPID']['privateKey'])) {
            return false;
        }

        // Initialize WebPush
        // Note: Requires 'minishlink/web-push' to be autoloaded via Composer
        if (!class_exists('Minishlink\WebPush\WebPush')) {
            error_log('WebPush library not found. Please run composer install.');
            return false;
        }

        $webPush = new WebPush($auth);

        $payload = json_encode(array(
            'title' => $title,
            'body' => $body,
            'icon' => '/icon-192x192.png', // Should be configurable
            'badge' => '/badge-72x72.png',
            'data' => $data,
        ));

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create(array(
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh_key,
                'authToken' => $sub->auth_key,
            ));

            $webPush->queueNotification($subscription, $payload);
        }

        // Send all
        $results = $webPush->flush();

        // Handle expired subscriptions
        foreach ($results as $result) {
            if (!$result->isSuccess() && $result->isSubscriptionExpired()) {
                $endpoint = $result->getEndpoint();
                $wpdb->update(
                    $wpdb->prefix . 'aq_push_subscriptions',
                    array('is_active' => 0),
                    array('endpoint' => $endpoint),
                    array('%d'),
                    array('%s')
                );
            }
        }

        return true;
    }

    /**
     * Generate VAPID keys.
     *
     * @since  1.1.0
     * @static
     * @return array Keys array.
     */
    public static function generate_vapid_keys()
    {
        if (!class_exists('Minishlink\WebPush\VAPID')) {
            return false;
        }

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        update_option('aqop_push_vapid_public_key', $keys['publicKey']);
        update_option('aqop_push_vapid_private_key', $keys['privateKey']);

        return $keys;
    }

    /**
     * Save subscription.
     *
     * @since  1.1.0
     * @static
     * @param  int   $user_id User ID.
     * @param  array $sub_data Subscription data from frontend.
     * @return int|false ID on success.
     */
    public static function save_subscription($user_id, $sub_data)
    {
        global $wpdb;

        $endpoint = isset($sub_data['endpoint']) ? $sub_data['endpoint'] : '';
        $keys = isset($sub_data['keys']) ? $sub_data['keys'] : array();

        if (empty($endpoint) || empty($keys['auth']) || empty($keys['p256dh'])) {
            return false;
        }

        // Check if exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_push_subscriptions WHERE endpoint = %s",
                $endpoint
            )
        );

        if ($exists) {
            $wpdb->update(
                $wpdb->prefix . 'aq_push_subscriptions',
                array(
                    'user_id' => $user_id,
                    'is_active' => 1,
                    'last_used_at' => current_time('mysql'),
                ),
                array('id' => $exists),
                array('%d', '%d', '%s'),
                array('%d')
            );
            return $exists;
        }

        $wpdb->insert(
            $wpdb->prefix . 'aq_push_subscriptions',
            array(
                'user_id' => $user_id,
                'endpoint' => $endpoint,
                'auth_key' => $keys['auth'],
                'p256dh_key' => $keys['p256dh'],
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );

        return $wpdb->insert_id;
    }
}
