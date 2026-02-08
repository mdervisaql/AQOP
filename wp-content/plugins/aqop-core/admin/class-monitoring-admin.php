<?php
/**
 * Monitoring Admin Class
 *
 * WordPress admin interface for Operations Center monitoring.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Monitoring_Admin
{
    /**
     * Initialize admin.
     *
     * @since 1.0.0
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Add admin menu page.
     *
     * @since 1.0.0
     */
    public static function add_menu_page()
    {
        add_menu_page(
            __('Operations Center', 'aqop-core'),
            __('Operations Center', 'aqop-core'),
            'manage_options',
            'aqop-operations-center',
            array(__CLASS__, 'render_page'),
            'dashicons-visibility',
            3
        );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_scripts($hook)
    {
        if ('toplevel_page_aqop-operations-center' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'aqop-monitoring-admin',
            AQOP_PLUGIN_URL . 'admin/css/monitoring-admin.css',
            array(),
            AQOP_VERSION
        );

        wp_enqueue_script(
            'aqop-monitoring-admin',
            AQOP_PLUGIN_URL . 'admin/js/monitoring-admin.js',
            array('jquery'),
            AQOP_VERSION,
            true
        );

        wp_localize_script(
            'aqop-monitoring-admin',
            'aqopMonitoring',
            array(
                'apiUrl' => rest_url('aqop/v1/monitoring'),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );
    }

    /**
     * Render admin page.
     *
     * @since 1.0.0
     */
    public static function render_page()
    {
        // Cleanup stale sessions
        AQOP_Session_Manager::cleanup_stale_sessions();

        // Get active users
        $active_users = AQOP_Session_Manager::get_active_users();
        $total_active = count($active_users);

        // Get recent activity
        $recent_activity = AQOP_Activity_Tracker::get_recent_activity(array('limit' => 20));

        ?>
        <div class="wrap aqop-operations-center">
            <h1>
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Operations Center', 'aqop-core'); ?>
            </h1>

            <!-- Stats Cards -->
            <div class="aqop-stats-grid">
                <div class="aqop-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="active-users-count"><?php echo $total_active; ?></div>
                        <div class="stat-label"><?php _e('Active Users', 'aqop-core'); ?></div>
                    </div>
                    <div class="stat-badge live-badge">
                        <span class="pulse-dot"></span>
                        <?php _e('LIVE', 'aqop-core'); ?>
                    </div>
                </div>

                <div class="aqop-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="activity-count">-</div>
                        <div class="stat-label"><?php _e('Actions (Last Hour)', 'aqop-core'); ?></div>
                    </div>
                </div>

                <div class="aqop-stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-plugins"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="module-count">-</div>
                        <div class="stat-label"><?php _e('Active Modules', 'aqop-core'); ?></div>
                    </div>
                </div>
            </div>

            <div class="aqop-monitoring-grid">
                <!-- Active Users Panel -->
                <div class="aqop-panel">
                    <div class="panel-header">
                        <h2><?php _e('Active Users', 'aqop-core'); ?></h2>
                        <button class="button button-small" id="refresh-users">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'aqop-core'); ?>
                        </button>
                    </div>
                    <div class="panel-content">
                        <div id="active-users-list">
                            <?php if (empty($active_users)): ?>
                                <div class="no-data">
                                    <span class="dashicons dashicons-info"></span>
                                    <p><?php _e('No active users at the moment', 'aqop-core'); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="users-table">
                                    <?php foreach ($active_users as $user):
                                        $user_data = get_userdata($user->user_id);
                                        $role = !empty($user_data->roles) ? $user_data->roles[0] : 'subscriber';
                                        $duration = self::format_duration($user->session_duration);
                                        ?>
                                        <div class="user-row">
                                            <div class="user-avatar">
                                                <?php echo get_avatar($user->user_id, 40); ?>
                                            </div>
                                            <div class="user-info">
                                                <div class="user-name"><?php echo esc_html($user->display_name); ?></div>
                                                <div class="user-meta">
                                                    <span class="user-role"><?php echo esc_html($role); ?></span>
                                                    <?php if ($user->current_module): ?>
                                                        <span class="separator">•</span>
                                                        <span class="user-module"><?php echo esc_html($user->current_module); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="user-activity">
                                                <div class="activity-time"><?php echo esc_html($duration); ?></div>
                                                <div class="activity-label"><?php _e('Session', 'aqop-core'); ?></div>
                                            </div>
                                            <div class="user-status">
                                                <span class="status-indicator active"></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Panel -->
                <div class="aqop-panel">
                    <div class="panel-header">
                        <h2><?php _e('Recent Activity', 'aqop-core'); ?></h2>
                        <button class="button button-small" id="refresh-activity">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'aqop-core'); ?>
                        </button>
                    </div>
                    <div class="panel-content">
                        <div id="recent-activity-list">
                            <?php if (empty($recent_activity)): ?>
                                <div class="no-data">
                                    <span class="dashicons dashicons-info"></span>
                                    <p><?php _e('No recent activity', 'aqop-core'); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="activity-feed">
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <?php echo self::get_action_icon($activity->action_type); ?>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-user"><?php echo esc_html($activity->user_name); ?></div>
                                                <div class="activity-action">
                                                    <?php echo esc_html(self::format_action($activity->action_type)); ?></div>
                                                <div class="activity-module"><?php echo esc_html($activity->module_code); ?></div>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo human_time_diff(strtotime($activity->created_at), current_time('timestamp')); ?>
                                                <?php _e('ago', 'aqop-core'); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Distribution Chart -->
            <div class="aqop-panel">
                <div class="panel-header">
                    <h2><?php _e('Module Distribution', 'aqop-core'); ?></h2>
                </div>
                <div class="panel-content">
                    <div id="module-distribution-chart">
                        <canvas id="module-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Format session duration.
     *
     * @since  1.0.0
     * @param  int $seconds Duration in seconds.
     * @return string Formatted duration.
     */
    private static function format_duration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        } else {
            return sprintf('%dm', $minutes);
        }
    }

    /**
     * Get action icon.
     *
     * @since  1.0.0
     * @param  string $action_type Action type.
     * @return string Icon HTML.
     */
    private static function get_action_icon($action_type)
    {
        $icons = array(
            'page_view' => 'dashicons-visibility',
            'api_call' => 'dashicons-rest-api',
            'lead_created' => 'dashicons-plus',
            'lead_updated' => 'dashicons-edit',
            'feedback_created' => 'dashicons-feedback',
            'default' => 'dashicons-admin-generic',
        );

        $icon_class = isset($icons[$action_type]) ? $icons[$action_type] : $icons['default'];

        return '<span class="dashicons ' . $icon_class . '"></span>';
    }

    /**
     * Format action type.
     *
     * @since  1.0.0
     * @param  string $action_type Action type.
     * @return string Formatted action.
     */
    private static function format_action($action_type)
    {
        $actions = array(
            'page_view' => __('Viewed page', 'aqop-core'),
            'api_call' => __('API call', 'aqop-core'),
            'lead_created' => __('Created lead', 'aqop-core'),
            'lead_updated' => __('Updated lead', 'aqop-core'),
            'feedback_created' => __('Submitted feedback', 'aqop-core'),
        );

        return isset($actions[$action_type]) ? $actions[$action_type] : ucfirst(str_replace('_', ' ', $action_type));
    }
}
