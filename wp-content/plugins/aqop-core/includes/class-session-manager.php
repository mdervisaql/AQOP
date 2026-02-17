<?php
/**
 * Session Manager Class
 *
 * Manages user sessions for activity tracking and monitoring.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Session_Manager
{
    /**
     * Start a new user session.
     *
     * @since  1.0.0
     * @param  int   $user_id User ID.
     * @param  array $data    Session data.
     * @return string|false Session token on success, false on failure.
     */
    public static function start_session($user_id, $data = array())
    {
        global $wpdb;

        try {
            // Generate unique session token
            $session_token = wp_generate_password(64, false);

            // Prepare session data
            $session_data = array(
                'user_id' => absint($user_id),
                'session_token' => $session_token,
                'ip_address' => isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : self::get_client_ip(),
                'user_agent' => isset($data['user_agent']) ? sanitize_text_field($data['user_agent']) : $_SERVER['HTTP_USER_AGENT'],
                'login_at' => current_time('mysql'),
                'last_activity' => current_time('mysql'),
                'is_active' => 1,
            );

            // Insert session
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'aq_user_sessions',
                $session_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%d')
            );

            if (false === $inserted) {
                error_log('AQOP Session Manager: Failed to create session - ' . $wpdb->last_error);
                return false;
            }

            return $session_token;

        } catch (Exception $e) {
            error_log('AQOP Session Manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update session activity.
     *
     * @since  1.0.0
     * @param  string $session_token Session token.
     * @param  array  $data          Update data.
     * @return bool True on success, false on failure.
     */
    public static function update_activity($session_token, $data = array())
    {
        global $wpdb;

        $update_data = array(
            'last_activity' => current_time('mysql'),
        );

        if (isset($data['current_module'])) {
            $update_data['current_module'] = sanitize_text_field($data['current_module']);
        }

        if (isset($data['current_page'])) {
            $update_data['current_page'] = esc_url_raw($data['current_page']);
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_user_sessions',
            $update_data,
            array('session_token' => $session_token, 'is_active' => 1),
            array('%s', '%s', '%s'),
            array('%s', '%d')
        );

        return false !== $updated;
    }

    /**
     * End user session.
     *
     * @since  1.0.0
     * @param  string $session_token Session token.
     * @return bool True on success, false on failure.
     */
    public static function end_session($session_token)
    {
        global $wpdb;

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_user_sessions',
            array(
                'logout_at' => current_time('mysql'),
                'is_active' => 0,
            ),
            array('session_token' => $session_token),
            array('%s', '%d'),
            array('%s')
        );

        return false !== $updated;
    }

    /**
     * Get active users.
     *
     * @since  1.0.0
     * @param  array $args Query arguments.
     * @return array Active users.
     */
    public static function get_active_users($args = array())
    {
        global $wpdb;

        $defaults = array(
            'module' => null,
            'limit' => 50,
        );

        $args = wp_parse_args($args, $defaults);

        // Consider sessions active if last activity within 5 minutes
        $active_threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));

        $where = "s.is_active = 1 AND s.last_activity >= %s";
        $where_values = array($active_threshold);

        if ($args['module']) {
            $where .= " AND s.current_module = %s";
            $where_values[] = $args['module'];
        }

        $sql = "SELECT 
			s.id,
			s.user_id,
			s.session_token,
			s.current_module,
			s.current_page,
			s.ip_address,
			s.last_activity,
			s.login_at,
			u.display_name,
			u.user_email,
			TIMESTAMPDIFF(SECOND, s.login_at, NOW()) as session_duration
		FROM {$wpdb->prefix}aq_user_sessions s
		LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
		WHERE {$where}
		ORDER BY s.last_activity DESC
		LIMIT %d";

        $where_values[] = absint($args['limit']);

        $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return $results ? $results : array();
    }

    /**
     * Get session by token.
     *
     * @since  1.0.0
     * @param  string $session_token Session token.
     * @return object|false Session object or false.
     */
    public static function get_session($session_token)
    {
        global $wpdb;

        $session = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_user_sessions WHERE session_token = %s",
                $session_token
            )
        );

        return $session ? $session : false;
    }

    /**
     * Cleanup stale sessions.
     *
     * @since  1.0.0
     * @param  int $minutes Inactive minutes threshold.
     * @return int Number of sessions cleaned up.
     */
    public static function cleanup_stale_sessions($minutes = 30)
    {
        global $wpdb;

        $threshold = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}aq_user_sessions 
				SET is_active = 0, logout_at = last_activity 
				WHERE is_active = 1 
				AND last_activity < %s 
				AND logout_at IS NULL",
                $threshold
            )
        );

        return $updated ? $updated : 0;
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
}
