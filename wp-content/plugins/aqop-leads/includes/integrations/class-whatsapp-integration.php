<?php
/**
 * WhatsApp Integration Class
 *
 * Handles interaction with Meta WhatsApp Business API.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_WhatsApp_Integration class.
 */
class AQOP_WhatsApp_Integration
{
    /**
     * API Base URL.
     *
     * @var string
     */
    private $api_url = 'https://graph.facebook.com/v18.0/';

    /**
     * Phone Number ID.
     *
     * @var string
     */
    private $phone_id;

    /**
     * Access Token.
     *
     * @var string
     */
    private $access_token;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->phone_id = get_option('aqop_whatsapp_phone_id');
        $this->access_token = get_option('aqop_whatsapp_access_token');
    }

    /**
     * Send a text message.
     *
     * @param string $phone_number Recipient phone number.
     * @param string $message      Message content.
     * @return array|WP_Error Response or error.
     */
    public function send_message($phone_number, $message)
    {
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone_number,
            'type' => 'text',
            'text' => array(
                'preview_url' => false,
                'body' => $message,
            ),
        );

        return $this->make_request('messages', 'POST', $payload);
    }

    /**
     * Send a template message.
     *
     * @param string $phone_number  Recipient phone number.
     * @param string $template_name Template name.
     * @param string $language      Language code (default: en_US).
     * @param array  $components    Template components (header, body, buttons).
     * @return array|WP_Error Response or error.
     */
    public function send_template($phone_number, $template_name, $language = 'en_US', $components = array())
    {
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone_number,
            'type' => 'template',
            'template' => array(
                'name' => $template_name,
                'language' => array(
                    'code' => $language,
                ),
                'components' => $components,
            ),
        );

        return $this->make_request('messages', 'POST', $payload);
    }

    /**
     * Get available templates.
     *
     * @return array|WP_Error List of templates or error.
     */
    public function get_templates()
    {
        $business_id = get_option('aqop_whatsapp_business_id');
        if (!$business_id) {
            return new WP_Error('missing_config', 'WhatsApp Business ID is not configured.');
        }

        // Templates endpoint is on the WABA ID, not Phone ID
        $url = $this->api_url . $business_id . '/message_templates';

        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ),
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message'], $data['error']);
        }

        return $data;
    }

    /**
     * Test connection.
     *
     * @return bool|WP_Error True if connected, error otherwise.
     */
    public function test_connection()
    {
        if (!$this->phone_id || !$this->access_token) {
            return new WP_Error('missing_config', 'Phone ID or Access Token is missing.');
        }

        // Simple GET request to check phone number details
        $response = $this->make_request('', 'GET');

        if (is_wp_error($response)) {
            return $response;
        }

        return true;
    }

    /**
     * Make API Request.
     *
     * @param string $endpoint API Endpoint relative to phone ID.
     * @param string $method   HTTP Method.
     * @param array  $payload  Request payload.
     * @return array|WP_Error Response data or error.
     */
    private function make_request($endpoint, $method = 'POST', $payload = null)
    {
        if (!$this->phone_id || !$this->access_token) {
            return new WP_Error('missing_config', 'WhatsApp configuration is missing.');
        }

        $url = $this->api_url . $this->phone_id;
        if ($endpoint) {
            $url .= '/' . $endpoint;
        }

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 15,
        );

        if ($payload && $method !== 'GET') {
            $args['body'] = json_encode($payload);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code >= 400) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API Error';
            return new WP_Error('api_error', $message, $data);
        }

        return $data;
    }

    /**
     * Handle Webhook Payload.
     *
     * @param array $payload Webhook payload.
     */
    public function handle_webhook($payload)
    {
        // Basic validation
        if (!isset($payload['object']) || $payload['object'] !== 'whatsapp_business_account') {
            return;
        }

        foreach ($payload['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }

                $value = $change['value'];

                // Handle Messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->process_incoming_message($message, $value['contacts'][0] ?? null);
                    }
                }

                // Handle Status Updates (sent, delivered, read)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->process_status_update($status);
                    }
                }
            }
        }
    }

    /**
     * Process incoming message.
     *
     * @param array $message Message data.
     * @param array $contact Contact data.
     */
    private function process_incoming_message($message, $contact)
    {
        global $wpdb;

        $wa_id = $message['from'];
        $phone = $wa_id; // Usually same, but might need normalization
        $message_id = $message['id'];
        $type = $message['type'];
        $timestamp = isset($message['timestamp']) ? date('Y-m-d H:i:s', $message['timestamp']) : current_time('mysql');

        // Find Lead
        $lead = $this->find_lead_by_phone($phone);
        $lead_id = $lead ? $lead->id : null;

        $content = '';
        $media_url = null;

        switch ($type) {
            case 'text':
                $content = $message['text']['body'];
                break;
            case 'image':
                $content = $message['image']['caption'] ?? '';
                $media_url = $this->get_media_url($message['image']['id']);
                break;
            case 'document':
                $content = $message['document']['caption'] ?? $message['document']['filename'];
                $media_url = $this->get_media_url($message['document']['id']);
                break;
            // Add other types as needed
            default:
                $content = "[Unsupported message type: $type]";
        }

        // Save to DB
        $wpdb->insert(
            $wpdb->prefix . 'aq_whatsapp_messages',
            array(
                'lead_id' => $lead_id,
                'wa_message_id' => $message_id,
                'phone_number' => $phone,
                'direction' => 'inbound',
                'message_type' => $type,
                'content' => $content,
                'media_url' => $media_url,
                'status' => 'delivered', // Incoming is always delivered to us
                'created_at' => $timestamp,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // If lead exists, log communication
        if ($lead_id) {
            $wpdb->insert(
                $wpdb->prefix . 'aq_communications',
                array(
                    'lead_id' => $lead_id,
                    'user_id' => 0, // System
                    'type' => 'whatsapp',
                    'direction' => 'inbound',
                    'content' => $content,
                    'outcome' => 'completed',
                )
            );
        }
    }

    /**
     * Process status update.
     *
     * @param array $status Status data.
     */
    private function process_status_update($status)
    {
        global $wpdb;

        $message_id = $status['id'];
        $new_status = $status['status']; // sent, delivered, read, failed

        $wpdb->update(
            $wpdb->prefix . 'aq_whatsapp_messages',
            array('status' => $new_status),
            array('wa_message_id' => $message_id),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Find lead by phone number.
     *
     * @param string $phone Phone number.
     * @return object|null Lead object or null.
     */
    private function find_lead_by_phone($phone)
    {
        global $wpdb;
        // Simple exact match for now. In production, handle formatting variations.
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}aq_leads WHERE phone LIKE %s OR whatsapp LIKE %s LIMIT 1",
            '%' . $phone . '%',
            '%' . $phone . '%'
        ));
    }

    /**
     * Get Media URL.
     * 
     * @param string $media_id Media ID.
     * @return string|null URL.
     */
    private function get_media_url($media_id)
    {
        // Retrieving media URL requires another API call.
        // For MVP, we'll just store the ID or skip.
        // Implementing full media retrieval is complex (requires download).
        return null;
    }
}
