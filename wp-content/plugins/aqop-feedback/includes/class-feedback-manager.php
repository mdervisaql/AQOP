<?php
/**
 * Feedback Manager Class
 *
 * Handles all CRUD operations for feedback.
 *
 * @package AQOP_Feedback
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Feedback_Manager
{

    /**
     * Create new feedback.
     *
     * @since  1.0.0
     * @param  array $data Feedback data.
     * @return int|false Feedback ID on success, false on failure.
     */
    public static function create_feedback($data)
    {
        global $wpdb;

        try {
            // Validate required fields.
            if (empty($data['title']) || empty($data['description'])) {
                throw new Exception('Title and description are required');
            }

            // Get current user if not provided.
            $user_id = isset($data['user_id']) ? absint($data['user_id']) : get_current_user_id();

            if (!$user_id) {
                throw new Exception('User ID is required');
            }

            // Prepare feedback data.
            $feedback_data = array(
                'user_id' => $user_id,
                'module_code' => isset($data['module_code']) ? sanitize_text_field($data['module_code']) : 'general',
                'feedback_type' => isset($data['feedback_type']) ? $data['feedback_type'] : 'question',
                'title' => sanitize_text_field($data['title']),
                'description' => sanitize_textarea_field($data['description']),
                'priority' => isset($data['priority']) ? $data['priority'] : 'medium',
                'status_id' => 1, // New
                'screenshot_url' => isset($data['screenshot_url']) ? esc_url_raw($data['screenshot_url']) : null,
                'browser_info' => isset($data['browser_info']) ? sanitize_text_field($data['browser_info']) : null,
                'page_url' => isset($data['page_url']) ? esc_url_raw($data['page_url']) : null,
                'created_at' => current_time('mysql'),
            );

            // Insert feedback.
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'aq_feedback',
                $feedback_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
            );

            if (false === $inserted) {
                throw new Exception('Failed to insert feedback: ' . $wpdb->last_error);
            }

            $feedback_id = $wpdb->insert_id;

            // Log event.
            if (class_exists('AQOP_Event_Logger')) {
                AQOP_Event_Logger::log(
                    'feedback',
                    'feedback_created',
                    'feedback',
                    $feedback_id,
                    array(
                        'module' => $feedback_data['module_code'],
                        'type' => $feedback_data['feedback_type'],
                        'priority' => $feedback_data['priority'],
                        'title' => $feedback_data['title'],
                    )
                );
            }

            // Send notification for critical bugs.
            if ('bug' === $feedback_data['feedback_type'] && 'critical' === $feedback_data['priority']) {
                self::send_critical_notification($feedback_id, $feedback_data);
            }

            do_action('aqop_feedback_created', $feedback_id, $data);

            return $feedback_id;

        } catch (Exception $e) {
            error_log('AQOP Feedback Manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update feedback.
     *
     * @since  1.0.0
     * @param  int   $feedback_id Feedback ID.
     * @param  array $data        Updated data.
     * @return bool True on success, false on failure.
     */
    public static function update_feedback($feedback_id, $data)
    {
        global $wpdb;

        try {
            $feedback_id = absint($feedback_id);

            if (!$feedback_id) {
                throw new Exception('Invalid feedback ID');
            }

            // Get current feedback.
            $old_feedback = self::get_feedback($feedback_id);
            if (!$old_feedback) {
                throw new Exception('Feedback not found');
            }

            // Prepare update data.
            $update_data = array();
            $update_format = array();

            $allowed_fields = array('status_id', 'priority', 'assigned_to', 'title', 'description');

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, array('status_id', 'assigned_to'), true)) {
                        $update_data[$field] = absint($data[$field]);
                        $update_format[] = '%d';
                    } else {
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                    }
                }
            }

            $update_data['updated_at'] = current_time('mysql');
            $update_format[] = '%s';

            // Check if resolved.
            if (isset($data['status_id']) && in_array(absint($data['status_id']), array(3, 4), true)) {
                $update_data['resolved_at'] = current_time('mysql');
                $update_data['resolved_by'] = get_current_user_id();
                $update_format[] = '%s';
                $update_format[] = '%d';
            }

            // Update feedback.
            $updated = $wpdb->update(
                $wpdb->prefix . 'aq_feedback',
                $update_data,
                array('id' => $feedback_id),
                $update_format,
                array('%d')
            );

            if (false === $updated) {
                throw new Exception('Failed to update feedback: ' . $wpdb->last_error);
            }

            // Log event.
            if (class_exists('AQOP_Event_Logger')) {
                AQOP_Event_Logger::log(
                    'feedback',
                    'feedback_updated',
                    'feedback',
                    $feedback_id,
                    array(
                        'updated_fields' => array_keys($update_data),
                        'old_data' => $old_feedback,
                    )
                );
            }

            do_action('aqop_feedback_updated', $feedback_id, $data, $old_feedback);

            return true;

        } catch (Exception $e) {
            error_log('AQOP Feedback Manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get feedback.
     *
     * @since  1.0.0
     * @param  int $feedback_id Feedback ID.
     * @return object|false Feedback object or false.
     */
    public static function get_feedback($feedback_id)
    {
        global $wpdb;

        $feedback_id = absint($feedback_id);

        if (!$feedback_id) {
            return false;
        }

        $feedback = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
					f.*,
					s.status_name_en,
					s.status_name_ar,
					s.color as status_color,
					u.display_name as user_name,
					u.user_email as user_email,
					a.display_name as assigned_user_name
				FROM {$wpdb->prefix}aq_feedback f
				LEFT JOIN {$wpdb->prefix}aq_feedback_status s ON f.status_id = s.id
				LEFT JOIN {$wpdb->users} u ON f.user_id = u.ID
				LEFT JOIN {$wpdb->users} a ON f.assigned_to = a.ID
				WHERE f.id = %d",
                $feedback_id
            )
        );

        return $feedback ? $feedback : false;
    }

    /**
     * Add comment to feedback.
     *
     * @since  1.0.0
     * @param  int    $feedback_id Feedback ID.
     * @param  string $comment_text Comment text.
     * @param  bool   $is_internal  Is internal comment.
     * @param  int    $user_id      User ID.
     * @return int|false Comment ID on success, false on failure.
     */
    public static function add_comment($feedback_id, $comment_text, $is_internal = false, $user_id = null)
    {
        global $wpdb;

        try {
            $feedback_id = absint($feedback_id);

            if (!$feedback_id || empty($comment_text)) {
                throw new Exception('Invalid parameters');
            }

            if (null === $user_id) {
                $user_id = get_current_user_id();
            }

            // Insert comment.
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'aq_feedback_comments',
                array(
                    'feedback_id' => $feedback_id,
                    'user_id' => $user_id,
                    'comment_text' => sanitize_textarea_field($comment_text),
                    'is_internal' => $is_internal ? 1 : 0,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s', '%d', '%s')
            );

            if (false === $inserted) {
                throw new Exception('Failed to add comment');
            }

            $comment_id = $wpdb->insert_id;

            // Update feedback updated_at.
            $wpdb->update(
                $wpdb->prefix . 'aq_feedback',
                array('updated_at' => current_time('mysql')),
                array('id' => $feedback_id),
                array('%s'),
                array('%d')
            );

            // Log event.
            if (class_exists('AQOP_Event_Logger')) {
                AQOP_Event_Logger::log(
                    'feedback',
                    'feedback_comment_added',
                    'feedback',
                    $feedback_id,
                    array(
                        'comment_id' => $comment_id,
                        'user_id' => $user_id,
                        'is_internal' => $is_internal,
                    )
                );
            }

            do_action('aqop_feedback_comment_added', $comment_id, $feedback_id);

            return $comment_id;

        } catch (Exception $e) {
            error_log('AQOP Feedback Manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get feedback comments.
     *
     * @since  1.0.0
     * @param  int  $feedback_id   Feedback ID.
     * @param  bool $include_internal Include internal comments.
     * @return array Array of comment objects.
     */
    public static function get_comments($feedback_id, $include_internal = false)
    {
        global $wpdb;

        $feedback_id = absint($feedback_id);

        if (!$feedback_id) {
            return array();
        }

        $where = $include_internal ? '' : 'AND c.is_internal = 0';

        $comments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
					c.*,
					u.display_name as user_name
				FROM {$wpdb->prefix}aq_feedback_comments c
				LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
				WHERE c.feedback_id = %d {$where}
				ORDER BY c.created_at ASC",
                $feedback_id
            )
        );

        return $comments ? $comments : array();
    }

    /**
     * Query feedback.
     *
     * @since  1.0.0
     * @param  array $args Query arguments.
     * @return array Query results.
     */
    public static function query_feedback($args = array())
    {
        global $wpdb;

        $defaults = array(
            'module' => null,
            'type' => null,
            'status' => null,
            'priority' => null,
            'assigned_to' => null,
            'user_id' => null,
            'search' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array();
        $where_values = array();

        if ($args['module']) {
            $where_clauses[] = 'f.module_code = %s';
            $where_values[] = $args['module'];
        }

        if ($args['type']) {
            $where_clauses[] = 'f.feedback_type = %s';
            $where_values[] = $args['type'];
        }

        if ($args['status']) {
            $where_clauses[] = 'f.status_id = %d';
            $where_values[] = absint($args['status']);
        }

        if ($args['priority']) {
            $where_clauses[] = 'f.priority = %s';
            $where_values[] = $args['priority'];
        }

        if ($args['assigned_to']) {
            $where_clauses[] = 'f.assigned_to = %d';
            $where_values[] = absint($args['assigned_to']);
        }

        if ($args['user_id']) {
            $where_clauses[] = 'f.user_id = %d';
            $where_values[] = absint($args['user_id']);
        }

        if ($args['search']) {
            $where_clauses[] = '(f.title LIKE %s OR f.description LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // Count total.
        $count_sql = "SELECT COUNT(f.id) FROM {$wpdb->prefix}aq_feedback f {$where_sql}";

        if (!empty($where_values)) {
            $total = $wpdb->get_var($wpdb->prepare($count_sql, $where_values));
        } else {
            $total = $wpdb->get_var($count_sql);
        }

        // Get feedback.
        $orderby = in_array($args['orderby'], array('created_at', 'updated_at', 'priority'), true) ? $args['orderby'] : 'created_at';
        $order = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';

        $query_sql = "SELECT 
			f.*,
			s.status_name_en,
			s.status_name_ar,
			s.color as status_color,
			u.display_name as user_name,
			a.display_name as assigned_user_name
		FROM {$wpdb->prefix}aq_feedback f
		LEFT JOIN {$wpdb->prefix}aq_feedback_status s ON f.status_id = s.id
		LEFT JOIN {$wpdb->users} u ON f.user_id = u.ID
		LEFT JOIN {$wpdb->users} a ON f.assigned_to = a.ID
		{$where_sql}
		ORDER BY f.{$orderby} {$order}
		LIMIT %d OFFSET %d";

        $where_values[] = absint($args['limit']);
        $where_values[] = absint($args['offset']);

        $feedback = $wpdb->get_results($wpdb->prepare($query_sql, $where_values));

        return array(
            'feedback' => $feedback ? $feedback : array(),
            'total' => (int) $total,
        );
    }

    /**
     * Send critical notification.
     *
     * @since  1.0.0
     * @param  int   $feedback_id Feedback ID.
     * @param  array $data        Feedback data.
     */
    private static function send_critical_notification($feedback_id, $data)
    {
        if (!class_exists('AQOP_Notification_System')) {
            return;
        }

        // Use centralized notification system.
        AQOP_Notification_System::send(array(
            'module' => 'feedback',
            'event' => 'feedback_created',
            'event_id' => $feedback_id,
            'priority' => 'critical',
            'data' => array(
                'feedback_type' => 'bug',
                'priority' => 'critical',
                'module_code' => $data['module_code'],
                'title' => $data['title'],
                'description' => $data['description'],
                'page_url' => $data['page_url'],
            ),
        ));
    }
}
