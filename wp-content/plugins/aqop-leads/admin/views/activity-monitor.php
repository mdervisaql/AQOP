<?php
/**
 * Activity Monitor Admin View
 *
 * @package AQOP_Leads
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options') && !current_user_can('edit_others_posts')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'aqop-leads'));
}

// Get tracker instance
$tracker = new AQOP_Activity_Tracker();
global $wpdb;

// Handle actions
if (isset($_POST['aqop_action']) && $_POST['aqop_action'] === 'clear_history') {
    check_admin_referer('aqop_clear_history', 'aqop_nonce');
    $days = isset($_POST['days']) ? absint($_POST['days']) : 90;
    $deleted = $tracker->cleanup($days);
    echo '<div class="notice notice-success"><p>' . sprintf(__('History cleared. %d records deleted.', 'aqop-leads'), $deleted) . '</p></div>';
}

// Get current tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'logs';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Activity Monitor', 'aqop-leads'); ?></h1>

    <?php if ($active_tab === 'logs'): ?>
        <form method="post" style="display:inline-block; margin-left: 10px;">
            <?php wp_nonce_field('aqop_clear_history', 'aqop_nonce'); ?>
            <input type="hidden" name="aqop_action" value="clear_history">
            <input type="hidden" name="days" value="90">
            <button type="submit" class="button button-secondary"
                onclick="return confirm('<?php _e('Are you sure you want to delete logs older than 90 days?', 'aqop-leads'); ?>');">
                <?php _e('Clear Old History (> 90 days)', 'aqop-leads'); ?>
            </button>
        </form>
    <?php endif; ?>

    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper">
        <a href="?page=aqop-activity-monitor&tab=logs"
            class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Activity Logs', 'aqop-leads'); ?>
        </a>
        <a href="?page=aqop-activity-monitor&tab=users"
            class="nav-tab <?php echo $active_tab === 'users' ? 'nav-tab-active' : ''; ?>">
            <?php _e('User Stats', 'aqop-leads'); ?>
        </a>
    </nav>

    <div class="aqop-tab-content" style="margin-top: 20px;">
        <?php if ($active_tab === 'logs'): ?>
            <?php
            // Get filters
            $filter_user = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
            $filter_type = isset($_GET['activity_type']) ? sanitize_text_field($_GET['activity_type']) : '';
            $filter_search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
            $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
            $limit = 20;
            $offset = ($paged - 1) * $limit;

            // Get logs
            $logs_data = $tracker->get_logs(array(
                'user_id' => $filter_user,
                'activity_type' => $filter_type,
                'search' => $filter_search,
                'limit' => $limit,
                'offset' => $offset,
            ));

            $logs = $logs_data['items'];
            $total = $logs_data['total'];
            $total_pages = ceil($total / $limit);

            // Get unique activity types for filter
            $types = $wpdb->get_col("SELECT DISTINCT action_type FROM {$wpdb->prefix}aq_user_activity ORDER BY action_type ASC");
            ?>

            <!-- Filters -->
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <input type="hidden" name="tab" value="logs" />

                <div class="tablenav top">
                    <div class="alignleft actions">
                        <!-- Activity Type Filter -->
                        <select name="activity_type">
                            <option value=""><?php _e('All Activities', 'aqop-leads'); ?></option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- User Filter -->
                        <input type="number" name="user_id" placeholder="User ID"
                            value="<?php echo $filter_user ? esc_attr($filter_user) : ''; ?>" style="width: 100px;">

                        <input type="submit" class="button" value="<?php _e('Filter', 'aqop-leads'); ?>">
                    </div>

                    <div class="alignleft actions">
                        <input type="search" name="s" value="<?php echo esc_attr($filter_search); ?>"
                            placeholder="<?php _e('Search logs...', 'aqop-leads'); ?>">
                        <input type="submit" class="button" value="<?php _e('Search', 'aqop-leads'); ?>">
                    </div>

                    <!-- Pagination -->
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo sprintf(__('%d items', 'aqop-leads'), $total); ?></span>
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $paged
                        ));

                        if ($page_links) {
                            echo '<span class="pagination-links">' . $page_links . '</span>';
                        }
                        ?>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-date" style="width: 150px;">
                                <?php _e('Date', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-user" style="width: 150px;">
                                <?php _e('User', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-activity" style="width: 150px;">
                                <?php _e('Activity', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-details"><?php _e('Details', 'aqop-leads'); ?></th>
                            <th scope="col" class="manage-column column-ip" style="width: 120px;">
                                <?php _e('IP Address', 'aqop-leads'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($log->created_at); ?><br>
                                        <small
                                            style="color: #999;"><?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ago'; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($log->user_name); ?></strong><br>
                                        <small><a
                                                href="user-edit.php?user_id=<?php echo $log->user_id; ?>"><?php echo esc_html($log->user_email); ?></a></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo esc_attr($log->activity_type); ?>"
                                            style="background: #e5e5e5; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
                                            <?php echo esc_html(strtoupper(str_replace('_', ' ', $log->activity_type))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($log->activity_details)) {
                                            echo '<pre style="margin: 0; font-size: 10px; max-height: 100px; overflow: auto;">';
                                            echo esc_html(json_encode($log->activity_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                            echo '</pre>';
                                        } else {
                                            echo '<span style="color: #ccc;">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($log->ip_address); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5"><?php _e('No activity logs found.', 'aqop-leads'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

        <?php elseif ($active_tab === 'users'): ?>
            <?php
            // Get all users
            $users = get_users();

            // 1. Get Login Stats
            $login_stats = $wpdb->get_results("
                SELECT user_id, MAX(created_at) as last_login, COUNT(*) as login_count 
                FROM {$wpdb->prefix}aq_user_activity 
                WHERE action_type = 'login' 
                GROUP BY user_id
            ", OBJECT_K);

            // 2. Get Lead Edit Stats (from Event Logger)
            $edit_stats = $wpdb->get_results("
                SELECT user_id, COUNT(*) as edit_count 
                FROM {$wpdb->prefix}aq_events_log 
                WHERE event_type_id IN (SELECT id FROM {$wpdb->prefix}aq_dim_event_types WHERE event_code = 'lead_updated') 
                GROUP BY user_id
            ", OBJECT_K);

            // 3. Get Lead View Stats (from Activity Tracker)
            $view_stats = $wpdb->get_results("
                SELECT user_id, COUNT(*) as view_count 
                FROM {$wpdb->prefix}aq_user_activity 
                WHERE action_type = 'page_view' AND page_url LIKE '%page=aqop-leads-view%' 
                GROUP BY user_id
            ", OBJECT_K);

            // 4. Get Last Activity
            $last_activity_stats = $wpdb->get_results("
                SELECT user_id, MAX(created_at) as last_activity 
                FROM {$wpdb->prefix}aq_user_activity 
                GROUP BY user_id
            ", OBJECT_K);
            ?>

            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-user"><?php _e('User', 'aqop-leads'); ?></th>
                            <th scope="col" class="manage-column column-role"><?php _e('Role', 'aqop-leads'); ?></th>
                            <th scope="col" class="manage-column column-last-login"><?php _e('Last Login', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-logins"><?php _e('Total Logins', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-views"><?php _e('Leads Viewed', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-edits"><?php _e('Leads Edited', 'aqop-leads'); ?>
                            </th>
                            <th scope="col" class="manage-column column-last-activity">
                                <?php _e('Last Activity', 'aqop-leads'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'aqop-leads'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $uid = $user->ID;
                            $last_login = isset($login_stats[$uid]) ? $login_stats[$uid]->last_login : '-';
                            $login_count = isset($login_stats[$uid]) ? $login_stats[$uid]->login_count : 0;
                            $edit_count = isset($edit_stats[$uid]) ? $edit_stats[$uid]->edit_count : 0;
                            $view_count = isset($view_stats[$uid]) ? $view_stats[$uid]->view_count : 0;
                            $last_activity = isset($last_activity_stats[$uid]) ? $last_activity_stats[$uid]->last_activity : '-';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                    <small><?php echo esc_html($user->user_email); ?></small>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($user->roles)) {
                                        foreach ($user->roles as $role) {
                                            echo '<span class="badge" style="background: #f0f0f1; padding: 2px 6px; border-radius: 4px; margin-right: 4px;">' . esc_html(ucfirst($role)) . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo $last_login !== '-' ? esc_html($last_login) : '<span style="color: #ccc;">-</span>'; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($login_count); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($view_count); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($edit_count); ?>
                                </td>
                                <td>
                                    <?php
                                    if ($last_activity !== '-') {
                                        echo esc_html($last_activity) . '<br>';
                                        echo '<small style="color: #999;">' . human_time_diff(strtotime($last_activity), current_time('timestamp')) . ' ago</small>';
                                    } else {
                                        echo '<span style="color: #ccc;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?page=aqop-activity-monitor&tab=logs&user_id=<?php echo $uid; ?>"
                                        class="button button-small">
                                        <?php _e('View Logs', 'aqop-leads'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>