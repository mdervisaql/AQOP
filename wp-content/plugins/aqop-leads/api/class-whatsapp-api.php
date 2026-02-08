<?php
/**
 * WhatsApp API Class
 *
 * REST API endpoints for WhatsApp Integration.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_WhatsApp_API class.
 */
class AQOP_WhatsApp_API extends WP_REST_Controller
{
    /**
     * WhatsApp Integration instance.
     *
     * @var AQOP_WhatsApp_Integration
     */
    private $whatsapp;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'aqop/v1';
        $this->rest_base = 'whatsapp';

        require_once AQOP_LEADS_PLUGIN_DIR . 'includes/integrations/class-whatsapp-integration.php';
        $this->whatsapp = new AQOP_WhatsApp_Integration();
    }

    /**
     * Register routes.
     */
    public function register_routes()
    {
        // Send Message
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/send',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'send_message'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        // Get Messages for Lead
        register_rest_route(
            $this->namespace,
            '/leads/(?P<id>\d+)/whatsapp/messages',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_messages'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        // Webhook (Public)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/webhook',
            array(
                array(
                    'methods' => WP_REST_Server::READABLE, // GET for verification
                    'callback' => array($this, 'verify_webhook'),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods' => WP_REST_Server::CREATABLE, // POST for events
                    'callback' => array($this, 'handle_webhook'),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // Get Templates
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/templates',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_templates'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );

        // Test Connection
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/test',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'test_connection'),
                'permission_callback' => array($this, 'check_permission'),
            )
        );
    }

    /**
     * Check permission.
     *
     * @return bool
     */
    public function check_permission()
    {
        return current_user_can('edit_posts');
    }

    /**
     * Send Message.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response.
     */
    public function send_message($request)
    {
        $lead_id = $request->get_param('lead_id');
        $message = $request->get_param('message');
        $type = $request->get_param('type') ?: 'text';
        $template_name = $request->get_param('template_name');
        $template_params = $request->get_param('template_params') ?: array();

        // Get Lead Phone
        global $wpdb;
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_leads WHERE id = %d", $lead_id));

        if (!$lead) {
            return new WP_Error('not_found', 'Lead not found', array('status' => 404));
        }

        $phone = $lead->whatsapp ?: $lead->phone;
        if (!$phone) {
            return new WP_Error('no_phone', 'Lead has no phone number', array('status' => 400));
        }

        // Send via Integration
        if ($type === 'template') {
            $result = $this->whatsapp->send_template($phone, $template_name, 'en_US', $template_params);
        } else {
            $result = $this->whatsapp->send_message($phone, $message);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        // Save to DB
        $wa_message_id = $result['messages'][0]['id'] ?? null;

        $wpdb->insert(
            $wpdb->prefix . 'aq_whatsapp_messages',
            array(
                'lead_id' => $lead_id,
                'wa_message_id' => $wa_message_id,
                'phone_number' => $phone,
                'direction' => 'outbound',
                'message_type' => $type,
                'content' => $message, // Or template name
                'template_name' => $template_name,
                'status' => 'sent',
            )
        );

        // Log in Communications
        $wpdb->insert(
            $wpdb->prefix . 'aq_communications',
            array(
                'lead_id' => $lead_id,
                'user_id' => get_current_user_id(),
                'type' => 'whatsapp',
                'direction' => 'outbound',
                'content' => $message ?: "Template: $template_name",
                'outcome' => 'completed',
            )
        );

        return rest_ensure_response($result);
    }

    /**
     * Get Messages.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function get_messages($request)
    {
        $lead_id = $request->get_param('id');
        global $wpdb;

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aq_whatsapp_messages 
			 WHERE lead_id = %d 
			 ORDER BY created_at ASC",
            $lead_id
        ));

        return rest_ensure_response($messages);
    }

    /**
     * Verify Webhook (GET).
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|string Response.
     */
    public function verify_webhook($request)
    {
        $params = $request->get_params();
        $mode = $params['hub_mode'] ?? '';
        $token = $params['hub_verify_token'] ?? '';
        $challenge = $params['hub_challenge'] ?? '';

        $verify_token = get_option('aqop_whatsapp_webhook_token');

        if ($mode === 'subscribe' && $token === $verify_token) {
            echo $challenge; // Must echo raw challenge
            exit;
        }

        return new WP_Error('forbidden', 'Verification failed', array('status' => 403));
    }

    /**
     * Handle Webhook (POST).
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function handle_webhook($request)
    {
        $payload = $request->get_json_params();
        $this->whatsapp->handle_webhook($payload);
        return rest_ensure_response(array('success' => true));
    }

    /**
     * Get Templates.
     *
     * @return WP_REST_Response Response.
     */
    public function get_templates()
    {
        $templates = $this->whatsapp->get_templates();
        if (is_wp_error($templates)) {
            return $templates;
        }
        return rest_ensure_response($templates);
    }

    /**
     * Test Connection.
     *
     * @return WP_REST_Response Response.
     */
    public function test_connection()
    {
        $result = $this->whatsapp->test_connection();
        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response(array('success' => true, 'message' => 'Connection successful'));
    }
}
