<?php
/**
 * Monitoring API Class
 *
 * REST API endpoints for user activity monitoring.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Monitoring_API
{
    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'aqop/v1';

    /**
     * Initialize API.
     *
     * @since 1.0.0
     */
    public static function init()
    {
        $instance = new self();
        add_action('rest_api_init', array($instance, 'register_routes'));
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        // Get active users
        register_rest_route(
            $this->namespace,
            '/monitoring/active-users',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_active_users'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );

        // Get user activity
        register_rest_route(
            $this->namespace,
            '/monitoring/user-activity/(?P<user_id>\d+)',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_user_activity'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'user_id' => array(
                        'required' => true,
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        },
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Get recent activity
        register_rest_route(
            $this->namespace,
            '/monitoring/recent-activity',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_recent_activity'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );

        // Heartbeat - update user activity
        register_rest_route(
            $this->namespace,
            '/monitoring/heartbeat',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'heartbeat'),
                'permission_callback' => '__return_true', // All logged-in users
            )
        );

        // Get monitoring stats
        register_rest_route(
            $this->namespace,
            '/monitoring/stats',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );
    }

    /**
     * Get active users.
     *
     * @since  1.0.0
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_active_users($request)
    {
        $module = $request->get_param('module');

        $args = array();
        if ($module) {
            $args['module'] = sanitize_text_field($module);
        }

        $active_users = AQOP_Session_Manager::get_active_users($args);

        // Add role information
        foreach ($active_users as &$user) {
            $user_data = get_userdata($user->user_id);
            if ($user_data) {
                $user->role = !empty($user_data->roles) ? $user_data->roles[0] : 'subscriber';
            }
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $active_users,
                'total' => count($active_users),
            )
        );
    }

    /**
     * Get user activity.
     *
     * @since  1.0.0
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_user_activity($request)
    {
        $user_id = $request['user_id'];
        $module = $request->get_param('module');
        $action_type = $request->get_param('action_type');
        $limit = $request->get_param('limit') ?: 100;

        $args = array(
            'limit' => absint($limit),
        );

        if ($module) {
            $args['module'] = sanitize_text_field($module);
        }

        if ($action_type) {
            $args['action_type'] = sanitize_text_field($action_type);
        }

        $activity = AQOP_Activity_Tracker::get_user_activity($user_id, $args);

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $activity,
                'total' => count($activity),
            )
        );
    }

    /**
     * Get recent activity.
     *
     * @since  1.0.0
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_recent_activity($request)
    {
        $module = $request->get_param('module');
        $limit = $request->get_param('limit') ?: 50;

        $args = array(
            'limit' => absint($limit),
        );

        if ($module) {
            $args['module'] = sanitize_text_field($module);
        }

        $activity = AQOP_Activity_Tracker::get_recent_activity($args);

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $activity,
                'total' => count($activity),
            )
        );
    }

    /**
     * Heartbeat - update user activity.
     *
     * @since  1.0.0
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function heartbeat($request)
    {
        $session_token = $request->get_param('session_token');
        $module = $request->get_param('module');
        $page = $request->get_param('page');

        if (!$session_token) {
            return new WP_Error(
                'missing_session_token',
                __('Session token is required.', 'aqop-core'),
                array('status' => 400)
            );
        }

        $data = array();
        if ($module) {
            $data['current_module'] = sanitize_text_field($module);
        }
        if ($page) {
            $data['current_page'] = esc_url_raw($page);
        }

        $updated = AQOP_Session_Manager::update_activity($session_token, $data);

        if (!$updated) {
            return new WP_Error(
                'update_failed',
                __('Failed to update activity.', 'aqop-core'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Activity updated',
            )
        );
    }

    /**
     * Get monitoring statistics.
     *
     * @since  1.0.0
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_stats($request)
    {
        global $wpdb;

        // Active users count
        $active_threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $active_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aq_user_sessions 
				WHERE is_active = 1 AND last_activity >= %s",
                $active_threshold
            )
        );

        // Users by module
        $by_module = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT current_module as module, COUNT(*) as count 
				FROM {$wpdb->prefix}aq_user_sessions 
				WHERE is_active = 1 AND last_activity >= %s AND current_module IS NOT NULL
				GROUP BY current_module",
                $active_threshold
            )
        );

        // Activity in last hour
        $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $recent_activity_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}aq_user_activity 
				WHERE created_at >= %s",
                $hour_ago
            )
        );

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'active_users' => (int) $active_count,
                    'users_by_module' => $by_module,
                    'recent_activity_count' => (int) $recent_activity_count,
                ),
            )
        );
    }

    /**
     * Check admin permission.
     *
     * @since  1.0.0
     * @return bool|WP_Error Permission result.
     */
    public function check_admin_permission()
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('You must be logged in.', 'aqop-core'),
                array('status' => 401)
            );
        }

        $user = wp_get_current_user();
        $allowed_roles = array('administrator', 'operation_admin');

        if (!array_intersect($allowed_roles, $user->roles)) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'aqop-core'),
                array('status' => 403)
            );
        }

        return true;
    }
}
