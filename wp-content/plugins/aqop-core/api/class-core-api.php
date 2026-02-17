<?php
/**
 * Core API Class
 *
 * Handles REST API endpoints for system health and statistics.
 *
 * @package AQOP_Core
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AQOP_Core_API
{

    /**
     * API Namespace.
     *
     * @var string
     */
    private $namespace = 'aqop/v1';

    /**
     * Register routes.
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        // System Health (GET /aqop/v1/system/health)
        register_rest_route(
            $this->namespace,
            '/system/health',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_system_health'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );

        // System Stats (GET /aqop/v1/system/stats)
        register_rest_route(
            $this->namespace,
            '/system/stats',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_system_stats'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );
    }

    /**
     * Get system health status.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_system_health($request)
    {
        $integrations = array('airtable', 'telegram', 'dropbox');
        $health_data = array();

        foreach ($integrations as $service) {
            $health_data[$service] = AQOP_Integrations_Hub::get_integration_status($service);
        }

        // Add Database Health
        global $wpdb;
        $db_health = array(
            'status' => 'ok',
            'message' => 'Connected',
            'latency' => 0,
        );

        $start = microtime(true);
        if ($wpdb->check_connection()) {
            $db_health['latency'] = round((microtime(true) - $start) * 1000, 2);
        } else {
            $db_health['status'] = 'error';
            $db_health['message'] = 'Connection failed';
        }
        $health_data['database'] = $db_health;

        // Add Queue Health (Simple check of pending cron events)
        $crons = _get_cron_array();
        $pending_jobs = 0;
        foreach ($crons as $timestamp => $cronhooks) {
            foreach ($cronhooks as $hook => $keys) {
                if (strpos($hook, 'aqop_process_') === 0) {
                    $pending_jobs++;
                }
            }
        }
        $health_data['queue'] = array(
            'status' => 'ok',
            'message' => $pending_jobs . ' pending jobs',
            'count' => $pending_jobs,
        );

        return rest_ensure_response($health_data);
    }

    /**
     * Get system statistics.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_system_stats($request)
    {
        $days = $request->get_param('days') ? absint($request->get_param('days')) : 7;

        $stats = array(
            'events_today' => AQOP_Event_Logger::count_events_today(),
            'errors_24h' => AQOP_Event_Logger::count_errors_24h(),
            'daily_trends' => AQOP_Event_Logger::get_stats(null, $days),
        );

        return rest_ensure_response($stats);
    }

    /**
     * Check admin permission.
     *
     * @return bool True if admin, false otherwise.
     */
    public function check_admin_permission()
    {
        return current_user_can('manage_options');
    }
}
