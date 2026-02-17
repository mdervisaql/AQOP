<?php
/**
 * Notifications Admin Class
 *
 * Handles the admin interface for the notification system.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Notifications_Admin class.
 *
 * @since 1.1.0
 */
class AQOP_Notifications_Admin
{

    /**
     * Initialize the class.
     *
     * @since 1.1.0
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'register_admin_pages'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_aqop_save_notification_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_aqop_delete_notification_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_aqop_generate_vapid_keys', array($this, 'ajax_generate_vapid_keys'));
    }

    /**
     * Register admin pages.
     *
     * @since 1.1.0
     */
    public function register_admin_pages()
    {
        add_submenu_page(
            'aqop-control-center',
            __('Notifications', 'aqop-leads'),
            __('Notifications', 'aqop-leads'),
            'manage_options',
            'aqop-notifications',
            array($this, 'render_management_page')
        );
    }

    /**
     * Enqueue styles.
     *
     * @since 1.1.0
     */
    public function enqueue_styles()
    {
        // Use core styles
    }

    /**
     * Enqueue scripts.
     *
     * @since 1.1.0
     */
    public function enqueue_scripts()
    {
        // Enqueue custom script for notification management if needed
    }

    /**
     * Render management page.
     *
     * @since 1.1.0
     */
    public function render_management_page()
    {
        // Fetch data for the view
        global $wpdb;

        // Fetch Templates
        $templates = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_notification_templates ORDER BY created_at DESC");

        // Fetch Push Subscriptions Count
        $push_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_push_subscriptions WHERE is_active = 1");

        // Fetch VAPID Keys status
        $vapid_public = get_option('aqop_push_vapid_public_key');
        $has_vapid = !empty($vapid_public);

        include AQOP_LEADS_PLUGIN_DIR . 'admin/views/notifications-management.php';
    }

    /**
     * AJAX: Save Template.
     *
     * @since 1.1.0
     */
    public function ajax_save_template()
    {
        check_ajax_referer('aqop_notifications_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'title_template' => sanitize_text_field($_POST['title_template']),
            'message_template' => sanitize_textarea_field($_POST['message_template']),
            'notification_channels' => json_encode(array_map('sanitize_text_field', $_POST['channels'] ?? [])),
            'target_roles' => json_encode(array_map('sanitize_text_field', $_POST['target_roles'] ?? [])),
            'priority' => sanitize_text_field($_POST['priority']),
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'push_enabled' => isset($_POST['push_enabled']) ? 1 : 0,
        );

        if ($id > 0) {
            $wpdb->update($wpdb->prefix . 'aq_notification_templates', $data, array('id' => $id));
        } else {
            $wpdb->insert($wpdb->prefix . 'aq_notification_templates', $data);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Delete Template.
     *
     * @since 1.1.0
     */
    public function ajax_delete_template()
    {
        check_ajax_referer('aqop_notifications_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $id = absint($_POST['id']);

        $wpdb->delete($wpdb->prefix . 'aq_notification_templates', array('id' => $id));

        wp_send_json_success();
    }

    /**
     * AJAX: Generate VAPID Keys.
     *
     * @since 1.1.0
     */
    public function ajax_generate_vapid_keys()
    {
        check_ajax_referer('aqop_notifications_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!class_exists('AQOP_Push_Notification_Manager')) {
            wp_send_json_error('Push Manager not loaded');
        }

        $keys = AQOP_Push_Notification_Manager::generate_vapid_keys();

        if ($keys) {
            wp_send_json_success($keys);
        } else {
            wp_send_json_error('Failed to generate keys');
        }
    }
}
