<?php
/**
 * Activity Tracker REST API Controller
 *
 * Provides REST API endpoints for activity tracking.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AQOP_Activity_API class.
 */
class AQOP_Activity_API
{
    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'aqop/v1';

    /**
     * Activity Tracker instance.
     *
     * @var AQOP_Activity_Tracker
     */
    private $tracker;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tracker = new AQOP_Activity_Tracker();
    }

    /**
     * Register REST API routes.
     */
    public function register_routes()
    {
        // Log activity (POST /aqop/v1/activity/log)
        register_rest_route(
            $this->namespace,
            '/activity/log',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'log_activity'),
                'permission_callback' => array($this, 'check_logged_in'),
                'args' => array(
                    'activity_type' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'activity_details' => array(
                        'type' => 'object',
                    ),
                ),
            )
        );

        // Get logs (GET /aqop/v1/activity/logs)
        register_rest_route(
            $this->namespace,
            '/activity/logs',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_logs'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'search' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'activity_type' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'user_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Clear logs (DELETE /aqop/v1/activity/clear)
        register_rest_route(
            $this->namespace,
            '/activity/clear',
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'clear_logs'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'days' => array(
                        'default' => 0, // 0 means clear all
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Get stats (GET /aqop/v1/activity/stats)
        register_rest_route(
            $this->namespace,
            '/activity/stats',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );
    }

    /**
     * Log activity.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function log_activity($request)
    {
        $params = $request->get_params();
        $user_id = get_current_user_id();

        // If batch logging (array of activities)
        if (isset($params['batch']) && is_array($params['batch'])) {
            $count = 0;
            foreach ($params['batch'] as $activity) {
                if (!empty($activity['type'])) {
                    $this->tracker->log(
                        $user_id,
                        $activity['type'],
                        isset($activity['details']) ? $activity['details'] : array(),
                        '', // IP auto-detected
                        '', // Agent auto-detected
                        isset($activity['session_id']) ? $activity['session_id'] : ''
                    );
                    $count++;
                }
            }
            return new WP_REST_Response(array('success' => true, 'logged' => $count));
        }

        // Single log
        if (empty($params['activity_type'])) {
            return new WP_REST_Response(array('success' => false, 'message' => 'Missing parameter(s): activity_type'), 400);
        }

        $log_id = $this->tracker->log(
            $user_id,
            $params['activity_type'],
            isset($params['activity_details']) ? $params['activity_details'] : array(),
            '', // IP auto-detected
            '', // Agent auto-detected
            isset($params['session_id']) ? $params['session_id'] : ''
        );

        if (!$log_id) {
            return new WP_Error('log_failed', 'Failed to log activity', array('status' => 500));
        }

        return new WP_REST_Response(array('success' => true, 'id' => $log_id));
    }

    /**
     * Get logs.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function get_logs($request)
    {
        $params = $request->get_params();

        $page = isset($params['page']) ? absint($params['page']) : 1;
        $per_page = isset($params['per_page']) ? absint($params['per_page']) : 20;
        $offset = ($page - 1) * $per_page;

        $args = array(
            'limit' => $per_page,
            'offset' => $offset,
            'search' => isset($params['search']) ? $params['search'] : '',
            'activity_type' => isset($params['activity_type']) ? $params['activity_type'] : '',
            'user_id' => isset($params['user_id']) ? $params['user_id'] : 0,
            'date_from' => isset($params['date_from']) ? $params['date_from'] : '',
            'date_to' => isset($params['date_to']) ? $params['date_to'] : '',
        );

        $result = $this->tracker->get_logs($args);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $result['items'],
            'meta' => array(
                'total' => $result['total'],
                'pages' => ceil($result['total'] / $per_page),
                'current_page' => $page,
            )
        ));
    }

    /**
     * Clear logs.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function clear_logs($request)
    {
        $days = isset($request['days']) ? absint($request['days']) : 0;
        $deleted = $this->tracker->cleanup($days);

        return new WP_REST_Response(array(
            'success' => true,
            'deleted' => $deleted,
            'message' => 'Logs cleared successfully'
        ));
    }

    /**
     * Get stats.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function get_stats($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aq_user_activity_log';

        // Active users today
        $today = date('Y-m-d');
        $active_users_today = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE DATE(created_at) = '$today'");

        // Total logs
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Top activities
        $top_activities = $wpdb->get_results("SELECT activity_type, COUNT(*) as count FROM $table_name GROUP BY activity_type ORDER BY count DESC LIMIT 5");

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'active_users_today' => (int) $active_users_today,
                'total_logs' => (int) $total_logs,
                'top_activities' => $top_activities
            )
        ));
    }

    /**
     * Check if user is logged in.
     */
    public function check_logged_in()
    {
        return is_user_logged_in();
    }

    /**
     * Check admin permission.
     */
    public function check_admin_permission()
    {
        return current_user_can('manage_options') || current_user_can('edit_others_posts');
    }
}
