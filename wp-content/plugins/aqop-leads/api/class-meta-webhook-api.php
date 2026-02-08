<?php
/**
 * Meta Lead Ads Webhook API
 *
 * Handles incoming leads from Meta (Facebook) Lead Ads.
 * Processes webhook verification, lead parsing, and database insertion.
 *
 * @package AQOP_Leads
 * @subpackage API
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Meta_Webhook_API {

    /**
     * Namespace for the API endpoints
     *
     * @var string
     */
    private $namespace = 'aqop/v1';

    /**
     * Webhook endpoint base
     *
     * @var string
     */
    private $webhook_endpoint = 'meta/webhook';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->webhook_endpoint,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'verify_webhook'),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'hub_mode'         => array(
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'hub_challenge'    => array(
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'hub_verify_token' => array(
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'handle_webhook'),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }

    /**
     * Verify Meta webhook subscription
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function verify_webhook($request) {
        $this->log_webhook('verification_request', array(
            'hub_mode' => $request->get_param('hub_mode'),
            'hub_challenge' => $request->get_param('hub_challenge'),
            'hub_verify_token' => substr($request->get_param('hub_verify_token'), 0, 10) . '...'
        ));

        $hub_mode = $request->get_param('hub_mode');
        $hub_challenge = $request->get_param('hub_challenge');
        $hub_verify_token = $request->get_param('hub_verify_token');

        // Get stored verify token
        $stored_verify_token = get_option('aqop_meta_verify_token', '');

        if ($hub_mode === 'subscribe' && $hub_verify_token === $stored_verify_token) {
            $this->log_webhook('verification_success', array('challenge' => $hub_challenge));
            return new WP_REST_Response($hub_challenge, 200);
        }

        $this->log_webhook('verification_failed', array(
            'reason' => 'Invalid token or mode',
            'received_token' => substr($hub_verify_token, 0, 10) . '...',
            'stored_token' => substr($stored_verify_token, 0, 10) . '...'
        ));

        return new WP_Error('verification_failed', 'Invalid verification token', array('status' => 403));
    }

    /**
     * Handle incoming Meta webhook
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_webhook($request) {
        try {
            // Verify signature if app secret is configured
            $signature_verified = $this->verify_signature($request);
            if (!$signature_verified) {
                $this->log_webhook('signature_verification_failed', array(
                    'received_signature' => $request->get_header('x-hub-signature-256') ?: $request->get_header('x-hub-signature')
                ));
                return new WP_Error('signature_verification_failed', 'Invalid signature', array('status' => 403));
            }

            $body = $request->get_body();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log_webhook('json_parse_error', array('error' => json_last_error_msg(), 'body' => substr($body, 0, 500)));
                return new WP_Error('json_parse_error', 'Invalid JSON payload', array('status' => 400));
            }

            $this->log_webhook('webhook_received', array(
                'entry_count' => count($data['entry'] ?? array()),
                'object' => $data['object'] ?? 'unknown'
            ));

            // Process entries
            $processed_leads = 0;
            if (isset($data['entry']) && is_array($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    $leads_processed = $this->process_entry($entry);
                    $processed_leads += $leads_processed;
                }
            }

            $this->log_webhook('webhook_processed', array('leads_created' => $processed_leads));

            return new WP_REST_Response(array(
                'success' => true,
                'message' => "Processed $processed_leads leads",
                'leads_created' => $processed_leads
            ), 200);

        } catch (Exception $e) {
            $this->log_webhook('webhook_error', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            return new WP_Error('webhook_processing_error', 'Error processing webhook: ' . $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Verify webhook signature
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    private function verify_signature($request) {
        $app_secret = get_option('aqop_meta_app_secret', '');
        if (empty($app_secret)) {
            // If no app secret configured, skip verification
            return true;
        }

        $signature = $request->get_header('x-hub-signature-256');
        if (!$signature) {
            // Fallback to older signature format
            $signature = $request->get_header('x-hub-signature');
        }

        if (!$signature) {
            return false;
        }

        $body = $request->get_body();
        $expected_signature = 'sha256=' . hash_hmac('sha256', $body, $app_secret);

        return hash_equals($expected_signature, $signature);
    }

    /**
     * Process a single entry from the webhook
     *
     * @param array $entry
     * @return int Number of leads processed
     */
    private function process_entry($entry) {
        $leads_processed = 0;

        if (!isset($entry['changes']) || !is_array($entry['changes'])) {
            return $leads_processed;
        }

        foreach ($entry['changes'] as $change) {
            if (isset($change['field']) && $change['field'] === 'leadgen' && isset($change['value'])) {
                $lead_data = $this->map_meta_lead_to_aqop($change['value']);
                if ($lead_data) {
                    $this->create_aqop_lead($lead_data);
                    $leads_processed++;
                }
            }
        }

        return $leads_processed;
    }

    /**
     * Map Meta lead data to AQOP lead format
     *
     * @param array $meta_lead
     * @return array|null
     */
    private function map_meta_lead_to_aqop($meta_lead) {
        try {
            $lead_data = array(
                'source' => 'facebook',
                'status_code' => 'new',
                'assigned_to' => null,
                'custom_fields' => array(),
                'notes' => array(),
                'meta_data' => array(
                    'meta_leadgen_id' => $meta_lead['leadgen_id'] ?? '',
                    'meta_form_id' => $meta_lead['form_id'] ?? '',
                    'meta_page_id' => $meta_lead['page_id'] ?? '',
                    'meta_adgroup_id' => $meta_lead['adgroup_id'] ?? '',
                    'meta_created_time' => $meta_lead['created_time'] ?? time(),
                )
            );

            // Process field data
            if (isset($meta_lead['field_data']) && is_array($meta_lead['field_data'])) {
                foreach ($meta_lead['field_data'] as $field) {
                    if (!isset($field['name']) || !isset($field['values']) || !is_array($field['values'])) {
                        continue;
                    }

                    $field_name = $field['name'];
                    $field_value = $field['values'][0] ?? ''; // Take first value

                    // Map standard fields
                    switch ($field_name) {
                        case 'full_name':
                        case 'name':
                            $lead_data['name'] = $field_value;
                            break;
                        case 'email':
                            $lead_data['email'] = $field_value;
                            break;
                        case 'phone_number':
                        case 'phone':
                            $lead_data['phone'] = $field_value;
                            break;
                        case 'city':
                            $lead_data['city'] = $field_value;
                            break;
                        case 'country':
                            $lead_data['country'] = $field_value;
                            break;
                        case 'message':
                        case 'comment':
                            $lead_data['message'] = $field_value;
                            break;
                        default:
                            // Handle custom questions
                            if (strpos($field_name, 'custom_question_') === 0 ||
                                strpos($field_name, 'question_') === 0 ||
                                preg_match('/^q\d+$/', $field_name)) {
                                $lead_data['custom_fields'][$field_name] = array(
                                    'question' => $this->get_question_text($field_name),
                                    'answer' => $field_value
                                );
                            } else {
                                // Store other fields in custom_fields as well
                                $lead_data['custom_fields'][$field_name] = $field_value;
                            }
                            break;
                    }
                }
            }

            // Validate required fields
            if (empty($lead_data['name']) && empty($lead_data['email'])) {
                $this->log_webhook('lead_validation_failed', array(
                    'reason' => 'Missing required fields (name or email)',
                    'lead_data' => $lead_data
                ));
                return null;
            }

            // Set default name if missing
            if (empty($lead_data['name'])) {
                $lead_data['name'] = 'Facebook Lead';
            }

            return $lead_data;

        } catch (Exception $e) {
            $this->log_webhook('lead_mapping_error', array(
                'error' => $e->getMessage(),
                'meta_lead' => $meta_lead
            ));
            return null;
        }
    }

    /**
     * Get question text for custom questions
     *
     * @param string $field_name
     * @return string
     */
    private function get_question_text($field_name) {
        // Try to find question in campaign questions
        $campaign_questions = get_option('aqop_campaign_questions', array());

        foreach ($campaign_questions as $campaign_id => $questions) {
            if (isset($questions[$field_name])) {
                return $questions[$field_name]['text'] ?? $field_name;
            }
        }

        // Return field name if no question found
        return ucfirst(str_replace(array('custom_question_', 'question_', '_'), array('', '', ' '), $field_name));
    }

    /**
     * Create AQOP lead in database
     *
     * @param array $lead_data
     * @return int|bool Lead ID or false on failure
     */
    private function create_aqop_lead($lead_data) {
        global $wpdb;

        try {
            // Prepare lead data
            $now = current_time('mysql');
            $lead_data_to_insert = array(
                'name' => sanitize_text_field($lead_data['name']),
                'email' => sanitize_email($lead_data['email'] ?? ''),
                'phone' => sanitize_text_field($lead_data['phone'] ?? ''),
                'country' => sanitize_text_field($lead_data['country'] ?? ''),
                'city' => sanitize_text_field($lead_data['city'] ?? ''),
                'source' => sanitize_text_field($lead_data['source']),
                'status_code' => sanitize_text_field($lead_data['status_code']),
                'assigned_to' => $lead_data['assigned_to'] ? intval($lead_data['assigned_to']) : null,
                'message' => sanitize_textarea_field($lead_data['message'] ?? ''),
                'custom_fields' => !empty($lead_data['custom_fields']) ? wp_json_encode($lead_data['custom_fields']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            );

            // Insert lead
            $result = $wpdb->insert(
                $wpdb->prefix . 'aqop_leads',
                $lead_data_to_insert,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
            );

            if ($result === false) {
                $this->log_webhook('database_insert_error', array(
                    'error' => $wpdb->last_error,
                    'lead_data' => $lead_data_to_insert
                ));
                return false;
            }

            $lead_id = $wpdb->insert_id;

            // Store meta data
            if (!empty($lead_data['meta_data'])) {
                update_post_meta($lead_id, '_meta_webhook_data', $lead_data['meta_data']);
            }

            $this->log_webhook('lead_created', array(
                'lead_id' => $lead_id,
                'name' => $lead_data['name'],
                'email' => $lead_data['email'],
                'source' => $lead_data['source']
            ));

            // Trigger action for integrations
            do_action('aqop_lead_created', $lead_id, $lead_data);

            return $lead_id;

        } catch (Exception $e) {
            $this->log_webhook('lead_creation_error', array(
                'error' => $e->getMessage(),
                'lead_data' => $lead_data
            ));
            return false;
        }
    }

    /**
     * Log webhook events for debugging
     *
     * @param string $event
     * @param array $data
     */
    private function log_webhook($event, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        );

        // Store in option for admin display (keep last 50 entries)
        $existing_logs = get_option('aqop_meta_webhook_logs', array());
        array_unshift($existing_logs, $log_entry);
        $existing_logs = array_slice($existing_logs, 0, 50);
        update_option('aqop_meta_webhook_logs', $existing_logs);

        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AQOP Meta Webhook: ' . $event . ' - ' . wp_json_encode($data));
        }
    }

    /**
     * Get webhook logs
     *
     * @param int $limit
     * @return array
     */
    public static function get_webhook_logs($limit = 10) {
        $logs = get_option('aqop_meta_webhook_logs', array());
        return array_slice($logs, 0, $limit);
    }

    /**
     * Test webhook endpoint
     *
     * @return array
     */
    public static function test_webhook() {
        $webhook_url = get_rest_url(null, 'aqop/v1/meta/webhook');
        $verify_token = get_option('aqop_meta_verify_token', '');
        $app_secret = get_option('aqop_meta_app_secret', '');

        return array(
            'webhook_url' => $webhook_url,
            'verify_token_configured' => !empty($verify_token),
            'app_secret_configured' => !empty($app_secret),
            'test_payload' => array(
                'object' => 'page',
                'entry' => array(
                    array(
                        'id' => 'test_page_id',
                        'time' => time(),
                        'changes' => array(
                            array(
                                'field' => 'leadgen',
                                'value' => array(
                                    'leadgen_id' => 'test_' . time(),
                                    'form_id' => 'test_form',
                                    'created_time' => time(),
                                    'field_data' => array(
                                        array('name' => 'full_name', 'values' => array('Test User')),
                                        array('name' => 'email', 'values' => array('test@example.com')),
                                        array('name' => 'phone_number', 'values' => array('+1234567890'))
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
    }
}
