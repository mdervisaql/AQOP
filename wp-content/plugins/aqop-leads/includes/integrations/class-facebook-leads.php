<?php
/**
 * Facebook Leads Integration Class
 *
 * Handles Facebook Graph API interactions, OAuth flow, and Webhook processing.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Facebook_Leads class.
 *
 * @since 1.0.0
 */
class AQOP_Facebook_Leads
{

    /**
     * Facebook Graph API URL.
     *
     * @var string
     */
    private $graph_url = 'https://graph.facebook.com/v18.0';

    /**
     * Encryption key for tokens.
     *
     * @var string
     */
    private $encryption_key;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->encryption_key = defined('AUTH_KEY') ? AUTH_KEY : 'aqop_default_secret_key';
    }

    /**
     * Get Facebook Lead Ads Source ID.
     *
     * Gets or creates the source ID for "Facebook Lead Ads".
     *
     * @since  1.0.0
     * @return int Source ID.
     */
    public function get_facebook_source_id()
    {
        global $wpdb;

        // Check if source exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $source_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_leads_sources WHERE source_code = %s",
                'facebook_lead_ads'
            )
        );

        if ($source_id) {
            return (int) $source_id;
        }

        // Create source if not exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            "{$wpdb->prefix}aq_leads_sources",
            array(
                'source_code' => 'facebook_lead_ads',
                'source_name' => 'Facebook Lead Ads',
                'source_type' => 'paid',
                'cost_per_lead' => 0.00,
                'is_active' => 1,
            ),
            array('%s', '%s', '%s', '%f', '%d')
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * Get OAuth Login URL.
     *
     * Generates the Facebook Login URL for the user to authorize the app.
     *
     * @since  1.0.0
     * @param  string $redirect_uri Callback URL.
     * @return string Login URL.
     */
    public function get_oauth_url($redirect_uri)
    {
        $app_id = get_option('aqop_fb_app_id');

        if (!$app_id) {
            return new WP_Error('missing_config', __('Facebook App ID is not configured.', 'aqop-leads'));
        }

        $params = array(
            'client_id' => $app_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'leads_retrieval,pages_manage_ads,pages_read_engagement,pages_show_list',
            'response_type' => 'code',
            'state' => wp_create_nonce('aqop_fb_oauth'),
        );

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Exchange Code for Token.
     *
     * Exchanges the authorization code for a long-lived user access token.
     *
     * @since  1.0.0
     * @param  string $code         Authorization code.
     * @param  string $redirect_uri Callback URL.
     * @return array|WP_Error Token data or error.
     */
    public function exchange_code_for_token($code, $redirect_uri)
    {
        $app_id = get_option('aqop_fb_app_id');
        $app_secret = get_option('aqop_fb_app_secret');

        if (!$app_id || !$app_secret) {
            return new WP_Error('missing_config', __('Facebook App configuration is missing.', 'aqop-leads'));
        }

        // 1. Get short-lived token.
        $url = $this->graph_url . '/oauth/access_token';
        $params = array(
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code,
        );

        $response = wp_remote_get($url . '?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        $short_token = $body['access_token'];

        // 2. Exchange for long-lived token.
        $params = array(
            'grant_type' => 'fb_exchange_token',
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'fb_exchange_token' => $short_token,
        );

        $response = wp_remote_get($url . '?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return array(
            'access_token' => $body['access_token'],
            'expires_in' => isset($body['expires_in']) ? $body['expires_in'] : null,
        );
    }

    /**
     * Get User Profile.
     *
     * Fetches the user's Facebook profile.
     *
     * @since  1.0.0
     * @param  string $access_token User access token.
     * @return array|WP_Error User data or error.
     */
    public function get_user_profile($access_token)
    {
        $url = $this->graph_url . '/me?fields=id,name';
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return $body;
    }

    /**
     * Get Ad Accounts.
     *
     * Fetches the user's ad accounts.
     *
     * @since  1.0.0
     * @param  string $access_token User access token.
     * @return array|WP_Error Ad accounts or error.
     */
    public function get_ad_accounts($access_token)
    {
        $url = $this->graph_url . '/me/adaccounts?fields=id,name,account_id,currency';
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return isset($body['data']) ? $body['data'] : array();
    }

    /**
     * Get Pages.
     *
     * Fetches pages accessible by the user.
     *
     * @since  1.0.0
     * @param  string $access_token User access token.
     * @return array|WP_Error Pages or error.
     */
    public function get_pages($access_token)
    {
        $url = $this->graph_url . '/me/accounts?fields=id,name,access_token,tasks';
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return isset($body['data']) ? $body['data'] : array();
    }

    /**
     * Get Lead Forms.
     *
     * Fetches lead forms for a specific page.
     *
     * @since  1.0.0
     * @param  string $access_token Page access token (preferred) or User access token.
     * @param  string $page_id      Page ID.
     * @return array|WP_Error Forms or error.
     */
    public function get_lead_forms($access_token, $page_id)
    {
        $url = $this->graph_url . "/{$page_id}/leadgen_forms?fields=id,name,status,leads_count,expired_leads_count";
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return isset($body['data']) ? $body['data'] : array();
    }

    /**
     * Get Form Fields.
     *
     * Fetches questions/fields for a specific form.
     *
     * @since  1.0.0
     * @param  string $access_token Access token.
     * @param  string $form_id      Form ID.
     * @return array|WP_Error Fields or error.
     */
    public function get_form_fields($access_token, $form_id)
    {
        $url = $this->graph_url . "/{$form_id}?fields=questions,context_card";
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return isset($body['questions']) ? $body['questions'] : array();
    }

    /**
     * Subscribe to Webhooks.
     *
     * Subscribes the page to the app's webhooks.
     *
     * @since  1.0.0
     * @param  string $page_access_token Page access token.
     * @param  string $page_id           Page ID.
     * @return bool|WP_Error True on success.
     */
    public function subscribe_to_webhooks($page_access_token, $page_id)
    {
        $url = $this->graph_url . "/{$page_id}/subscribed_apps";
        $params = array(
            'subscribed_fields' => 'leadgen',
            'access_token' => $page_access_token,
        );

        $response = wp_remote_post($url, array(
            'body' => $params,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('fb_api_error', $body['error']['message']);
        }

        return isset($body['success']) && $body['success'];
    }

    /**
     * Verify Webhook.
     *
     * Verifies the webhook challenge from Facebook.
     *
     * @since  1.0.0
     * @param  string $hub_mode         Hub mode (subscribe).
     * @param  string $hub_verify_token Verify token.
     * @param  string $hub_challenge    Challenge string.
     * @return string|WP_Error Challenge string or error.
     */
    public function verify_webhook($hub_mode, $hub_verify_token, $hub_challenge)
    {
        $verify_token = get_option('aqop_fb_verify_token');

        if ($hub_mode === 'subscribe' && $hub_verify_token === $verify_token) {
            return $hub_challenge;
        }

        return new WP_Error('verification_failed', 'Invalid verification token.');
    }

    /**
     * Handle Webhook.
     *
     * Processes incoming webhook payload.
     *
     * @since  1.0.0
     * @param  array $payload Webhook payload.
     * @return void
     */
    public function handle_webhook($payload)
    {
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            return;
        }

        foreach ($payload['entry'] as $entry) {
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'leadgen') {
                    continue;
                }

                $value = $change['value'];
                $form_id = $value['form_id'];
                $leadgen_id = $value['leadgen_id'];
                $page_id = $value['page_id'];

                // Log receipt.
                $this->log_lead($form_id, $leadgen_id, $value, 'received');

                // Process lead.
                $this->process_lead($form_id, $leadgen_id, $page_id);
            }
        }
    }

    /**
     * Process Lead.
     *
     * Fetches lead details and creates a lead in AQOP.
     *
     * @since  1.0.0
     * @param  string $form_id    Form ID.
     * @param  string $leadgen_id Lead ID.
     * @param  string $page_id    Page ID.
     * @return void
     */
    private function process_lead($form_id, $leadgen_id, $page_id)
    {
        global $wpdb;

        // 1. Get connection and token.
        // We need a valid token to fetch lead details. We can use the connection associated with this page/form.
        // For simplicity, we'll look for any active connection that has access to this page.
        // In a real scenario, we should store which connection manages which page.
        // Here we assume the connection that mapped the form is the one to use.

        $form_mapping = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_facebook_forms WHERE fb_form_id = %s AND is_active = 1",
                $form_id
            )
        );

        if (!$form_mapping) {
            $this->log_lead($form_id, $leadgen_id, null, 'failed', 'Form not mapped or inactive');
            return;
        }

        $connection = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_facebook_connections WHERE id = %d",
                $form_mapping->connection_id
            )
        );

        if (!$connection) {
            $this->log_lead($form_id, $leadgen_id, null, 'failed', 'Connection not found');
            return;
        }

        $access_token = $this->decrypt_token($connection->access_token);

        // 2. Fetch lead details from Facebook.
        $url = $this->graph_url . "/{$leadgen_id}";
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            $this->log_lead($form_id, $leadgen_id, null, 'failed', $response->get_error_message());
            return;
        }

        $lead_data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($lead_data['error'])) {
            $this->log_lead($form_id, $leadgen_id, null, 'failed', $lead_data['error']['message']);
            return;
        }

        // 3. Map fields.
        $mapped_data = $this->map_lead_data($form_mapping->id, $lead_data['field_data']);

        // 4. Create Lead.
        $lead_id = $this->create_lead($mapped_data, $form_mapping, $lead_data);

        if (is_wp_error($lead_id)) {
            $this->log_lead($form_id, $leadgen_id, $lead_data, 'failed', $lead_id->get_error_message());
        } else {
            $this->log_lead($form_id, $leadgen_id, $lead_data, 'processed', null, $lead_id);

            // Update form stats.
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}aq_facebook_forms SET leads_count = leads_count + 1, last_lead_at = %s WHERE id = %d",
                    current_time('mysql'),
                    $form_mapping->id
                )
            );
        }
    }

    /**
     * Map Lead Data.
     *
     * Maps Facebook field data to AQOP fields based on configuration.
     *
     * @since  1.0.0
     * @param  int   $form_mapping_id Form mapping ID.
     * @param  array $field_data      Facebook field data.
     * @return array Mapped data.
     */
    private function map_lead_data($form_mapping_id, $field_data)
    {
        global $wpdb;

        $mappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_facebook_field_mappings WHERE form_id = %d AND is_active = 1",
                $form_mapping_id
            )
        );

        $mapped = array(
            'core' => array(),
            'questions' => array(),
        );

        // Convert field data to key-value pair for easier lookup.
        $fb_values = array();
        foreach ($field_data as $field) {
            $fb_values[$field['name']] = $field['values'][0];
        }

        foreach ($mappings as $mapping) {
            if (!isset($fb_values[$mapping->fb_field_name])) {
                continue;
            }

            $value = $fb_values[$mapping->fb_field_name];

            if ($mapping->wp_field) {
                $mapped['core'][$mapping->wp_field] = $value;
            } elseif ($mapping->question_id) {
                $mapped['questions'][$mapping->question_id] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Create Lead.
     *
     * Inserts the lead into the database.
     *
     * @since  1.0.0
     * @param  array  $mapped_data  Mapped data.
     * @param  object $form_mapping Form mapping object.
     * @param  array  $raw_data     Raw Facebook data.
     * @return int|WP_Error Lead ID or error.
     */
    private function create_lead($mapped_data, $form_mapping, $raw_data)
    {
        global $wpdb;

        $core_data = $mapped_data['core'];

        // Ensure required fields.
        if (empty($core_data['name'])) {
            // Try to construct name from full_name or first_name + last_name if available in raw data but not mapped?
            // For now, if name is missing, use a fallback.
            $core_data['name'] = 'Facebook Lead #' . $raw_data['id'];
        }

        // Set metadata.
        $core_data['source_id'] = $this->get_facebook_source_id();
        $core_data['campaign_id'] = $this->get_or_create_campaign($form_mapping);
        $core_data['created_at'] = current_time('mysql');
        $core_data['status_id'] = 1; // Pending

        // Format custom fields (questions) as JSON for the 'custom_fields' column in aq_leads table
        // This is a simple storage for now.
        if (!empty($mapped_data['questions'])) {
            // We might want to fetch question text to store as key-value for readability
            $questions_json = array();
            foreach ($mapped_data['questions'] as $q_id => $ans) {
                // Fetch question key/text
                $q = $wpdb->get_row($wpdb->prepare("SELECT question_key, question_text_en FROM {$wpdb->prefix}aq_questions_library WHERE id = %d", $q_id));
                if ($q) {
                    $questions_json[$q->question_key] = array(
                        'question' => $q->question_text_en,
                        'answer' => $ans
                    );
                } else {
                    $questions_json["q_{$q_id}"] = $ans;
                }
            }
            $core_data['custom_fields'] = json_encode($questions_json, JSON_UNESCAPED_UNICODE);
        }

        // Insert lead.
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}aq_leads",
            $core_data
        );

        if (!$inserted) {
            return new WP_Error('db_insert_error', $wpdb->last_error);
        }

        $lead_id = $wpdb->insert_id;

        return $lead_id;
    }

    /**
     * Get or Create Campaign.
     *
     * Ensures a campaign exists for the form.
     *
     * @since  1.0.0
     * @param  object $form_mapping Form mapping object.
     * @return int Campaign ID.
     */
    private function get_or_create_campaign($form_mapping)
    {
        global $wpdb;

        if ($form_mapping->campaign_id) {
            return $form_mapping->campaign_id;
        }

        // Check if campaign exists by name (Form Name).
        $campaign_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_leads_campaigns WHERE name = %s",
                $form_mapping->fb_form_name
            )
        );

        if ($campaign_id) {
            // Update mapping.
            $wpdb->update(
                "{$wpdb->prefix}aq_facebook_forms",
                array('campaign_id' => $campaign_id),
                array('id' => $form_mapping->id),
                array('%d'),
                array('%d')
            );
            return $campaign_id;
        }

        // Create new campaign.
        $wpdb->insert(
            "{$wpdb->prefix}aq_leads_campaigns",
            array(
                'name' => $form_mapping->fb_form_name,
                'group_id' => $form_mapping->campaign_group_id,
                'platform' => 'facebook',
                'is_active' => 1,
                'created_at' => current_time('mysql'),
            )
        );

        $new_campaign_id = $wpdb->insert_id;

        // Update mapping.
        $wpdb->update(
            "{$wpdb->prefix}aq_facebook_forms",
            array('campaign_id' => $new_campaign_id),
            array('id' => $form_mapping->id),
            array('%d'),
            array('%d')
        );

        return $new_campaign_id;
    }

    /**
     * Log Lead.
     *
     * Logs the lead reception and processing status.
     *
     * @since  1.0.0
     * @param  string $form_id       Form ID.
     * @param  string $leadgen_id    Lead ID.
     * @param  array  $raw_data      Raw data.
     * @param  string $status        Status.
     * @param  string $error_message Error message.
     * @param  int    $lead_id       Created lead ID.
     * @return void
     */
    private function log_lead($form_id, $leadgen_id, $raw_data, $status, $error_message = null, $lead_id = null)
    {
        global $wpdb;

        $wpdb->insert(
            "{$wpdb->prefix}aq_facebook_leads_log",
            array(
                'fb_lead_id' => $leadgen_id,
                'lead_id' => $lead_id,
                'raw_data' => $raw_data ? json_encode($raw_data) : null,
                'status' => $status,
                'error_message' => $error_message,
                'created_at' => current_time('mysql'),
            )
        );
    }

    /**
     * Encrypt Token.
     *
     * @since  1.0.0
     * @param  string $token Token.
     * @return string Encrypted token.
     */
    public function encrypt_token($token)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($token, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt Token.
     *
     * @since  1.0.0
     * @param  string $encrypted_token Encrypted token.
     * @return string Decrypted token.
     */
    public function decrypt_token($encrypted_token)
    {
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_token), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->encryption_key, 0, $iv);
    }
}
