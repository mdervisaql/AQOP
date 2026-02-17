<?php
/**
 * Lead Scoring Class
 *
 * Handles lead scoring logic and calculations.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Lead_Scoring class.
 *
 * @since 1.0.0
 */
class AQOP_Lead_Scoring
{

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     * @static
     */
    public static function init()
    {
        // Hook into lead events to recalculate score
        add_action('aqop_lead_created', array(__CLASS__, 'calculate_score'), 10, 1);
        add_action('aqop_lead_updated', array(__CLASS__, 'calculate_score'), 10, 1);

        // Hook into communication events
        add_action('aqop_communication_logged', array(__CLASS__, 'handle_communication_event'), 10, 2);
    }

    /**
     * Handle communication event.
     * 
     * @param int $communication_id
     * @param array $data
     */
    public static function handle_communication_event($lead_id, $type = '')
    {
        if ($lead_id) {
            self::calculate_score($lead_id);
        }
    }

    /**
     * Calculate score for a lead.
     *
     * @since 1.0.0
     * @static
     * @param int $lead_id Lead ID.
     * @return int|bool New score or false on failure.
     */
    public static function calculate_score($lead_id)
    {
        global $wpdb;

        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_leads WHERE id = %d", $lead_id));

        if (!$lead) {
            return false;
        }

        // Get active rules
        $rules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_scoring_rules WHERE is_active = 1 ORDER BY priority ASC");

        $score = 0;
        $applied_rules = array();

        // Pre-fetch related data for rules
        $interactions_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}aq_communications WHERE lead_id = %d", $lead_id));
        $inquiries_count = 1; // Default to 1 (the lead itself), logic could be more complex

        // Calculate response time if applicable
        $response_time = 0; // Minutes
        $no_response_time = 0; // Minutes

        if ($lead->last_contact_at) {
            $created = strtotime($lead->created_at);
            $contacted = strtotime($lead->last_contact_at);
            $response_time = round(($contacted - $created) / 60);
        } else {
            $created = strtotime($lead->created_at);
            $now = current_time('timestamp');
            $no_response_time = round(($now - $created) / 60);
        }

        foreach ($rules as $rule) {
            $condition_met = false;
            $value_to_check = '';

            switch ($rule->condition_field) {
                case 'country':
                    // Assuming country_id maps to a code or we check ID directly. 
                    // For now, let's assume we need to fetch country code or use ID.
                    // Simplified: checking against ID or if we had a country code field.
                    // Let's assume the rule value matches what's in the DB (ID or Code).
                    // If rule says 'SA,AE' and DB has IDs, this might mismatch. 
                    // For this implementation, I'll assume the rule value is compatible with the column value.
                    $value_to_check = $lead->country_id;
                    break;
                case 'source':
                    $value_to_check = $lead->source_id;
                    break;
                case 'response_time':
                    $value_to_check = $response_time;
                    break;
                case 'no_response_time':
                    $value_to_check = $no_response_time;
                    break;
                case 'interactions_count':
                    $value_to_check = $interactions_count;
                    break;
                case 'phone':
                    $value_to_check = $lead->phone;
                    break;
                case 'email':
                    $value_to_check = $lead->email;
                    break;
                case 'budget':
                    // Assuming budget is in custom_fields or notes, or a specific column if added.
                    // The user request said "Budget mentioned". I'll check custom_fields JSON.
                    $custom_fields = json_decode($lead->custom_fields, true);
                    $value_to_check = isset($custom_fields['budget']) ? $custom_fields['budget'] : '';
                    break;
                case 'tags':
                    // Assuming tags are in custom_fields or similar
                    $custom_fields = json_decode($lead->custom_fields, true);
                    $value_to_check = isset($custom_fields['tags']) ? $custom_fields['tags'] : '';
                    break;
                case 'inquiries_count':
                    $value_to_check = $inquiries_count;
                    break;
                case 'status':
                    // We might need to map status_id to status_code
                    $status_code = $wpdb->get_var($wpdb->prepare("SELECT status_code FROM {$wpdb->prefix}aq_leads_status WHERE id = %d", $lead->status_id));
                    $value_to_check = $status_code;
                    break;
                case 'validation_status':
                    // Assuming custom field for now
                    $custom_fields = json_decode($lead->custom_fields, true);
                    $value_to_check = isset($custom_fields['validation_status']) ? $custom_fields['validation_status'] : '';
                    break;
            }

            // Evaluate Condition
            switch ($rule->condition_operator) {
                case 'equals':
                    $condition_met = ($value_to_check == $rule->condition_value);
                    break;
                case 'not_equals':
                    $condition_met = ($value_to_check != $rule->condition_value);
                    break;
                case 'contains':
                    $condition_met = (strpos((string) $value_to_check, (string) $rule->condition_value) !== false);
                    break;
                case 'greater_than':
                    $condition_met = ((float) $value_to_check > (float) $rule->condition_value);
                    break;
                case 'less_than':
                    $condition_met = ((float) $value_to_check < (float) $rule->condition_value);
                    break;
                case 'in_list':
                    $list = array_map('trim', explode(',', $rule->condition_value));
                    // Special handling for country codes vs IDs if needed, but for now direct check
                    $condition_met = in_array($value_to_check, $list);
                    break;
            }

            if ($condition_met) {
                if ($rule->rule_type == 'add') {
                    $score += (int) $rule->score_points;
                } elseif ($rule->rule_type == 'subtract') {
                    $score -= (int) $rule->score_points;
                } elseif ($rule->rule_type == 'set') {
                    $score = (int) $rule->score_points;
                }
                $applied_rules[] = $rule->rule_name;
            }
        }

        // Clamp score 0-100
        $score = max(0, min(100, $score));

        // Determine Rating
        $rating = self::get_rating_from_score($score);

        // Update Lead if changed
        if ($score != $lead->lead_score || $rating != $lead->lead_rating) {
            self::log_score_change($lead_id, $lead->lead_score, $score, $lead->lead_rating, $rating, implode(', ', $applied_rules));

            $wpdb->update(
                "{$wpdb->prefix}aq_leads",
                array(
                    'lead_score' => $score,
                    'lead_rating' => $rating,
                    'score_updated_at' => current_time('mysql')
                ),
                array('id' => $lead_id),
                array('%d', '%s', '%s'),
                array('%d')
            );

            /**
             * Fires after lead score has changed.
             *
             * @since 1.0.0
             *
             * @param int $lead_id Lead ID.
             * @param int $old_score Old score.
             * @param int $score New score.
             */
            do_action('aqop_lead_score_changed', $lead_id, $lead->lead_score, $score);
        }

        return $score;
    }

    /**
     * Get rating from score.
     *
     * @param int $score
     * @return string
     */
    public static function get_rating_from_score($score)
    {
        if ($score >= 80)
            return 'hot';
        if ($score >= 60)
            return 'warm';
        if ($score >= 40)
            return 'qualified';
        if ($score >= 20)
            return 'cold';
        return 'not_interested';
    }

    /**
     * Log score change.
     *
     * @param int $lead_id
     * @param int $old_score
     * @param int $new_score
     * @param string $old_rating
     * @param string $new_rating
     * @param string $reason
     */
    private static function log_score_change($lead_id, $old_score, $new_score, $old_rating, $new_rating, $reason)
    {
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}aq_lead_score_history",
            array(
                'lead_id' => $lead_id,
                'previous_score' => $old_score,
                'new_score' => $new_score,
                'previous_rating' => $old_rating,
                'new_rating' => $new_rating,
                'change_reason' => substr($reason, 0, 255),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Bulk recalculate scores.
     *
     * @param array $lead_ids
     * @return int Number of leads processed.
     */
    public static function bulk_recalculate($lead_ids)
    {
        $count = 0;
        foreach ($lead_ids as $lead_id) {
            if (self::calculate_score($lead_id) !== false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get score history.
     * 
     * @param int $lead_id
     * @return array
     */
    public static function get_score_history($lead_id)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aq_lead_score_history WHERE lead_id = %d ORDER BY created_at DESC",
            $lead_id
        ));
    }
}
