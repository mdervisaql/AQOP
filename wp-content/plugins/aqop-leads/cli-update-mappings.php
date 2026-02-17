<?php
/**
 * CLI Script to Update Airtable Field Mappings
 *
 * Usage:
 *   php cli-update-mappings.php
 *
 * @package AQOP_Leads
 */

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║      AQOP Airtable Sync - Update Field Mappings           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Find WordPress root
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
    exit(1);
}

echo "📂 Loading WordPress from: {$wp_load_path}\n";

// Load WordPress
define('WP_USE_THEMES', false);
require_once($wp_load_path);

echo "✅ WordPress loaded successfully.\n\n";

// Define the NEW mappings
$new_mappings = array(
    array(
        'airtable_field' => 'Name',
        'wp_field' => 'name',
        'auto_create' => false
    ),
    array(
        'airtable_field' => 'mail',
        'wp_field' => 'email',
        'auto_create' => false
    ),
    array(
        'airtable_field' => 'Phone number',
        'wp_field' => 'phone',
        'auto_create' => false
    ),
    array(
        'airtable_field' => 'Name EN',
        'wp_field' => 'country_id',
        'auto_create' => true
    ),
    array(
        'airtable_field' => 'Campaign Name',
        'wp_field' => 'campaign_id',
        'auto_create' => true
    ),
    array(
        'airtable_field' => 'Platform Name',
        'wp_field' => 'source_id',
        'auto_create' => true
    )
);

echo "📋 Updating mappings to:\n";
foreach ($new_mappings as $mapping) {
    $auto = $mapping['auto_create'] ? " [auto_create: YES]" : " [auto_create: NO]";
    echo "   {$mapping['airtable_field']} → {$mapping['wp_field']}{$auto}\n";
}

// Update the option
update_option('aqop_airtable_field_mapping', $new_mappings);

// Verify the update
$saved_mappings = get_option('aqop_airtable_field_mapping');

echo "\n";
if ($saved_mappings === $new_mappings) {
    echo "✅ SUCCESS: Mappings updated successfully!\n";
    echo "   Stored as proper PHP array.\n";
} else {
    echo "❌ ERROR: Failed to update mappings correctly.\n";
    print_r($saved_mappings);
}

echo "\n";
