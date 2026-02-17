<?php
/**
 * Users REST API Controller
 *
 * Provides REST API endpoints for user management.
 *
 * @package AQOP_Core
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Users_API class.
 *
 * REST API controller for user management.
 *
 * @since 1.1.0
 */
class AQOP_Users_API
{

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'aqop/v1';

    /**
     * Register REST API routes.
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        // List users (GET /aqop/v1/users)
        register_rest_route(
            $this->namespace,
            '/users',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_users'),
                'permission_callback' => array($this, 'check_admin_permission'),
            )
        );

        // Update user (POST /aqop/v1/users/{id})
        register_rest_route(
            $this->namespace,
            '/users/(?P<id>\d+)',
            array(
                'methods' => WP_REST_Server::CREATABLE, // Using POST for updates
                'callback' => array($this, 'update_user'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        },
                        'sanitize_callback' => 'absint',
                    ),
                    'role' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'country_id' => array(
                        'required' => false,
                        'validate_callback' => function ($param) {
                            return is_numeric($param) || empty($param);
                        },
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

    /**
     * Get users list.
     *
     * @since 1.1.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_users($request)
    {
        $args = array(
            'orderby' => 'display_name',
            'order' => 'ASC',
        );

        $users = get_users($args);
        $data = array();

        foreach ($users as $user) {
            $country_id = get_user_meta($user->ID, 'aq_assigned_country', true);

            // Get country name if ID exists
            $country_name = '';
            if ($country_id) {
                global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $country_name = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT country_name_en FROM {$wpdb->prefix}aq_dim_countries WHERE id = %d",
                        $country_id
                    )
                );
            }

            $data[] = array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'roles' => $user->roles,
                'country_id' => $country_id ? (int) $country_id : null,
                'country_name' => $country_name,
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $data,
            )
        );
    }

    /**
     * Update user.
     *
     * @since 1.1.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function update_user($request)
    {
        $user_id = $request['id'];
        $params = $request->get_params();

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'aqop-core'),
                array('status' => 404)
            );
        }

        // Update Role
        if (isset($params['role']) && !empty($params['role'])) {
            // Remove all existing roles
            foreach ($user->roles as $role) {
                $user->remove_role($role);
            }
            // Add new role
            $user->add_role($params['role']);
        }

        // Update Country
        if (isset($params['country_id'])) {
            if (empty($params['country_id'])) {
                delete_user_meta($user_id, 'aq_assigned_country');
            } else {
                update_user_meta($user_id, 'aq_assigned_country', absint($params['country_id']));
            }
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('User updated successfully.', 'aqop-core'),
            )
        );
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
