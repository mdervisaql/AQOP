<?php
/**
 * Bulk WhatsApp API Class
 *
 * Handles REST API endpoints for bulk WhatsApp messaging.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Bulk_WhatsApp_API class.
 *
 * @since 1.0.0
 */
class AQOP_Bulk_WhatsApp_API extends WP_REST_Controller
{

    /**
     * Bulk WhatsApp instance.
     *
     * @var AQOP_Bulk_WhatsApp
     */
    private $bulk_whatsapp;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->namespace = 'aqop/v1';
        $this->rest_base = 'whatsapp/bulk';

        if (!class_exists('AQOP_Bulk_WhatsApp')) {
            require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-bulk-whatsapp.php';
        }
        $this->bulk_whatsapp = new AQOP_Bulk_WhatsApp();
    }

    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/create', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_job'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/jobs', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_jobs'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/jobs/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_job'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/jobs/(?P<id>\d+)/cancel', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'cancel_job'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/jobs/(?P<id>\d+)/results', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_results'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/jobs/(?P<id>\d+)/export', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'export_results'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Check permissions.
     */
    public function check_permissions()
    {
        return current_user_can('manage_options') || current_user_can('edit_posts'); // Adjust capability as needed
    }

    /**
     * Create a job.
     */
    public function create_job($request)
    {
        $params = $request->get_params();

        $job_name = isset($params['job_name']) ? sanitize_text_field($params['job_name']) : '';
        $lead_ids = isset($params['lead_ids']) ? $params['lead_ids'] : array();
        $message_type = isset($params['message_type']) ? sanitize_text_field($params['message_type']) : 'custom';
        $message_content = isset($params['message_content']) ? $params['message_content'] : ''; // Content sanitization handled in class
        $template_name = isset($params['template_name']) ? sanitize_text_field($params['template_name']) : null;
        $template_params = isset($params['template_params']) ? $params['template_params'] : array();

        $result = $this->bulk_whatsapp->create_job($job_name, $lead_ids, $message_type, $message_content, $template_name, $template_params);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array('success' => true, 'job_id' => $result), 200);
    }

    /**
     * Get jobs list.
     */
    public function get_jobs($request)
    {
        $limit = isset($request['limit']) ? absint($request['limit']) : 20;
        $offset = isset($request['offset']) ? absint($request['offset']) : 0;

        $jobs = $this->bulk_whatsapp->get_jobs($limit, $offset);
        return new WP_REST_Response($jobs, 200);
    }

    /**
     * Get single job.
     */
    public function get_job($request)
    {
        global $wpdb;
        $job_id = $request['id'];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_bulk_whatsapp_jobs WHERE id = %d", $job_id));

        if (!$job) {
            return new WP_Error('not_found', 'Job not found', array('status' => 404));
        }

        return new WP_REST_Response($job, 200);
    }

    /**
     * Cancel job.
     */
    public function cancel_job($request)
    {
        $job_id = $request['id'];
        $this->bulk_whatsapp->cancel_job($job_id);
        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Get job results.
     */
    public function get_results($request)
    {
        $job_id = $request['id'];
        $results = $this->bulk_whatsapp->get_job_results($job_id);
        return new WP_REST_Response($results, 200);
    }

    /**
     * Export results (CSV).
     */
    public function export_results($request)
    {
        $job_id = $request['id'];
        $results = $this->bulk_whatsapp->get_job_results($job_id);

        if (empty($results)) {
            return new WP_Error('no_data', 'No results to export', array('status' => 404));
        }

        // Generate CSV
        $csv_data = "Lead Name,Phone,Status,Error,Sent At\n";
        foreach ($results as $row) {
            $csv_data .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $row->lead_name,
                $row->phone_number,
                $row->status,
                $row->error_message,
                $row->sent_at
            );
        }

        // Return as plain text
        $response = new WP_REST_Response($csv_data, 200);
        $response->set_headers(array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="job_' . $job_id . '_results.csv"',
        ));

        return $response;
    }
}
