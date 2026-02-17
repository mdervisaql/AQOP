<?php
/**
 * Communications API Class
 *
 * REST API endpoints for communication logs and follow-ups.
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Communications_API class.
 *
 * @since 1.1.0
 */
class AQOP_Communications_API extends WP_REST_Controller
{

    /**
     * Register routes.
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        $namespace = 'aqop/v1';

        // === COMMUNICATIONS ===

        // Get communications for a lead
        register_rest_route($namespace, '/leads/(?P<id>\d+)/communications', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_communications'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        },
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'add_communication'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => $this->get_communication_schema(),
            ),
        ));

        // Update/Delete communication
        register_rest_route($namespace, '/communications/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_communication'),
                'permission_callback' => array($this, 'check_permission'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_communication'),
                'permission_callback' => array($this, 'check_delete_permission'),
            ),
        ));

        // === FOLLOW-UPS ===

        // Get follow-ups
        register_rest_route($namespace, '/follow-ups', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_follow_ups'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));

        // Get today's follow-ups
        register_rest_route($namespace, '/follow-ups/today', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_today_follow_ups'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));

        // Complete follow-up
        register_rest_route($namespace, '/follow-ups/(?P<id>\d+)/complete', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'complete_follow_up'),
                'permission_callback' => array($this, 'check_permission'),
            ),
        ));

        // Create follow-up (standalone - for next step scheduling)
        register_rest_route($namespace, '/follow-ups', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_follow_up'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'lead_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'title' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'description' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'due_date' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'contact_method' => array(
                        'type' => 'string',
                        'enum' => array('call', 'whatsapp', 'email', 'meeting', 'sms'),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'priority' => array(
                        'type' => 'string',
                        'enum' => array('low', 'medium', 'high'),
                        'default' => 'medium',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
        ));
    }

    /**
     * Get communications for a lead.
     */
    public function get_communications($request)
    {
        global $wpdb;
        $lead_id = $request['id'];

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, u.display_name as user_name 
				FROM {$wpdb->prefix}aq_communications c
				LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
				WHERE c.lead_id = %d
				ORDER BY c.created_at DESC",
                $lead_id
            )
        );

        return rest_ensure_response($results);
    }

    /**
     * Add new communication.
     */
    public function add_communication($request)
    {
        global $wpdb;
        $lead_id = $request['id'];
        $user_id = get_current_user_id();
        $params = $request->get_params();

        $data = array(
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'type' => sanitize_text_field($params['type']),
            'direction' => isset($params['direction']) ? sanitize_text_field($params['direction']) : 'outbound',
            'subject' => isset($params['subject']) ? sanitize_text_field($params['subject']) : null,
            'content' => isset($params['content']) ? sanitize_textarea_field($params['content']) : '',
            'outcome' => isset($params['outcome']) ? sanitize_text_field($params['outcome']) : null,
            'duration_seconds' => isset($params['duration_seconds']) ? absint($params['duration_seconds']) : null,
            'created_at' => current_time('mysql'),
        );

        if (!empty($params['follow_up_date'])) {
            $data['follow_up_date'] = sanitize_text_field($params['follow_up_date']);
            $data['follow_up_note'] = isset($params['follow_up_note']) ? sanitize_text_field($params['follow_up_note']) : null;
        }

        $inserted = $wpdb->insert($wpdb->prefix . 'aq_communications', $data);

        if (!$inserted) {
            return new WP_Error('db_error', 'Failed to insert communication log', array('status' => 500));
        }

        $communication_id = $wpdb->insert_id;

        // Create follow-up if date is set
        if (!empty($params['follow_up_date'])) {
            $wpdb->insert(
                $wpdb->prefix . 'aq_follow_ups',
                array(
                    'lead_id' => $lead_id,
                    'user_id' => $user_id,
                    'communication_id' => $communication_id,
                    'title' => 'Follow up: ' . ($data['subject'] ?: $data['type']),
                    'description' => $data['follow_up_note'] ?: 'Scheduled follow-up',
                    'due_date' => $data['follow_up_date'],
                    'priority' => 'medium',
                    'status' => 'pending',
                    'created_at' => current_time('mysql'),
                )
            );
        }

        // Update lead last contact
        $wpdb->update(
            $wpdb->prefix . 'aq_leads',
            array('last_contact_at' => current_time('mysql')),
            array('id' => $lead_id)
        );

        // Fire action for Lead Scoring
        do_action('aqop_communication_logged', $lead_id, $data['type']);

        return rest_ensure_response(array('success' => true, 'id' => $communication_id));
    }

    /**
     * Update communication.
     */
    public function update_communication($request)
    {
        global $wpdb;
        $id = $request['id'];
        $params = $request->get_params();

        // Only allow updating content and outcome
        $data = array();
        if (isset($params['content']))
            $data['content'] = sanitize_textarea_field($params['content']);
        if (isset($params['outcome']))
            $data['outcome'] = sanitize_text_field($params['outcome']);

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', array('status' => 400));
        }

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_communications',
            $data,
            array('id' => $id)
        );

        return rest_ensure_response(array('success' => (bool) $updated));
    }

    /**
     * Delete communication.
     */
    public function delete_communication($request)
    {
        global $wpdb;
        $id = $request['id'];

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'aq_communications',
            array('id' => $id)
        );

        return rest_ensure_response(array('success' => (bool) $deleted));
    }

    /**
     * Get follow-ups.
     */
    public function get_follow_ups($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $params = $request->get_params();
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';

        $sql = "SELECT f.*, l.name as lead_name, l.phone as lead_phone 
				FROM {$wpdb->prefix}aq_follow_ups f
				LEFT JOIN {$wpdb->prefix}aq_leads l ON f.lead_id = l.id
				WHERE f.user_id = %d";

        $args = array($user_id);

        if ($status !== 'all') {
            $sql .= " AND f.status = %s";
            $args[] = $status;
        }

        $sql .= " ORDER BY f.due_date ASC";

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$args));

        return rest_ensure_response($results);
    }

    /**
     * Get today's follow-ups.
     */
    public function get_today_follow_ups($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.*, l.name as lead_name 
				FROM {$wpdb->prefix}aq_follow_ups f
				LEFT JOIN {$wpdb->prefix}aq_leads l ON f.lead_id = l.id
				WHERE f.user_id = %d 
				AND f.status = 'pending'
				AND f.due_date BETWEEN %s AND %s
				ORDER BY f.due_date ASC",
                $user_id,
                $today_start,
                $today_end
            )
        );

        return rest_ensure_response($results);
    }

    /**
     * Complete follow-up.
     */
    public function complete_follow_up($request)
    {
        global $wpdb;
        $id = $request['id'];

        $updated = $wpdb->update(
            $wpdb->prefix . 'aq_follow_ups',
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ),
            array('id' => $id)
        );

        return rest_ensure_response(array('success' => (bool) $updated));
    }

    /**
     * Create a standalone follow-up (next step).
     */
    public function create_follow_up($request)
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $params = $request->get_params();

        $lead_id = absint($params['lead_id']);

        // Verify lead exists
        $lead = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name FROM {$wpdb->prefix}aq_leads WHERE id = %d",
                $lead_id
            )
        );

        if (!$lead) {
            return new WP_Error('not_found', 'Lead not found', array('status' => 404));
        }

        // Build description with contact method
        $contact_method = isset($params['contact_method']) ? sanitize_text_field($params['contact_method']) : '';
        $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : '';

        if ($contact_method) {
            $method_labels = array(
                'call' => 'اتصال هاتفي',
                'whatsapp' => 'واتساب',
                'email' => 'بريد إلكتروني',
                'meeting' => 'اجتماع',
                'sms' => 'رسالة نصية',
            );
            $method_label = isset($method_labels[$contact_method]) ? $method_labels[$contact_method] : $contact_method;
            $description = "[{$method_label}] " . $description;
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'aq_follow_ups',
            array(
                'lead_id' => $lead_id,
                'user_id' => $user_id,
                'title' => sanitize_text_field($params['title']),
                'description' => $description,
                'due_date' => sanitize_text_field($params['due_date']),
                'priority' => isset($params['priority']) ? sanitize_text_field($params['priority']) : 'medium',
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            )
        );

        if (!$inserted) {
            return new WP_Error('db_error', 'Failed to create follow-up', array('status' => 500));
        }

        $follow_up_id = $wpdb->insert_id;

        // Log the event
        if (class_exists('AQOP_Event_Logger')) {
            AQOP_Event_Logger::log(
                'leads',
                'follow_up_created',
                'lead',
                $lead_id,
                array(
                    'follow_up_id' => $follow_up_id,
                    'title' => $params['title'],
                    'due_date' => $params['due_date'],
                    'contact_method' => $contact_method,
                )
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'id' => $follow_up_id,
                'lead_id' => $lead_id,
                'lead_name' => $lead->name,
                'title' => $params['title'],
                'description' => $description,
                'due_date' => $params['due_date'],
                'contact_method' => $contact_method,
                'priority' => isset($params['priority']) ? $params['priority'] : 'medium',
                'status' => 'pending',
            ),
            'message' => 'Follow-up created successfully',
        ));
    }

    /**
     * Check permission.
     */
    public function check_permission()
    {
        return is_user_logged_in();
    }

    /**
     * Check delete permission (Managers only).
     */
    public function check_delete_permission()
    {
        $user = wp_get_current_user();
        $manager_roles = array('administrator', 'operation_admin', 'operation_manager');
        return !empty(array_intersect($manager_roles, $user->roles));
    }

    /**
     * Get schema.
     */
    private function get_communication_schema()
    {
        return array(
            'type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('whatsapp', 'sms', 'email', 'call', 'meeting', 'note'),
            ),
            'content' => array(
                'required' => true,
                'type' => 'string',
            ),
        );
    }
}
