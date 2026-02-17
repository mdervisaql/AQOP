<?php
/**
 * Activity Tracker Class
 *
 * Tracks all user activities for monitoring and analytics.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Activity_Tracker
{
    /**
     * Track user action.
     *
     * @since  1.0.0
     * @param  array $data Activity data.
     * @return int|false Activity ID on success, false on failure.
     */
    public static function track_action($data)
    {
        global $wpdb;

        try {
            // Validate required fields
            if (empty($data['user_id']) || empty($data['module_code']) || empty($data['action_type'])) {
                return false;
            }

            // Get session ID if available
            $session_id = isset($data['session_id']) ? absint($data['session_id']) : self::get_current_session_id($data['user_id']);

            // Prepare activity data
            $activity_data = array(
                'session_id' => $session_id,
                'user_id' => absint($data['user_id']),
                'module_code' => sanitize_text_field($data['module_code']),
                'action_type' => sanitize_text_field($data['action_type']),
                'action_details' => isset($data['action_details']) ? sanitize_textarea_field($data['action_details']) : null,
                'page_url' => isset($data['page_url']) ? esc_url_raw($data['page_url']) : null,
                'ip_address' => isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : self::get_client_ip(),
                'created_at' => current_time('mysql'),
            );

            // Insert activity
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'aq_user_activity',
                $activity_data,
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if (false === $inserted) {
                error_log('AQOP Activity Tracker: Failed to track action - ' . $wpdb->last_error);
                return false;
            }

            return $wpdb->insert_id;

        } catch (Exception $e) {
            error_log('AQOP Activity Tracker: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Track page view.
     *
     * @since  1.0.0
     * @param  int    $user_id User ID.
     * @param  string $module  Module code.
     * @param  string $page_url Page URL.
     * @return int|false Activity ID on success, false on failure.
     */
    public static function track_page_view($user_id, $module, $page_url)
    {
        return self::track_action(array(
            'user_id' => $user_id,
            'module_code' => $module,
            'action_type' => 'page_view',
            'page_url' => $page_url,
        ));
    }

    /**
     * Track API call.
     *
     * @since  1.0.0
     * @param  int    $user_id  User ID.
     * @param  string $endpoint API endpoint.
     * @param  string $method   HTTP method.
     * @param  array  $params   Request parameters.
     * @return int|false Activity ID on success, false on failure.
     */
    public static function track_api_call($user_id, $endpoint, $method, $params = array())
    {
        $module = self::extract_module_from_endpoint($endpoint);

        return self::track_action(array(
            'user_id' => $user_id,
            'module_code' => $module,
            'action_type' => 'api_call',
            'action_details' => json_encode(array(
                'endpoint' => $endpoint,
                'method' => $method,
                'params' => $params,
            )),
        ));
    }

    /**
     * Get user activity.
     *
     * @since  1.0.0
     * @param  int   $user_id User ID.
     * @param  array $args    Query arguments.
     * @return array Activity records.
     */
    public static function get_user_activity($user_id, $args = array())
    {
        global $wpdb;

        $defaults = array(
            'module' => null,
            'action_type' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 100,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('a.user_id = %d');
        $where_values = array(absint($user_id));

        if ($args['module']) {
            $where_clauses[] = 'a.module_code = %s';
            $where_values[] = $args['module'];
        }

        if ($args['action_type']) {
            $where_clauses[] = 'a.action_type = %s';
            $where_values[] = $args['action_type'];
        }

        if ($args['date_from']) {
            $where_clauses[] = 'a.created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_clauses[] = 'a.created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where_clauses);

        $sql = "SELECT 
			a.*,
			u.display_name as user_name
		FROM {$wpdb->prefix}aq_user_activity a
		LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
		WHERE {$where_sql}
		ORDER BY a.created_at DESC
		LIMIT %d OFFSET %d";

        $where_values[] = absint($args['limit']);
        $where_values[] = absint($args['offset']);

        $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return $results ? $results : array();
    }

    /**
     * Get recent activity.
     *
     * @since  1.0.0
     * @param  array $args Query arguments.
     * @return array Activity records.
     */
    public static function get_recent_activity($args = array())
    {
        global $wpdb;

        $defaults = array(
            'module' => null,
            'limit' => 50,
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array();
        $where_values = array();

        if ($args['module']) {
            $where_clauses[] = 'a.module_code = %s';
            $where_values[] = $args['module'];
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        $sql = "SELECT 
			a.*,
			u.display_name as user_name,
			u.user_email
		FROM {$wpdb->prefix}aq_user_activity a
		LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
		{$where_sql}
		ORDER BY a.created_at DESC
		LIMIT %d";

        $where_values[] = absint($args['limit']);

        $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return $results ? $results : array();
    }

    /**
     * Get current session ID for user.
     *
     * @since  1.0.0
     * @param  int $user_id User ID.
     * @return int|null Session ID or null.
     */
    private static function get_current_session_id($user_id)
    {
        global $wpdb;

        $session = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_user_sessions 
				WHERE user_id = %d 
				AND is_active = 1 
				ORDER BY last_activity DESC 
				LIMIT 1",
                absint($user_id)
            )
        );

        return $session ? absint($session) : null;
    }

    /**
     * Extract module from API endpoint.
     *
     * @since  1.0.0
     * @param  string $endpoint API endpoint.
     * @return string Module code.
     */
    private static function extract_module_from_endpoint($endpoint)
    {
        // Extract module from endpoint like /aqop/v1/leads/123
        if (preg_match('/\/aqop\/v1\/([^\/]+)/', $endpoint, $matches)) {
            return $matches[1];
        }

        return 'core';
    }

    /**
     * Get client IP address.
     *
     * @since  1.0.0
     * @return string IP address.
     */
    private static function get_client_ip()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }
    /**
     * Get logs with pagination and filtering.
     *
     * @since 1.1.0
     * @param array $args Query arguments.
     * @return array Array containing 'items' and 'total'.
     */
    public function get_logs($args = array())
    {
        global $wpdb;

        $defaults = array(
            'user_id' => 0,
            'activity_type' => '',
            'search' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $where_values = array();

        if (!empty($args['user_id'])) {
            $where[] = 'a.user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if (!empty($args['activity_type'])) {
            $where[] = 'a.action_type = %s';
            $where_values[] = $args['activity_type'];
        }

        if (!empty($args['search'])) {
            $where[] = '(a.action_details LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_sql = implode(' AND ', $where);

        // Count total
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}aq_user_activity a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID WHERE {$where_sql}";
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $where_values));

        // Get items
        $sql = "SELECT a.*, u.display_name as user_name, u.user_email 
                FROM {$wpdb->prefix}aq_user_activity a 
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                WHERE {$where_sql} 
                ORDER BY a.{$args['orderby']} {$args['order']} 
                LIMIT %d OFFSET %d";

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $items = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        // Map fields
        if ($items) {
            foreach ($items as $item) {
                $item->activity_type = $item->action_type;
                $item->activity_details = json_decode($item->action_details, true) ?: $item->action_details;
            }
        }

        return array(
            'items' => $items ? $items : array(),
            'total' => $total ? (int) $total : 0
        );
    }

    /**
     * Initialize tracker hooks.
     *
     * @since 1.2.0
     */
    public static function init()
    {
        add_action('wp_login', array(__CLASS__, 'track_user_login'), 10, 2);
        add_action('current_screen', array(__CLASS__, 'track_admin_page_view'));
    }

    /**
     * Track user login.
     *
     * @since 1.2.0
     * @param string $user_login User login.
     * @param object $user       User object.
     */
    public static function track_user_login($user_login, $user)
    {
        self::track_action(array(
            'user_id' => $user->ID,
            'module_code' => 'auth',
            'action_type' => 'login',
            'action_details' => 'User logged in',
        ));
    }

    /**
     * Track admin page view.
     *
     * @since 1.2.0
     * @param object $screen Current screen.
     */
    public static function track_admin_page_view($screen)
    {
        if (!is_admin() || !is_user_logged_in()) {
            return;
        }

        // Only track AQOP pages or relevant admin pages
        if (strpos($screen->id, 'aqop') === false && strpos($screen->id, 'page_aqop') === false) {
            return;
        }

        self::track_page_view(
            get_current_user_id(),
            'admin',
            $_SERVER['REQUEST_URI']
        );
    }

    /**
     * Cleanup old logs.
     *
     * @since 1.1.0
     * @param int $days Days to keep.
     * @return int Number of deleted rows.
     */
    public function cleanup($days = 90)
    {
        global $wpdb;
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}aq_user_activity WHERE created_at < %s", $date));
    }
}
