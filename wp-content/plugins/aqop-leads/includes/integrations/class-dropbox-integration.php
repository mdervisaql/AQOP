<?php

/**
 * Dropbox Integration Handler
 *
 * Handles file uploads to Dropbox using the Dropbox API v2.
 *
 * @since      1.0.0
 * @package    AQOP_Leads
 * @subpackage AQOP_Leads/includes/integrations
 */

class AQOP_Dropbox_Integration
{

    /**
     * Dropbox Access Token
     *
     * @var string
     */
    private $access_token;

    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        // TODO: Retrieve this from settings in the future
        // For now, we use a placeholder or constant defined in wp-config.php
        $this->access_token = defined('AQOP_DROPBOX_ACCESS_TOKEN') ? AQOP_DROPBOX_ACCESS_TOKEN : '';
    }

    /**
     * Upload a file to Dropbox.
     *
     * @param array $file The file array from $_FILES.
     * @param int   $lead_id The ID of the lead.
     * @return array|WP_Error Response data or error.
     */
    public function upload_file($file, $lead_id)
    {
        if (empty($this->access_token)) {
            return new WP_Error('dropbox_error', 'Dropbox Access Token is not configured.');
        }

        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            return new WP_Error('invalid_file', 'Invalid file data.');
        }

        // Read file content
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            return new WP_Error('file_read_error', 'Could not read file content.');
        }

        // Generate filename: {lead_id}_{Y-m-d}_{H-i-s}_{random}.{ext}
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $timestamp = date('Y-m-d_H-i-s');
        $random = wp_rand(100, 999);
        $filename = "{$lead_id}_{$timestamp}_{$random}.{$ext}";

        // Dropbox Path: /{lead_id}/{filename}
        $dropbox_path = "/{$lead_id}/{$filename}";

        // Dropbox API Endpoint
        $url = 'https://content.dropboxapi.com/2/files/upload';

        // API Args
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode(array(
                    'path' => $dropbox_path,
                    'mode' => 'add',
                    'autorename' => true,
                    'mute' => false,
                    'strict_conflict' => false
                )),
            ),
            'body' => $file_content,
            'timeout' => 60, // Increase timeout for uploads
        );

        // Send Request
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($response_code !== 200) {
            $error_msg = isset($data['error_summary']) ? $data['error_summary'] : 'Unknown Dropbox error';
            return new WP_Error('dropbox_api_error', 'Dropbox API Error: ' . $error_msg);
        }

        return array(
            'success' => true,
            'path' => $data['path_display'],
            'name' => $data['name'],
            'id' => $data['id'],
            'url' => '', // We could generate a shared link here if needed
        );
    }
}
