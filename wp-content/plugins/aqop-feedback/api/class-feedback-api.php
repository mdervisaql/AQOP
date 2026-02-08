<?php
/**
 * Feedback REST API Controller
 *
 * Handles all feedback REST API endpoints.
 *
 * @package AQOP_Feedback
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Feedback_API
{

    /**
     * API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'aqop/v1';

    /**
     * Initialize API.
     *
     * @since 1.0.0
     */
    public static function init()
    {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public static function register_routes()
    {
        // Create feedback.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'create_feedback'),
                'permission_callback' => 'is_user_logged_in',
                'args' => array(
                    'title' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'description' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            )
        );

        // List feedback.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'list_feedback'),
                'permission_callback' => 'is_user_logged_in',
            )
        );

        // Get single feedback.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback/(?P<id>\d+)',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_feedback'),
                'permission_callback' => 'is_user_logged_in',
            )
        );

        // Update feedback.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback/(?P<id>\d+)',
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array(__CLASS__, 'update_feedback'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            )
        );

        // Add comment.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback/(?P<id>\d+)/comments',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'add_comment'),
                'permission_callback' => 'is_user_logged_in',
                'args' => array(
                    'comment_text' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            )
        );

        // Get comments.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback/(?P<id>\d+)/comments',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_comments'),
                'permission_callback' => 'is_user_logged_in',
            )
        );

        // Get statistics.
        register_rest_route(
            self::NAMESPACE ,
            '/feedback/stats',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_stats'),
                'permission_callback' => array(__CLASS__, 'check_admin_permission'),
            )
        );
    }

    /**
     * Create feedback endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function create_feedback($request)
    {
        $data = array(
            'title' => $request->get_param('title'),
            'description' => $request->get_param('description'),
            'module_code' => $request->get_param('module_code'),
            'feedback_type' => $request->get_param('feedback_type'),
            'priority' => $request->get_param('priority'),
            'screenshot_url' => $request->get_param('screenshot_url'),
            'browser_info' => $request->get_param('browser_info'),
            'page_url' => $request->get_param('page_url'),
        );

        $feedback_id = AQOP_Feedback_Manager::create_feedback($data);

        if (!$feedback_id) {
            return new WP_Error(
                'feedback_creation_failed',
                __('Failed to create feedback.', 'aqop-feedback'),
                array('status' => 500)
            );
        }

        $feedback = AQOP_Feedback_Manager::get_feedback($feedback_id);

        return new WP_REST_Response(
            array(
                'success' => true,
                'feedback' => $feedback,
            ),
            201
        );
    }

    /**
     * List feedback endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function list_feedback($request)
    {
        $args = array(
            'module' => $request->get_param('module'),
            'type' => $request->get_param('type'),
            'status' => $request->get_param('status'),
            'priority' => $request->get_param('priority'),
            'assigned_to' => $request->get_param('assigned_to'),
            'search' => $request->get_param('search'),
            'limit' => $request->get_param('per_page') ? absint($request->get_param('per_page')) : 50,
            'offset' => $request->get_param('page') ? (absint($request->get_param('page')) - 1) * 50 : 0,
        );

        // If not admin, only show user's own feedback.
        if (!current_user_can('manage_options')) {
            $args['user_id'] = get_current_user_id();
        }

        $result = AQOP_Feedback_Manager::query_feedback($args);

        return new WP_REST_Response(
            array(
                'success' => true,
                'feedback' => $result['feedback'],
                'total' => $result['total'],
            ),
            200
        );
    }

    /**
     * Get feedback endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function get_feedback($request)
    {
        $feedback_id = absint($request->get_param('id'));
        $feedback = AQOP_Feedback_Manager::get_feedback($feedback_id);

        if (!$feedback) {
            return new WP_Error(
                'feedback_not_found',
                __('Feedback not found.', 'aqop-feedback'),
                array('status' => 404)
            );
        }

        // Check permission.
        if (!current_user_can('manage_options') && $feedback->user_id != get_current_user_id()) {
            return new WP_Error(
                'forbidden',
                __('You do not have permission to view this feedback.', 'aqop-feedback'),
                array('status' => 403)
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'feedback' => $feedback,
            ),
            200
        );
    }

    /**
     * Update feedback endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function update_feedback($request)
    {
        $feedback_id = absint($request->get_param('id'));

        $data = array();
        if ($request->has_param('status_id')) {
            $data['status_id'] = absint($request->get_param('status_id'));
        }
        if ($request->has_param('priority')) {
            $data['priority'] = $request->get_param('priority');
        }
        if ($request->has_param('assigned_to')) {
            $data['assigned_to'] = absint($request->get_param('assigned_to'));
        }

        $updated = AQOP_Feedback_Manager::update_feedback($feedback_id, $data);

        if (!$updated) {
            return new WP_Error(
                'feedback_update_failed',
                __('Failed to update feedback.', 'aqop-feedback'),
                array('status' => 500)
            );
        }

        $feedback = AQOP_Feedback_Manager::get_feedback($feedback_id);

        return new WP_REST_Response(
            array(
                'success' => true,
                'feedback' => $feedback,
            ),
            200
        );
    }

    /**
     * Add comment endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function add_comment($request)
    {
        $feedback_id = absint($request->get_param('id'));
        $comment_text = $request->get_param('comment_text');
        $is_internal = $request->get_param('is_internal') && current_user_can('manage_options');

        $comment_id = AQOP_Feedback_Manager::add_comment($feedback_id, $comment_text, $is_internal);

        if (!$comment_id) {
            return new WP_Error(
                'comment_creation_failed',
                __('Failed to add comment.', 'aqop-feedback'),
                array('status' => 500)
            );
        }

        $comments = AQOP_Feedback_Manager::get_comments($feedback_id, current_user_can('manage_options'));

        return new WP_REST_Response(
            array(
                'success' => true,
                'comments' => $comments,
            ),
            201
        );
    }

    /**
     * Get comments endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function get_comments($request)
    {
        $feedback_id = absint($request->get_param('id'));
        $include_internal = current_user_can('manage_options');

        $comments = AQOP_Feedback_Manager::get_comments($feedback_id, $include_internal);

        return new WP_REST_Response(
            array(
                'success' => true,
                'comments' => $comments,
            ),
            200
        );
    }

    /**
     * Get statistics endpoint.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public static function get_stats($request)
    {
        global $wpdb;

        // Total feedback.
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_feedback");

        // By status.
        $by_status = $wpdb->get_results(
            "SELECT 
				s.status_name_en as status,
				s.color,
				COUNT(f.id) as count
			FROM {$wpdb->prefix}aq_feedback f
			LEFT JOIN {$wpdb->prefix}aq_feedback_status s ON f.status_id = s.id
			GROUP BY f.status_id"
        );

        // By module.
        $by_module = $wpdb->get_results(
            "SELECT 
				module_code as module,
				COUNT(*) as count
			FROM {$wpdb->prefix}aq_feedback
			GROUP BY module_code"
        );

        // By type.
        $by_type = $wpdb->get_results(
            "SELECT 
				feedback_type as type,
				COUNT(*) as count
			FROM {$wpdb->prefix}aq_feedback
			GROUP BY feedback_type"
        );

        // By priority.
        $by_priority = $wpdb->get_results(
            "SELECT 
				priority,
				COUNT(*) as count
			FROM {$wpdb->prefix}aq_feedback
			GROUP BY priority"
        );

        return new WP_REST_Response(
            array(
                'success' => true,
                'total' => (int) $total,
                'by_status' => $by_status,
                'by_module' => $by_module,
                'by_type' => $by_type,
                'by_priority' => $by_priority,
            ),
            200
        );
    }

    /**
     * Check admin permission.
     *
     * @return bool True if admin.
     */
    public static function check_admin_permission()
    {
        return current_user_can('manage_options');
    }
}
