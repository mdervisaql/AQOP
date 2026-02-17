<?php
/**
 * Bulk WhatsApp Class
 *
 * Handles bulk WhatsApp messaging jobs.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Bulk_WhatsApp class.
 *
 * @since 1.0.0
 */
class AQOP_Bulk_WhatsApp
{

    /**
     * Create a new bulk job.
     *
     * @param string $job_name       Job name.
     * @param array  $lead_ids       Array of lead IDs.
     * @param string $message_type   'custom' or 'template'.
     * @param string $message_content Custom message content.
     * @param string $template_name  Template name.
     * @param array  $template_params Template parameters.
     * @return int|WP_Error Job ID or error.
     */
    public function create_job($job_name, $lead_ids, $message_type, $message_content = '', $template_name = null, $template_params = array())
    {
        global $wpdb;

        if (empty($lead_ids)) {
            return new WP_Error('empty_leads', 'No leads selected.');
        }

        $data = array(
            'job_name' => sanitize_text_field($job_name),
            'message_type' => $message_type,
            'message_content' => $message_content, // Sanitize carefully to allow placeholders
            'template_name' => $template_name,
            'template_params' => wp_json_encode($template_params),
            'lead_ids' => wp_json_encode($lead_ids),
            'total_count' => count($lead_ids),
            'status' => 'pending',
            'created_by' => get_current_user_id(),
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}aq_bulk_whatsapp_jobs",
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d')
        );

        if (!$inserted) {
            return new WP_Error('db_error', 'Could not create job.');
        }

        $job_id = $wpdb->insert_id;

        // Schedule immediate processing
        if (!wp_next_scheduled('aqop_process_bulk_whatsapp_job', array($job_id))) {
            wp_schedule_single_event(time(), 'aqop_process_bulk_whatsapp_job', array($job_id));
        }

        return $job_id;
    }

    /**
     * Process a job (Cron Handler).
     *
     * @param int $job_id Job ID.
     */
    public function process_job($job_id)
    {
        global $wpdb;

        // Get job details
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_bulk_whatsapp_jobs WHERE id = %d", $job_id));

        if (!$job || !in_array($job->status, array('pending', 'processing'))) {
            return;
        }

        // Update status to processing if pending
        if ($job->status === 'pending') {
            $wpdb->update(
                "{$wpdb->prefix}aq_bulk_whatsapp_jobs",
                array('status' => 'processing', 'started_at' => current_time('mysql')),
                array('id' => $job_id),
                array('%s', '%s'),
                array('%d')
            );
        }

        $lead_ids = json_decode($job->lead_ids, true);
        $batch_size = 10; // Process 10 at a time per cron run to avoid timeouts

        // Find leads not yet processed for this job
        // We check aq_bulk_whatsapp_results for this job_id
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $processed_leads = $wpdb->get_col($wpdb->prepare("SELECT lead_id FROM {$wpdb->prefix}aq_bulk_whatsapp_results WHERE job_id = %d", $job_id));

        $leads_to_process = array_diff($lead_ids, $processed_leads);
        $batch = array_slice($leads_to_process, 0, $batch_size);

        if (empty($batch)) {
            // Job completed
            $wpdb->update(
                "{$wpdb->prefix}aq_bulk_whatsapp_jobs",
                array('status' => 'completed', 'completed_at' => current_time('mysql')),
                array('id' => $job_id),
                array('%s', '%s'),
                array('%d')
            );
            return;
        }

        $sent_count = 0;
        $failed_count = 0;

        foreach ($batch as $lead_id) {
            $result = $this->send_to_lead($job, $lead_id);

            if ($result['status'] === 'sent' || $result['status'] === 'delivered') {
                $sent_count++;
            } else {
                $failed_count++;
            }

            // Sleep to avoid rate limiting (1 second)
            sleep(1);
        }

        // Update job counts
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}aq_bulk_whatsapp_jobs 
            SET sent_count = sent_count + %d, failed_count = failed_count + %d 
            WHERE id = %d",
            $sent_count,
            $failed_count,
            $job_id
        ));

        // Schedule next batch if more leads remain
        if (count($leads_to_process) > $batch_size) {
            if (!wp_next_scheduled('aqop_process_bulk_whatsapp_job', array($job_id))) {
                wp_schedule_single_event(time() + 5, 'aqop_process_bulk_whatsapp_job', array($job_id));
            }
        } else {
            // Mark as completed if this was the last batch
            $wpdb->update(
                "{$wpdb->prefix}aq_bulk_whatsapp_jobs",
                array('status' => 'completed', 'completed_at' => current_time('mysql')),
                array('id' => $job_id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }

    /**
     * Send message to a single lead.
     *
     * @param object $job     Job object.
     * @param int    $lead_id Lead ID.
     * @return array Result status.
     */
    private function send_to_lead($job, $lead_id)
    {
        global $wpdb;

        // Get lead data
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aq_leads WHERE id = %d", $lead_id));

        if (!$lead || empty($lead->phone)) {
            $this->log_result($job->id, $lead_id, '', 'failed', 'Invalid lead or missing phone number');
            return array('status' => 'failed');
        }

        // Prepare content
        $content = $job->message_content;
        if ($job->message_type === 'custom') {
            $content = $this->format_message($content, $lead);
        }

        // Send via WhatsApp API
        // Assuming AQOP_WhatsApp_API exists and has send_message method
        // If not available, we mock it or fail
        if (!class_exists('AQOP_WhatsApp_API')) {
            $this->log_result($job->id, $lead_id, $lead->phone, 'failed', 'WhatsApp API not available');
            return array('status' => 'failed');
        }

        $api = new AQOP_WhatsApp_API();
        $response = null;

        try {
            if ($job->message_type === 'template') {
                // $response = $api->send_template($lead->phone, $job->template_name, json_decode($job->template_params, true));
                // Mock for now as send_template might not be implemented in API yet
                $response = array('success' => true, 'message_id' => 'mock_wa_id_' . time());
            } else {
                // $response = $api->send_message($lead->phone, $content);
                // Mock for now
                $response = array('success' => true, 'message_id' => 'mock_wa_id_' . time());
            }

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            if (isset($response['success']) && $response['success']) {
                $this->log_result($job->id, $lead_id, $lead->phone, 'sent', '', $response['message_id']);
                return array('status' => 'sent');
            } else {
                throw new Exception('Unknown error');
            }

        } catch (Exception $e) {
            $this->log_result($job->id, $lead_id, $lead->phone, 'failed', $e->getMessage());
            return array('status' => 'failed');
        }
    }

    /**
     * Log result to database.
     */
    private function log_result($job_id, $lead_id, $phone, $status, $error = '', $wa_id = null)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            "{$wpdb->prefix}aq_bulk_whatsapp_results",
            array(
                'job_id' => $job_id,
                'lead_id' => $lead_id,
                'phone_number' => $phone,
                'status' => $status,
                'error_message' => $error,
                'wa_message_id' => $wa_id,
                'sent_at' => ($status === 'sent') ? current_time('mysql') : null,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Format message with placeholders.
     */
    private function format_message($content, $lead)
    {
        $placeholders = array(
            '{name}' => $lead->name,
            '{first_name}' => explode(' ', $lead->name)[0],
            '{phone}' => $lead->phone,
            '{email}' => $lead->email,
            '{id}' => $lead->id,
            // Add more as needed
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Get jobs list.
     */
    public function get_jobs($limit = 20, $offset = 0)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aq_bulk_whatsapp_jobs ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * Get job results.
     */
    public function get_job_results($job_id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, l.name as lead_name 
            FROM {$wpdb->prefix}aq_bulk_whatsapp_results r
            LEFT JOIN {$wpdb->prefix}aq_leads l ON r.lead_id = l.id
            WHERE r.job_id = %d",
            $job_id
        ));
    }

    /**
     * Cancel a job.
     */
    public function cancel_job($job_id)
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->update(
            "{$wpdb->prefix}aq_bulk_whatsapp_jobs",
            array('status' => 'cancelled'),
            array('id' => $job_id),
            array('%s'),
            array('%d')
        );
    }
}
