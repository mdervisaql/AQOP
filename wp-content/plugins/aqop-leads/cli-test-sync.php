<?php
/**
 * CLI Test Script for Airtable Sync
 *
 * Usage:
 *   php cli-test-sync.php          - Sync 3 records (default)
 *   php cli-test-sync.php 10       - Sync 10 records
 *   php cli-test-sync.php all      - Sync all records
 *
 * @package AQOP_Leads
 */

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         AQOP Airtable Sync - CLI Test Script              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get record limit from command line argument
$limit = isset($argv[1]) ? $argv[1] : 3;
if ($limit === 'all') {
    $limit = 0; // 0 means no limit
    echo "📊 Mode: Sync ALL records\n";
} else {
    $limit = intval($limit);
    echo "📊 Mode: Sync {$limit} records\n";
}

// Find WordPress root
// Try different relative paths based on where this script might be run from
$possible_wp_paths = array(
    __DIR__ . '/../../../../wp-load.php',           // From plugin directory
    __DIR__ . '/../../../wp-load.php',              // From wp-content/plugins
    dirname(__DIR__, 4) . '/wp-load.php',           // Alternative method
    '/var/www/html/wp-load.php',                    // Common server path
);

$wp_load_path = null;
foreach ($possible_wp_paths as $path) {
    if (file_exists($path)) {
        $wp_load_path = $path;
        break;
    }
}

if (!$wp_load_path) {
    echo "❌ ERROR: Could not find WordPress installation.\n";
    echo "   Searched paths:\n";
    foreach ($possible_wp_paths as $path) {
        echo "   - {$path}\n";
    }
    echo "\n   Make sure to run this script from the plugin directory.\n";
    exit(1);
}

echo "📂 Loading WordPress from: {$wp_load_path}\n";

// Load WordPress
define('WP_USE_THEMES', false);
require_once($wp_load_path);

echo "✅ WordPress loaded successfully.\n";

// Check if plugin is active
if (!class_exists('AQOP_Airtable_Sync')) {
    // Try to load the class manually
    $sync_class_path = __DIR__ . '/includes/class-airtable-sync.php';
    if (file_exists($sync_class_path)) {
        require_once($sync_class_path);
        echo "📦 Loaded AQOP_Airtable_Sync class manually.\n";
    } else {
        echo "❌ ERROR: AQOP_Airtable_Sync class not found.\n";
        echo "   Looked for: {$sync_class_path}\n";
        exit(1);
    }
}

// Check Airtable configuration
$api_key = get_option('aqop_airtable_token', '');
$base_id = get_option('aqop_airtable_base_id', '');
$table_name = get_option('aqop_airtable_table_name', '');

echo "\n";
echo "🔧 Configuration Check:\n";
echo "   API Key: " . (empty($api_key) ? '❌ NOT SET' : '✅ Set (' . substr($api_key, 0, 10) . '...)') . "\n";
echo "   Base ID: " . (empty($base_id) ? '❌ NOT SET' : '✅ ' . $base_id) . "\n";
echo "   Table:   " . (empty($table_name) ? '❌ NOT SET' : '✅ ' . $table_name) . "\n";

if (empty($api_key) || empty($base_id) || empty($table_name)) {
    echo "\n❌ ERROR: Airtable configuration incomplete. Please configure in WordPress admin.\n";
    exit(1);
}

// Check field mappings
$mappings = get_option('aqop_airtable_field_mapping', array());
if (is_string($mappings)) {
    $mappings = json_decode($mappings, true) ?: array();
}

echo "\n📋 Field Mappings (" . count($mappings) . " total):\n";
foreach ($mappings as $mapping) {
    $auto_create = isset($mapping['auto_create']) && $mapping['auto_create'] ? ' [auto_create]' : '';
    echo "   {$mapping['airtable_field']} → {$mapping['wp_field']}{$auto_create}\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "                    STARTING SYNC\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Get leads count before sync
global $wpdb;
$leads_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads");
echo "📊 Current leads in database: {$leads_before}\n\n";

// Create sync instance and run
$sync = new AQOP_Airtable_Sync();

// If we have a limit, we need to do a chunked sync
if ($limit > 0) {
    echo "🔄 Fetching up to {$limit} records from Airtable...\n\n";

    // Use sync_chunk method with page size = limit
    $result = $sync->sync_chunk($limit, '');
} else {
    echo "🔄 Running full sync...\n\n";
    $result = $sync->run();
}

echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "                    SYNC RESULTS\n";
echo "════════════════════════════════════════════════════════════\n\n";

if (isset($result['success']) && $result['success']) {
    echo "✅ SYNC SUCCESSFUL!\n\n";
} else {
    echo "⚠️  SYNC COMPLETED WITH ISSUES\n\n";
    if (isset($result['message'])) {
        echo "   Message: {$result['message']}\n\n";
    }
}

// Display statistics
echo "📊 Statistics:\n";
echo "   Records Processed: " . ($result['leads_processed'] ?? 0) . "\n";
echo "   Leads Created:     " . ($result['leads_created'] ?? 0) . "\n";
echo "   Leads Updated:     " . ($result['leads_updated'] ?? 0) . "\n";
echo "   Countries Created: " . ($result['countries_created'] ?? 0) . "\n";
echo "   Campaigns Created: " . ($result['campaigns_created'] ?? 0) . "\n";

// Show any errors
if (!empty($result['errors'])) {
    echo "\n❌ Errors:\n";
    foreach ($result['errors'] as $error) {
        echo "   - {$error}\n";
    }
}

// Get recently synced leads
echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "                 RECENTLY SYNCED LEADS\n";
echo "════════════════════════════════════════════════════════════\n\n";

$recent_leads = $wpdb->get_results("
    SELECT l.id, l.name, l.email, l.country_id, l.campaign_id, l.priority, l.airtable_record_id,
           c.country_name_en as country_name,
           camp.name as campaign_name
    FROM {$wpdb->prefix}aq_leads l
    LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON l.country_id = c.id
    LEFT JOIN {$wpdb->prefix}aq_leads_campaigns camp ON l.campaign_id = camp.id
    ORDER BY l.id DESC
    LIMIT 10
");

if (empty($recent_leads)) {
    echo "   No leads found in database.\n";
} else {
    echo str_pad("ID", 8) . str_pad("Name", 25) . str_pad("Country", 15) . str_pad("Campaign", 25) . "Airtable ID\n";
    echo str_repeat("-", 100) . "\n";

    foreach ($recent_leads as $lead) {
        $name = mb_substr($lead->name ?? 'N/A', 0, 22);
        $country = $lead->country_name ?? ($lead->country_id ? "ID:{$lead->country_id}" : 'NULL');
        $campaign = mb_substr($lead->campaign_name ?? ($lead->campaign_id ? "ID:{$lead->campaign_id}" : 'NULL'), 0, 22);
        $airtable_id = $lead->airtable_record_id ?? 'N/A';

        echo str_pad($lead->id, 8);
        echo str_pad($name, 25);
        echo str_pad($country, 15);
        echo str_pad($campaign, 25);
        echo $airtable_id . "\n";
    }
}

// Final summary
$leads_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads");
$new_leads = $leads_after - $leads_before;

echo "\n";
echo "════════════════════════════════════════════════════════════\n";
echo "                       SUMMARY\n";
echo "════════════════════════════════════════════════════════════\n\n";
echo "   Leads before sync: {$leads_before}\n";
echo "   Leads after sync:  {$leads_after}\n";
echo "   New leads added:   {$new_leads}\n";

// Check for NULL country/campaign issues
$null_country = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads WHERE country_id IS NULL AND airtable_record_id IS NOT NULL");
$null_campaign = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aq_leads WHERE campaign_id IS NULL AND airtable_record_id IS NOT NULL");

echo "\n⚠️  Potential Issues:\n";
echo "   Leads with NULL country_id:  {$null_country}\n";
echo "   Leads with NULL campaign_id: {$null_campaign}\n";

if ($null_country > 0 || $null_campaign > 0) {
    echo "\n   💡 TIP: Check that your field mappings include:\n";
    echo "      - A Lookup field for Country → country_id (with auto_create)\n";
    echo "      - A Lookup field for Campaign → campaign_id (with auto_create)\n";
    echo "      Make sure to use LOOKUP fields, not LINKED RECORD fields.\n";
}

echo "\n✅ Test complete!\n\n";
