<?php
/**
 * Airtable Connection Test
 * 
 * Simple test file to verify Airtable settings and connection.
 * Access via: /wp-content/plugins/aqop-leads/test-airtable.php
 * 
 * Delete this file after debugging!
 * 
 * @package AQOP_Leads
 */

// Load WordPress
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die('Error: Could not find wp-load.php');
}
require_once $wp_load_path;

// Security check - admin only
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an admin to view this page.');
}

// Get Airtable settings
$api_key = get_option('aqop_airtable_token', '');
$base_id = get_option('aqop_airtable_base_id', '');
$table_name = get_option('aqop_airtable_table_name', '');
$last_sync = get_option('aqop_airtable_last_sync', '');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Airtable Connection Test - AQOP Leads</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; background: #f5f5f5; }
        .card { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #1d2327; margin-top: 0; }
        h2 { color: #2271b1; margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: 500; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        pre { background: #f8f9fa; padding: 15px; overflow-x: auto; border-radius: 4px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; }
        th { color: #646970; font-weight: 500; width: 200px; }
        .btn { display: inline-block; padding: 10px 20px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🔌 Airtable Connection Test</h1>
    
    <div class="warning">
        ⚠️ <strong>Security Notice:</strong> Delete this file after debugging is complete!
    </div>
    
    <!-- Settings Check -->
    <div class="card">
        <h2>📋 Settings Status</h2>
        <table>
            <tr>
                <th>API Token</th>
                <td>
                    <?php if (!empty($api_key)): ?>
                        <span class="status status-ok">✓ Set</span>
                        <br><small>Length: <?php echo strlen($api_key); ?> chars (starts with: <?php echo substr($api_key, 0, 8); ?>...)</small>
                    <?php else: ?>
                        <span class="status status-error">✗ Not Set</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Base ID</th>
                <td>
                    <?php if (!empty($base_id)): ?>
                        <span class="status status-ok">✓ Set</span>
                        <br><small><?php echo esc_html($base_id); ?></small>
                    <?php else: ?>
                        <span class="status status-error">✗ Not Set</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Table Name</th>
                <td>
                    <?php if (!empty($table_name)): ?>
                        <span class="status status-ok">✓ Set</span>
                        <br><small><?php echo esc_html($table_name); ?></small>
                    <?php else: ?>
                        <span class="status status-warning">⚠ Not Set (using default "Leads")</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Last Sync</th>
                <td>
                    <?php if (!empty($last_sync)): ?>
                        <span class="status status-ok"><?php echo esc_html($last_sync); ?></span>
                    <?php else: ?>
                        <span class="status status-warning">Never synced</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Connection Test -->
    <div class="card">
        <h2>🌐 API Connection Test</h2>
        <?php
        if (empty($api_key) || empty($base_id)) {
            echo '<p class="status status-error">Cannot test: Missing API credentials</p>';
        } else {
            $test_table = !empty($table_name) ? $table_name : 'Leads';
            $api_url = 'https://api.airtable.com/v0/' . $base_id . '/' . rawurlencode($test_table) . '?pageSize=1';
            
            echo '<p><strong>Testing URL:</strong> <code>' . esc_html($api_url) . '</code></p>';
            
            $start_time = microtime(true);
            
            $response = wp_remote_get($api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'timeout' => 30,
            ));
            
            $elapsed = round((microtime(true) - $start_time) * 1000);
            
            if (is_wp_error($response)) {
                echo '<p><span class="status status-error">✗ Connection Failed</span></p>';
                echo '<pre>Error: ' . esc_html($response->get_error_message()) . '</pre>';
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                echo '<p><strong>Response Time:</strong> ' . $elapsed . 'ms</p>';
                echo '<p><strong>HTTP Status:</strong> ' . $status_code . '</p>';
                
                if ($status_code === 200) {
                    echo '<p><span class="status status-ok">✓ Connection Successful!</span></p>';
                    
                    if (isset($data['records']) && is_array($data['records'])) {
                        $record_count = count($data['records']);
                        echo '<p><strong>Records returned:</strong> ' . $record_count . '</p>';
                        
                        if ($record_count > 0 && isset($data['records'][0]['fields'])) {
                            echo '<h3>Available Fields (from first record):</h3>';
                            echo '<pre>' . esc_html(print_r(array_keys($data['records'][0]['fields']), true)) . '</pre>';
                        }
                    }
                } else {
                    echo '<p><span class="status status-error">✗ API Error</span></p>';
                    if (isset($data['error'])) {
                        echo '<pre>Error: ' . esc_html($data['error']['message'] ?? 'Unknown error') . '</pre>';
                    }
                }
            }
        }
        ?>
    </div>
    
    <!-- Count Total Records -->
    <div class="card">
        <h2>📊 Record Count Estimate</h2>
        <?php
        if (!empty($api_key) && !empty($base_id)) {
            $test_table = !empty($table_name) ? $table_name : 'Leads';
            
            // Fetch with pagination to count all records
            $total_count = 0;
            $offset = '';
            $pages = 0;
            $max_pages = 20; // Safety limit
            
            do {
                $url = 'https://api.airtable.com/v0/' . $base_id . '/' . rawurlencode($test_table) . '?pageSize=100';
                if (!empty($offset)) {
                    $url .= '&offset=' . $offset;
                }
                
                $response = wp_remote_get($url, array(
                    'headers' => array('Authorization' => 'Bearer ' . $api_key),
                    'timeout' => 30,
                ));
                
                if (is_wp_error($response)) {
                    echo '<p class="status status-error">Failed to count records</p>';
                    break;
                }
                
                $data = json_decode(wp_remote_retrieve_body($response), true);
                
                if (isset($data['records'])) {
                    $total_count += count($data['records']);
                }
                
                $offset = isset($data['offset']) ? $data['offset'] : '';
                $pages++;
                
                // Brief pause to avoid rate limiting
                if (!empty($offset)) {
                    usleep(100000); // 0.1 second
                }
                
            } while (!empty($offset) && $pages < $max_pages);
            
            echo '<table>';
            echo '<tr><th>Total Records</th><td><strong>' . $total_count . '</strong>' . ($pages >= $max_pages ? ' (limited scan)' : '') . '</td></tr>';
            echo '<tr><th>API Pages Scanned</th><td>' . $pages . '</td></tr>';
            echo '<tr><th>Estimated Sync Time</th><td>~' . ceil($total_count / 50) . ' chunks × 20-30 seconds each</td></tr>';
            echo '</table>';
        }
        ?>
    </div>
    
    <p><a href="<?php echo admin_url('admin.php?page=aqop-settings'); ?>" class="btn">← Back to Settings</a></p>
    
</body>
</html>
