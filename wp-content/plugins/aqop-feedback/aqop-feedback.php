<?php
/**
 * Plugin Name: AQOP Feedback System
 * Plugin URI: https://aqleeat.com
 * Description: Comprehensive feedback and issue reporting system for AQOP Platform modules
 * Version: 1.0.0
 * Author: Muhammed Derviş
 * Author URI: https://aqleeat.com
 * Requires Plugins: aqop-core
 * Text Domain: aqop-feedback
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AQOP_Feedback
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
                        __('<strong>AQOP Feedback System</strong> requires <strong>AQOP Core</strong> to be installed and activated.', 'aqop-feedback')
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
define('AQOP_FEEDBACK_VERSION', '1.0.0');
define('AQOP_FEEDBACK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AQOP_FEEDBACK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AQOP_FEEDBACK_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load dependencies.
require_once AQOP_FEEDBACK_PLUGIN_DIR . 'includes/class-feedback-installer.php';
require_once AQOP_FEEDBACK_PLUGIN_DIR . 'includes/class-feedback-manager.php';
require_once AQOP_FEEDBACK_PLUGIN_DIR . 'api/class-feedback-api.php';

// Load admin if in admin area.
if (is_admin()) {
    require_once AQOP_FEEDBACK_PLUGIN_DIR . 'admin/class-feedback-admin.php';
}

/**
 * Activation hook.
 */
register_activation_hook(__FILE__, array('AQOP_Feedback_Installer', 'activate'));

/**
 * Initialize the Feedback System.
 *
 * @since 1.0.0
 */
function aqop_feedback_init()
{
    // Initialize API.
    AQOP_Feedback_API::init();

    // Initialize Admin.
    if (is_admin()) {
        AQOP_Feedback_Admin::init();
    }
}
add_action('plugins_loaded', 'aqop_feedback_init', 20);
