<?php
/**
 * Feedback Admin Class
 *
 * Handles WordPress Admin interface for feedback management.
 *
 * @package AQOP_Feedback
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AQOP_Feedback_Admin
{

    /**
     * Initialize admin.
     *
     * @since 1.0.0
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public static function add_admin_menu()
    {
        add_menu_page(
            __('Feedback', 'aqop-feedback'),
            __('Feedback', 'aqop-feedback'),
            'manage_options',
            'aqop-feedback',
            array(__CLASS__, 'render_feedback_page'),
            'dashicons-feedback',
            30
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since 1.0.0
     */
    public static function enqueue_scripts($hook)
    {
        if ('toplevel_page_aqop-feedback' !== $hook) {
            return;
        }

        wp_enqueue_style('aqop-feedback-admin', AQOP_FEEDBACK_PLUGIN_URL . 'assets/admin.css', array(), AQOP_FEEDBACK_VERSION);
    }

    /**
     * Render feedback page.
     *
     * @since 1.0.0
     */
    public static function render_feedback_page()
    {
        // Get filter parameters.
        $module = isset($_GET['module']) ? sanitize_text_field($_GET['module']) : '';
        $status = isset($_GET['status']) ? absint($_GET['status']) : '';
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';

        // Query feedback.
        $args = array(
            'limit' => 100,
        );

        if ($module) {
            $args['module'] = $module;
        }
        if ($status) {
            $args['status'] = $status;
        }
        if ($type) {
            $args['type'] = $type;
        }
        if ($priority) {
            $args['priority'] = $priority;
        }

        $result = AQOP_Feedback_Manager::query_feedback($args);
        $feedback_list = $result['feedback'];

        // Get statuses for filter.
        global $wpdb;
        $statuses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aq_feedback_status ORDER BY status_order");

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Filters -->
            <div class="tablenav top">
                <form method="get" action="">
                    <input type="hidden" name="page" value="aqop-feedback">

                    <select name="module">
                        <option value=""><?php esc_html_e('All Modules', 'aqop-feedback'); ?></option>
                        <option value="leads" <?php selected($module, 'leads'); ?>>Leads</option>
                        <option value="feedback" <?php selected($module, 'feedback'); ?>>Feedback</option>
                        <option value="general" <?php selected($module, 'general'); ?>>General</option>
                    </select>

                    <select name="status">
                        <option value=""><?php esc_html_e('All Statuses', 'aqop-feedback'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo esc_attr($s->id); ?>" <?php selected($status, $s->id); ?>>
                                <?php echo esc_html($s->status_name_en); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="type">
                        <option value=""><?php esc_html_e('All Types', 'aqop-feedback'); ?></option>
                        <option value="bug" <?php selected($type, 'bug'); ?>>Bug</option>
                        <option value="feature_request" <?php selected($type, 'feature_request'); ?>>Feature Request</option>
                        <option value="improvement" <?php selected($type, 'improvement'); ?>>Improvement</option>
                        <option value="question" <?php selected($type, 'question'); ?>>Question</option>
                    </select>

                    <select name="priority">
                        <option value=""><?php esc_html_e('All Priorities', 'aqop-feedback'); ?></option>
                        <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                        <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                        <option value="high" <?php selected($priority, 'high'); ?>>High</option>
                        <option value="critical" <?php selected($priority, 'critical'); ?>>Critical</option>
                    </select>

                    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'aqop-feedback'); ?>">
                </form>
            </div>

            <!-- Feedback Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Title', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Module', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Type', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Priority', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Status', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('User', 'aqop-feedback'); ?></th>
                        <th><?php esc_html_e('Created', 'aqop-feedback'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedback_list)): ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e('No feedback found.', 'aqop-feedback'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedback_list as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($item->title); ?></strong>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a
                                                href="<?php echo esc_url(admin_url('admin.php?page=aqop-feedback&action=view&id=' . $item->id)); ?>">
                                                <?php esc_html_e('View', 'aqop-feedback'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($item->module_code); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo esc_attr($item->feedback_type); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $item->feedback_type))); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-priority-<?php echo esc_attr($item->priority); ?>">
                                        <?php echo esc_html(ucfirst($item->priority)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background-color: <?php echo esc_attr($item->status_color); ?>; color: white; padding: 3px 8px; border-radius: 3px;">
                                        <?php echo esc_html($item->status_name_en); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($item->user_name); ?></td>
                                <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($item->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }

            .badge-bug {
                background-color: #f56565;
                color: white;
            }

            .badge-feature_request {
                background-color: #4299e1;
                color: white;
            }

            .badge-improvement {
                background-color: #ed8936;
                color: white;
            }

            .badge-question {
                background-color: #718096;
                color: white;
            }

            .badge-priority-low {
                background-color: #48bb78;
                color: white;
            }

            .badge-priority-medium {
                background-color: #ed8936;
                color: white;
            }

            .badge-priority-high {
                background-color: #f56565;
                color: white;
            }

            .badge-priority-critical {
                background-color: #9b2c2c;
                color: white;
            }
        </style>
        <?php
    }
}
