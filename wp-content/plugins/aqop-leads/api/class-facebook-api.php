<?php
/**
 * Facebook Leads API Class
 *
 * Defines REST API endpoints for Facebook Leads integration.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Facebook_API class.
 *
 * @since 1.0.0
 */
class AQOP_Facebook_API extends WP_REST_Controller
{

    /**
     * Integration instance.
     *
     * @var AQOP_Facebook_Leads
     */
    private $integration;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->namespace = 'aqop/v1';
        $this->rest_base = 'facebook';

        require_once AQOP_LEADS_PLUGIN_DIR . 'includes/integrations/class-facebook-leads.php';
        $this->integration = new AQOP_Facebook_Leads();
    }

    /**
     * Register routes.
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        // OAuth URL
        register_rest_route($this->namespace, '/' . $this->rest_base . '/oauth-url', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_oauth_url'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // OAuth Callback
        register_rest_route($this->namespace, '/' . $this->rest_base . '/oauth-callback', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_oauth_callback'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Get Connection
        register_rest_route($this->namespace, '/' . $this->rest_base . '/connection', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_connection'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Disconnect
        register_rest_route($this->namespace, '/' . $this->rest_base . '/disconnect', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'disconnect'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Get Ad Accounts
        register_rest_route($this->namespace, '/' . $this->rest_base . '/ad-accounts', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_ad_accounts'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Get Pages
        register_rest_route($this->namespace, '/' . $this->rest_base . '/pages', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_pages'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Get Forms
        register_rest_route($this->namespace, '/' . $this->rest_base . '/forms', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_forms'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Get Form Fields
        register_rest_route($this->namespace, '/' . $this->rest_base . '/forms/(?P<id>\d+)/fields', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_form_fields'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Save Mapping
        register_rest_route($this->namespace, '/' . $this->rest_base . '/forms/(?P<id>\d+)/map', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'save_mapping'),
            'permission_callback' => array($this, 'check_permission'),
        ));

        // Webhook (GET - Verification)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'verify_webhook'),
            'permission_callback' => '__return_true', // Public endpoint
        ));

        // Webhook (POST - Receive Leads)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Public endpoint
        ));
    }

    /**
     * Check permission.
     *
     * @since 1.0.0
     */
    public function check_permission()
    {
        return current_user_can('manage_facebook_leads') || current_user_can('manage_options');
    }

    /**
     * Get OAuth URL.
     */
    public function get_oauth_url($request)
    {
        $redirect_uri = $request->get_param('redirect_uri');
        if (!$redirect_uri) {
            return new WP_Error('missing_param', 'redirect_uri is required', array('status' => 400));
        }

        $url = $this->integration->get_oauth_url($redirect_uri);

        if (is_wp_error($url)) {
            return $url;
        }

        return rest_ensure_response(array('url' => $url));
    }

    /**
     * Handle OAuth Callback.
     */
    public function handle_oauth_callback($request)
    {
        global $wpdb;

        $code = $request->get_param('code');
        $redirect_uri = $request->get_param('redirect_uri');

        if (!$code || !$redirect_uri) {
            return new WP_Error('missing_param', 'code and redirect_uri are required', array('status' => 400));
        }

        $token_data = $this->integration->exchange_code_for_token($code, $redirect_uri);

        if (is_wp_error($token_data)) {
            return $token_data;
        }

        $access_token = $token_data['access_token'];
        $user_profile = $this->integration->get_user_profile($access_token);

        if (is_wp_error($user_profile)) {
            return $user_profile;
        }

        // Save connection.
        $user_id = get_current_user_id();
        $encrypted_token = $this->integration->encrypt_token($access_token);
        $expires_at = isset($token_data['expires_in']) ? date('Y-m-d H:i:s', time() + $token_data['expires_in']) : null;

        // Check existing connection for this FB user.
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}aq_facebook_connections WHERE fb_user_id = %s", $user_profile['id']));

        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}aq_facebook_connections",
                array(
                    'user_id' => $user_id,
                    'fb_user_name' => $user_profile['name'],
                    'access_token' => $encrypted_token,
                    'token_expires_at' => $expires_at,
                    'status' => 'active',
                ),
                array('id' => $existing->id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}aq_facebook_connections",
                array(
                    'user_id' => $user_id,
                    'fb_user_id' => $user_profile['id'],
                    'fb_user_name' => $user_profile['name'],
                    'access_token' => $encrypted_token,
                    'token_expires_at' => $expires_at,
                    'status' => 'active',
                )
            );
        }

        return rest_ensure_response(array('success' => true, 'user' => $user_profile));
    }

    /**
     * Get Connection.
     */
    public function get_connection()
    {
        global $wpdb;
        $user_id = get_current_user_id();

        // For now, return the first active connection. In future, might scope to user.
        $connection = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aq_facebook_connections WHERE status = 'active' LIMIT 1");

        if (!$connection) {
            return rest_ensure_response(array('connected' => false));
        }

        return rest_ensure_response(array(
            'connected' => true,
            'fb_user_name' => $connection->fb_user_name,
            'ad_account_name' => $connection->ad_account_name,
            'page_name' => $connection->page_name,
        ));
    }

    /**
     * Disconnect.
     */
    public function disconnect()
    {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}aq_facebook_connections",
            array('status' => 'disconnected'),
            array('status' => 'active')
        );
        return rest_ensure_response(array('success' => true));
    }

    /**
     * Get Ad Accounts.
     */
    public function get_ad_accounts()
    {
        global $wpdb;
        $connection = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aq_facebook_connections WHERE status = 'active' LIMIT 1");

        if (!$connection) {
            return new WP_Error('no_connection', 'No active Facebook connection found.', array('status' => 404));
        }

        $access_token = $this->integration->decrypt_token($connection->access_token);
        $accounts = $this->integration->get_ad_accounts($access_token);

        if (is_wp_error($accounts)) {
            return $accounts;
        }

        return rest_ensure_response($accounts);
    }

    /**
     * Get Pages.
     */
    public function get_pages()
    {
        global $wpdb;
        $connection = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}aq_facebook_connections WHERE status = 'active' LIMIT 1");

        if (!$connection) {
            return new WP_Error('no_connection', 'No active Facebook connection found.', array('status' => 404));
        }

        $access_token = $this->integration->decrypt_token($connection->access_token);
        $pages = $this->integration->get_pages($access_token);

        if (is_wp_error($pages)) {
            return $pages;
        }

        return rest_ensure_response($pages);
    }

    /**
     * Get Forms.
     */
    public function get_forms($request)
    {
        global $wpdb;
        $page_id = $request->get_param('page_id');
        $page_access_token = $request->get_param('page_access_token'); // Passed from frontend for simplicity, or could store in DB

        if (!$page_id || !$page_access_token) {
            return new WP_Error('missing_param', 'page_id and page_access_token are required', array('status' => 400));
        }

        $forms = $this->integration->get_lead_forms($page_access_token, $page_id);

        if (is_wp_error($forms)) {
            return $forms;
        }

        // Merge with local mapping status
        foreach ($forms as &$form) {
            $mapping = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_facebook_forms WHERE fb_form_id = %s", $form['id']));
            $form['is_mapped'] = !!$mapping;
            $form['local_status'] = $mapping ? ($mapping->is_active ? 'active' : 'inactive') : 'unmapped';
            $form['campaign_group_id'] = $mapping ? $mapping->campaign_group_id : null;
        }

        return rest_ensure_response($forms);
    }

    /**
     * Get Form Fields.
     */
    public function get_form_fields($request)
    {
        global $wpdb;
        $form_id = $request->get_param('id'); // This is FB form ID
        $access_token = $request->get_param('access_token'); // Page access token

        if (!$access_token) {
            return new WP_Error('missing_param', 'access_token is required', array('status' => 400));
        }

        $fields = $this->integration->get_form_fields($access_token, $form_id);

        if (is_wp_error($fields)) {
            return $fields;
        }

        // Get existing mappings if any
        $local_form = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}aq_facebook_forms WHERE fb_form_id = %s", $form_id));
        $existing_mappings = array();
        if ($local_form) {
            $existing_mappings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_facebook_field_mappings WHERE form_id = %d", $local_form->id), OBJECT_K);
        }

        // Format response
        $response_fields = array();
        foreach ($fields as $field) {
            $mapped_to = null;
            // Logic to find existing mapping...
            // Simplified for now
            $response_fields[] = array(
                'name' => $field['key'], // FB uses 'key' for field name usually, or 'label'
                'label' => $field['label'],
                'type' => $field['type'],
            );
        }

        return rest_ensure_response($response_fields);
    }

    /**
     * Save Mapping.
     */
    public function save_mapping($request)
    {
        global $wpdb;
        $fb_form_id = $request->get_param('id');
        $params = $request->get_json_params();

        $fb_form_name = $params['form_name'];
        $page_id = $params['page_id'];
        $campaign_group_id = $params['campaign_group_id'];
        $mappings = $params['mappings']; // Array of {fb_field, wp_field, question_id}

        // 1. Ensure connection exists (get active one)
        $connection = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}aq_facebook_connections WHERE status = 'active' LIMIT 1");
        if (!$connection) {
            return new WP_Error('no_connection', 'No active connection');
        }

        // 2. Save/Update Form
        $existing_form = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}aq_facebook_forms WHERE fb_form_id = %s", $fb_form_id));

        if ($existing_form) {
            $form_id = $existing_form->id;
            $wpdb->update(
                "{$wpdb->prefix}aq_facebook_forms",
                array(
                    'fb_form_name' => $fb_form_name,
                    'campaign_group_id' => $campaign_group_id,
                    'is_active' => 1,
                ),
                array('id' => $form_id)
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}aq_facebook_forms",
                array(
                    'connection_id' => $connection->id,
                    'fb_form_id' => $fb_form_id,
                    'fb_form_name' => $fb_form_name,
                    'campaign_group_id' => $campaign_group_id,
                    'is_active' => 1,
                )
            );
            $form_id = $wpdb->insert_id;
        }

        // 3. Save Mappings
        // Clear existing
        $wpdb->delete("{$wpdb->prefix}aq_facebook_field_mappings", array('form_id' => $form_id));

        foreach ($mappings as $map) {
            $wpdb->insert(
                "{$wpdb->prefix}aq_facebook_field_mappings",
                array(
                    'form_id' => $form_id,
                    'fb_field_name' => $map['fb_field'],
                    'fb_field_label' => $map['fb_label'],
                    'wp_field' => isset($map['wp_field']) ? $map['wp_field'] : null,
                    'question_id' => isset($map['question_id']) ? $map['question_id'] : null,
                )
            );
        }

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Verify Webhook.
     */
    public function verify_webhook($request)
    {
        $params = $request->get_query_params();
        $mode = isset($params['hub_mode']) ? $params['hub_mode'] : null;
        $token = isset($params['hub_verify_token']) ? $params['hub_verify_token'] : null;
        $challenge = isset($params['hub_challenge']) ? $params['hub_challenge'] : null;

        $result = $this->integration->verify_webhook($mode, $token, $challenge);

        if (is_wp_error($result)) {
            return $result;
        }

        echo $result;
        exit;
    }

    /**
     * Handle Webhook.
     */
    public function handle_webhook($request)
    {
        $payload = $request->get_json_params();
        $this->integration->handle_webhook($payload);
        return rest_ensure_response(array('success' => true));
    }
}
