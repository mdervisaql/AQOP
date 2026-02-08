<?php
/**
 * Automation Engine Class
 *
 * Handles processing of automation rules based on triggers and conditions.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Automation_Engine class.
 *
 * @since 1.0.0
 */
class AQOP_Automation_Engine
{

    /**
     * Process a trigger event.
     *
     * @since 1.0.0
     * @param string $event   Trigger event name.
     * @param int    $lead_id Lead ID.
     * @param array  $context Additional context data.
     */
    public function process_trigger($event, $lead_id, $context = array())
    {
        global $wpdb;

        // Get active rules for this trigger.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_automation_rules 
				WHERE trigger_event = %s AND is_active = 1 
				ORDER BY priority ASC",
                $event
            )
        );

        if (empty($rules)) {
            return;
        }

        // Get lead data.
        $lead = $this->get_lead_data($lead_id);
        if (!$lead) {
            return;
        }

        foreach ($rules as $rule) {
            // Check conditions.
            if ($this->evaluate_conditions($rule, $lead, $context)) {
                // Execute actions.
                $this->execute_actions($rule, $lead, $context);
            }
        }
    }

    /**
     * Get lead data.
     *
     * @since 1.0.0
     * @param int $lead_id Lead ID.
     * @return object|null Lead object or null.
     */
    private function get_lead_data($lead_id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aq_leads WHERE id = %d",
                $lead_id
            )
        );
    }

    /**
     * Evaluate rule conditions.
     *
     * @since 1.0.0
     * @param object $rule    Rule object.
     * @param object $lead    Lead object.
     * @param array  $context Context data.
     * @return bool True if conditions match.
     */
    private function evaluate_conditions($rule, $lead, $context)
    {
        $conditions = json_decode($rule->conditions, true);

        if (empty($conditions)) {
            return true; // No conditions means always run.
        }

        // Default logic is AND (all must match).
        // TODO: Support OR logic groups if needed.

        foreach ($conditions as $condition) {
            if (!$this->check_condition($condition, $lead, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check a single condition.
     *
     * @since 1.0.0
     * @param array  $condition Condition data.
     * @param object $lead      Lead object.
     * @param array  $context   Context data.
     * @return bool True if condition matches.
     */
    private function check_condition($condition, $lead, $context)
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        // Get actual value from lead or context.
        $actual_value = null;

        // Special fields handling.
        if ('hours_since_created' === $field) {
            $created_time = strtotime($lead->created_at);
            $actual_value = (current_time('timestamp') - $created_time) / 3600;
        } elseif ('hours_since_last_contact' === $field) {
            $last_contact = !empty($lead->last_contact_at) ? strtotime($lead->last_contact_at) : strtotime($lead->created_at);
            $actual_value = (current_time('timestamp') - $last_contact) / 3600;
        } elseif (isset($lead->$field)) {
            $actual_value = $lead->$field;
        } elseif (isset($context[$field])) {
            $actual_value = $context[$field];
        }

        return $this->compare_value($actual_value, $operator, $value);
    }

    /**
     * Compare values based on operator.
     *
     * @since 1.0.0
     * @param mixed  $actual   Actual value.
     * @param string $operator Operator.
     * @param mixed  $expected Expected value.
     * @return bool Comparison result.
     */
    private function compare_value($actual, $operator, $expected)
    {
        switch ($operator) {
            case 'equals':
                return $actual == $expected;
            case 'not_equals':
                return $actual != $expected;
            case 'greater_than':
                return $actual > $expected;
            case 'less_than':
                return $actual < $expected;
            case 'contains':
                return strpos((string) $actual, (string) $expected) !== false;
            case 'in_list':
                $list = array_map('trim', explode(',', $expected));
                return in_array((string) $actual, $list, true);
            case 'true':
                return (bool) $actual === true;
            case 'false':
                return (bool) $actual === false;
            case 'changed_from': // Context specific
                return $actual == $expected;
            case 'changed_to': // Context specific
                return $actual == $expected;
            default:
                return false;
        }
    }

    /**
     * Execute rule actions.
     *
     * @since 1.0.0
     * @param object $rule    Rule object.
     * @param object $lead    Lead object.
     * @param array  $context Context data.
     */
    private function execute_actions($rule, $lead, $context)
    {
        $actions = json_decode($rule->actions, true);

        if (empty($actions)) {
            return;
        }

        $actions_executed = array();
        $status = 'success';
        $error_message = '';

        $start_time = microtime(true);

        foreach ($actions as $action) {
            try {
                $this->execute_action($action, $lead, $context);
                $actions_executed[] = array(
                    'type' => $action['type'],
                    'status' => 'success',
                );
            } catch (Exception $e) {
                $status = 'partial'; // Or failed depending on strictness.
                $error_message .= $e->getMessage() . '; ';
                $actions_executed[] = array(
                    'type' => $action['type'],
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                );
            }
        }

        $execution_time = (microtime(true) - $start_time) * 1000;

        // Update rule stats.
        $this->update_rule_stats($rule->id);

        // Log execution.
        $this->log_execution($rule->id, $lead->id, $rule->trigger_event, $rule->conditions, $actions_executed, $status, $error_message, $execution_time);
    }

    /**
     * Execute a single action.
     *
     * @since 1.0.0
     * @param array  $action  Action data.
     * @param object $lead    Lead object.
     * @param array  $context Context data.
     * @throws Exception If action fails.
     */
    private function execute_action($action, $lead, $context)
    {
        $type = $action['type'];
        $params = isset($action['params']) ? $action['params'] : array();

        switch ($type) {
            case 'send_whatsapp':
                $this->action_send_whatsapp($lead, $params);
                break;
            case 'assign_to_user':
                $this->action_assign_to_user($lead, $params);
                break;
            case 'change_status':
                $this->action_change_status($lead, $params);
                break;
            case 'add_note':
                $this->action_add_note($lead, $params);
                break;
            case 'update_score':
                $this->action_update_score($lead, $params);
                break;
            case 'send_notification':
                $this->action_send_notification($lead, $params);
                break;
            default:
                throw new Exception("Unknown action type: $type");
        }
    }

    // === Action Implementations ===

    /**
     * Send WhatsApp message.
     */
    private function action_send_whatsapp($lead, $params)
    {
        if (!class_exists('AQOP_WhatsApp_API')) {
            throw new Exception('WhatsApp API not available');
        }

        $api = new AQOP_WhatsApp_API();
        // Assuming params has 'template_name' or 'message'
        // This is a placeholder implementation.
        // $api->send_message($lead->phone, $params['message']);
    }

    /**
     * Assign to user.
     */
    private function action_assign_to_user($lead, $params)
    {
        global $wpdb;
        $user_id = absint($params['user_id']);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            "{$wpdb->prefix}aq_leads",
            array('assigned_to' => $user_id),
            array('id' => $lead->id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Change status.
     */
    private function action_change_status($lead, $params)
    {
        global $wpdb;
        $status_code = sanitize_text_field($params['status_code']);

        // Get status ID.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $status_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = %s",
                $status_code
            )
        );

        if ($status_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update(
                "{$wpdb->prefix}aq_leads",
                array('status_id' => $status_id),
                array('id' => $lead->id),
                array('%d'),
                array('%d')
            );
        }
    }

    /**
     * Add note.
     */
    private function action_add_note($lead, $params)
    {
        global $wpdb;
        $note_text = sanitize_textarea_field($params['note']);
        $user_id = get_current_user_id(); // Or system user (0)

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            "{$wpdb->prefix}aq_leads_notes",
            array(
                'lead_id' => $lead->id,
                'user_id' => $user_id,
                'note_text' => $note_text,
            ),
            array('%d', '%d', '%s')
        );
    }

    /**
     * Update score.
     */
    private function action_update_score($lead, $params)
    {
        if (!class_exists('AQOP_Lead_Scoring')) {
            return;
        }

        // This might need a specific method in Lead Scoring class to manually adjust score
        // For now, we'll just log it as a limitation or implement a direct DB update if needed,
        // but ideally we use the scoring class.
    }

    /**
     * Send notification.
     */
    private function action_send_notification($lead, $params)
    {
        if (!class_exists('AQOP_Notification_Manager')) {
            return;
        }

        $manager = new AQOP_Notification_Manager();
        // $manager->send_notification(...)
    }


    /**
     * Update rule statistics.
     *
     * @since 1.0.0
     * @param int $rule_id Rule ID.
     */
    private function update_rule_stats($rule_id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}aq_automation_rules 
				SET run_count = run_count + 1, last_run_at = %s 
				WHERE id = %d",
                current_time('mysql'),
                $rule_id
            )
        );
    }

    /**
     * Log execution.
     *
     * @since 1.0.0
     * @param int    $rule_id        Rule ID.
     * @param int    $lead_id        Lead ID.
     * @param string $trigger_event  Trigger event.
     * @param string $conditions     Conditions JSON.
     * @param array  $actions        Actions executed array.
     * @param string $status         Status.
     * @param string $error_message  Error message.
     * @param float  $execution_time Execution time in ms.
     */
    private function log_execution($rule_id, $lead_id, $trigger_event, $conditions, $actions, $status, $error_message, $execution_time)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            "{$wpdb->prefix}aq_automation_logs",
            array(
                'rule_id' => $rule_id,
                'lead_id' => $lead_id,
                'trigger_event' => $trigger_event,
                'conditions_matched' => $conditions, // Already JSON string from rule
                'actions_executed' => wp_json_encode($actions),
                'status' => $status,
                'error_message' => $error_message,
                'execution_time_ms' => round($execution_time),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );
    }
    /**
     * Check for no response leads (Cron Job).
     *
     * @since 1.0.0
     */
    public function check_no_response_leads()
    {
        global $wpdb;

        // Get 'no_response' rules
        $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_automation_rules WHERE trigger_event = 'no_response' AND is_active = 1");

        foreach ($rules as $rule) {
            $conditions = json_decode($rule->conditions, true);
            $hours = 24; // Default fallback
            $found_time_condition = false;

            if (is_array($conditions)) {
                foreach ($conditions as $cond) {
                    if ($cond['field'] == 'hours_since_last_contact' && $cond['operator'] == 'greater_than') {
                        $hours = floatval($cond['value']);
                        $found_time_condition = true;
                        break;
                    }
                }
            }

            if (!$found_time_condition) {
                continue; // Skip rules without time condition to avoid processing all leads
            }

            $cutoff = date('Y-m-d H:i:s', current_time('timestamp') - ($hours * 3600));

            // Find leads that match time criteria and haven't had this rule executed
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql = "SELECT id FROM {$wpdb->prefix}aq_leads 
                    WHERE (last_contact_at < %s OR (last_contact_at IS NULL AND created_at < %s))
                    AND status_id NOT IN (SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE is_closed = 1)
                    AND id NOT IN (SELECT lead_id FROM {$wpdb->prefix}aq_automation_logs WHERE rule_id = %d)";

            $lead_ids = $wpdb->get_col($wpdb->prepare($sql, $cutoff, $cutoff, $rule->id));

            foreach ($lead_ids as $lead_id) {
                $lead = $this->get_lead_data($lead_id);
                if ($lead && $this->evaluate_conditions($rule, $lead, array())) {
                    $this->execute_actions($rule, $lead, array());
                }
            }
        }
    }

    /**
     * Check for overdue follow-ups (Cron Job).
     *
     * @since 1.0.0
     */
    public function check_overdue_follow_ups()
    {
        global $wpdb;

        // Get 'follow_up_overdue' rules
        $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_automation_rules WHERE trigger_event = 'follow_up_overdue' AND is_active = 1");

        if (empty($rules)) {
            return;
        }

        // Find overdue follow-ups
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $sql = "SELECT lead_id, id as follow_up_id FROM {$wpdb->prefix}aq_leads_followups 
                WHERE follow_up_date < %s AND status = 'pending'";

        $overdue_items = $wpdb->get_results($wpdb->prepare($sql, current_time('mysql')));

        foreach ($overdue_items as $item) {
            foreach ($rules as $rule) {
                // Check if rule already executed for this follow-up (using context or unique log check)
                // For simplicity, we check if executed for this lead + rule recently? 
                // Or better, we log the follow_up_id in context and check that?
                // The log table doesn't have context column indexed or easily searchable.

                // Workaround: Check if rule executed for this lead today? 
                // Or just execute. If the action is "Send Notification", we don't want to spam.

                // Let's check if we logged this specific trigger recently.
                $already_run = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}aq_automation_logs 
                    WHERE rule_id = %d AND lead_id = %d AND created_at > %s",
                    $rule->id,
                    $item->lead_id,
                    date('Y-m-d H:i:s', strtotime('-1 day')) // Don't spam more than once a day per rule
                ));

                if (!$already_run) {
                    $lead = $this->get_lead_data($item->lead_id);
                    if ($lead && $this->evaluate_conditions($rule, $lead, array('follow_up_id' => $item->follow_up_id))) {
                        $this->execute_actions($rule, $lead, array('follow_up_id' => $item->follow_up_id));
                    }
                }
            }
        }
    }
}
