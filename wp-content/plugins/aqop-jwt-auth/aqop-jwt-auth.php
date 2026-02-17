<?php
/**
 * Plugin Name: AQOP JWT Authentication
 * Plugin URI: https://aqleeat.com
 * Description: Enterprise-grade JWT authentication for AQOP Platform with refresh tokens and role-based access
 * Version: 1.0.0
 * Author: Muhammed Derviş
 * Author URI: https://aqleeat.com
 * Text Domain: aqop-jwt-auth
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * === JWT AUTHENTICATION SYSTEM (Hour 1) ===
 * Generated: 2025-11-17
 * Security Level: Enterprise Grade
 * Algorithm: HS256 (HMAC-SHA256)
 * Token Expiry: Access 15min, Refresh 7days
 * === END JWT AUTHENTICATION ===
 *
 * @package AQOP_JWT_Auth
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define('AQOP_JWT_VERSION', '1.0.0');
define('AQOP_JWT_PATH', plugin_dir_path(__FILE__));
define('AQOP_JWT_URL', plugin_dir_url(__FILE__));
define('AQOP_JWT_BASENAME', plugin_basename(__FILE__));

// Token expiry times (in seconds).
define('AQOP_JWT_ACCESS_EXPIRY', 15 * MINUTE_IN_SECONDS);  // 15 minutes
define('AQOP_JWT_REFRESH_EXPIRY', 7 * DAY_IN_SECONDS);     // 7 days

// Load dependencies.
require_once AQOP_JWT_PATH . 'includes/class-jwt-handler.php';
require_once AQOP_JWT_PATH . 'includes/class-jwt-rest-controller.php';
require_once AQOP_JWT_PATH . 'includes/class-jwt-installer.php';
require_once AQOP_JWT_PATH . 'includes/class-jwt-admin.php';

/**
 * Initialize the JWT authentication system.
 *
 * @since 1.0.0
 */
function aqop_jwt_init()
{
	AQOP_JWT_Handler::init();
	AQOP_JWT_REST_Controller::init();

	if (is_admin()) {
		AQOP_JWT_Admin::init();
	}
}
add_action('plugins_loaded', 'aqop_jwt_init');

/**
 * Activation hook.
 *
 * Runs when the plugin is activated.
 *
 * @since 1.0.0
 */
register_activation_hook(__FILE__, array('AQOP_JWT_Installer', 'activate'));

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated.
 *
 * @since 1.0.0
 */
register_deactivation_hook(__FILE__, array('AQOP_JWT_Installer', 'deactivate'));

/**
 * Get allowed CORS origins.
 *
 * Returns array of allowed origins from options or defaults.
 *
 * @since 1.0.0
 * @return array Allowed origins.
 */
function aqop_jwt_get_allowed_origins()
{
	// Get custom origins from options
	$custom_origins = get_option('aqop_jwt_allowed_origins', '');

	// Default allowed origins for development and production
	$default_origins = array(
		'http://localhost:5173',
		'http://localhost:5174',
		'http://localhost:3000',
	);

	// If custom origins are set, parse them
	if (!empty($custom_origins)) {
		$origins = array_filter(array_map('trim', explode("\n", $custom_origins)));
		// Merge with defaults for development
		return array_merge($default_origins, $origins);
	}

	return $default_origins;
}

/**
 * Setup CORS headers for REST API.
 *
 * @since 1.0.0
 */
add_action('rest_api_init', function () {
	remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
	add_filter('rest_pre_serve_request', function ($value) {
		// Get request origin
		$origin = isset($_SERVER['HTTP_ORIGIN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';

		// Get allowed origins
		$allowed_origins = aqop_jwt_get_allowed_origins();

		// Check if origin is allowed
		if (in_array($origin, $allowed_origins, true)) {
			header('Access-Control-Allow-Origin: ' . $origin);
		} elseif (empty($origin)) {
			// For direct API calls without origin (e.g., Postman, cURL)
			header('Access-Control-Allow-Origin: *');
		} else {
			// Origin not allowed - use first allowed origin as fallback for development
			if (!empty($allowed_origins)) {
				header('Access-Control-Allow-Origin: ' . $allowed_origins[0]);
			}
		}

		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

		// Handle preflight requests.
		if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
			status_header(200);
			exit();
		}

		return $value;
	});
}, 15);

