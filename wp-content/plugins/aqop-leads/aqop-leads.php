<?php
/**
 * Plugin Name: Operation Platform - Leads Module
 * Plugin URI: https://aqleeat.com
 * Description: Comprehensive leads management system with analytics, Airtable sync, and notifications
 * Version: 1.0.0
 * Author: Muhammed Derviş
 * Author URI: https://aqleeat.com
 * Requires Plugins: aqop-core
 * Text Domain: aqop-leads
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AQOP_Leads
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Check if Core is active.
if (!class_exists('AQOP_Core')) {
	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error">
			<p>
				<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Plugin name */
							__('<strong>Operation Platform - Leads Module</strong> requires <strong>Operation Platform Core</strong> to be installed and activated.', 'aqop-leads')
						)
					);
					?>
			</p>
		</div>
		<?php
		}
	);
	return;
}

// Define plugin constants.
define('AQOP_LEADS_VERSION', '1.0.0');
define('AQOP_LEADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AQOP_LEADS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AQOP_LEADS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load dependencies.
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-activator.php';
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-core.php';

/**
 * Activation hook.
 *
 * Runs when the plugin is activated.
 */
register_activation_hook(__FILE__, array('AQOP_Leads_Activator', 'activate'));

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated.
 */
register_deactivation_hook(__FILE__, array('AQOP_Leads_Deactivator', 'deactivate'));

/**
 * Initialize the Leads Module.
 *
 * Loads after core platform (priority 20).
 *
 * @since 1.0.0
 */
function aqop_leads_init()
{
	AQOP_Leads_Core::get_instance();
}
add_action('plugins_loaded', 'aqop_leads_init', 20);

/**
 * Run database upgrades.
 *
 * Checks for and adds any missing database columns.
 *
 * @since 1.0.1
 */
function aqop_leads_upgrade_db()
{
	require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-leads-installer.php';
	AQOP_Leads_Installer::upgrade_database();
}
add_action('plugins_loaded', 'aqop_leads_upgrade_db', 21);

/**
 * Register custom cron schedules for Airtable auto-sync.
 *
 * @since 1.0.2
 * @param array $schedules Existing cron schedules.
 * @return array Modified schedules.
 */
function aqop_airtable_cron_schedules($schedules)
{
	$schedules['every_15_minutes'] = array(
		'interval' => 900,
		'display' => __('Every 15 Minutes', 'aqop-leads'),
	);
	$schedules['every_30_minutes'] = array(
		'interval' => 1800,
		'display' => __('Every 30 Minutes', 'aqop-leads'),
	);
	$schedules['every_hour'] = array(
		'interval' => 3600,
		'display' => __('Every Hour', 'aqop-leads'),
	);
	$schedules['every_6_hours'] = array(
		'interval' => 21600,
		'display' => __('Every 6 Hours', 'aqop-leads'),
	);
	return $schedules;
}
add_filter('cron_schedules', 'aqop_airtable_cron_schedules');

/**
 * Schedule Airtable auto-sync cron job.
 *
 * @since 1.0.2
 */
function aqop_schedule_airtable_sync()
{
	// Check if auto-sync is enabled
	$auto_sync_enabled = get_option('aqop_airtable_auto_sync_enabled', false);

	if (!$auto_sync_enabled) {
		// If disabled, clear any existing schedule
		$timestamp = wp_next_scheduled('aqop_airtable_auto_sync_hook');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'aqop_airtable_auto_sync_hook');
		}
		return;
	}

	// Get configured interval
	$interval = get_option('aqop_airtable_auto_sync_interval', 'every_30_minutes');

	// Check if already scheduled with the correct interval
	$timestamp = wp_next_scheduled('aqop_airtable_auto_sync_hook');

	if ($timestamp) {
		// Check if interval has changed
		$current_schedule = wp_get_schedule('aqop_airtable_auto_sync_hook');
		if ($current_schedule !== $interval) {
			// Interval changed, reschedule
			wp_unschedule_event($timestamp, 'aqop_airtable_auto_sync_hook');
			wp_schedule_event(time(), $interval, 'aqop_airtable_auto_sync_hook');
		}
	} else {
		// Not scheduled, create new schedule
		wp_schedule_event(time(), $interval, 'aqop_airtable_auto_sync_hook');
	}
}
add_action('init', 'aqop_schedule_airtable_sync');

/**
 * Execute Airtable auto-sync.
 *
 * @since 1.0.2
 */
function aqop_execute_airtable_auto_sync()
{
	// Double-check if auto-sync is still enabled
	if (!get_option('aqop_airtable_auto_sync_enabled', false)) {
		return;
	}

	// Check if Airtable credentials are configured
	$api_key = get_option('aqop_airtable_token', '');
	$base_id = get_option('aqop_airtable_base_id', '');
	$table_name = get_option('aqop_airtable_table_name', '');

	if (empty($api_key) || empty($base_id) || empty($table_name)) {
		error_log('AQOP Auto-Sync: Skipped - Airtable credentials not configured');
		return;
	}

	error_log('AQOP Auto-Sync: Starting automatic Airtable sync at ' . current_time('mysql'));

	try {
		require_once AQOP_LEADS_PLUGIN_DIR . 'includes/class-airtable-sync.php';

		if (!class_exists('AQOP_Airtable_Sync')) {
			error_log('AQOP Auto-Sync: AQOP_Airtable_Sync class not found');
			return;
		}

		$sync = new AQOP_Airtable_Sync();
		$result = $sync->sync_from_airtable();

		// Save last auto-sync timestamp and result
		update_option('aqop_airtable_last_auto_sync', current_time('mysql'));
		update_option('aqop_airtable_last_auto_sync_result', $result);

		if ($result['success']) {
			error_log(sprintf(
				'AQOP Auto-Sync: Completed - %d processed, %d created, %d updated',
				$result['leads_processed'],
				$result['leads_created'],
				$result['leads_updated']
			));
		} else {
			error_log('AQOP Auto-Sync: Failed - ' . $result['message']);
		}
	} catch (Exception $e) {
		error_log('AQOP Auto-Sync: Exception - ' . $e->getMessage());
		update_option('aqop_airtable_last_auto_sync', current_time('mysql'));
		update_option('aqop_airtable_last_auto_sync_result', array(
			'success' => false,
			'message' => 'Exception: ' . $e->getMessage(),
		));
	}
}
add_action('aqop_airtable_auto_sync_hook', 'aqop_execute_airtable_auto_sync');

/**
 * Clear Airtable auto-sync cron on plugin deactivation.
 *
 * @since 1.0.2
 */
function aqop_clear_airtable_sync_cron()
{
	$timestamp = wp_next_scheduled('aqop_airtable_auto_sync_hook');
	if ($timestamp) {
		wp_unschedule_event($timestamp, 'aqop_airtable_auto_sync_hook');
	}
}
register_deactivation_hook(__FILE__, 'aqop_clear_airtable_sync_cron');
