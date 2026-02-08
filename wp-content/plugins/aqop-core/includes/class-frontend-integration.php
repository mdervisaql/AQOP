<?php
/**
 * Frontend Integration Class
 *
 * Integrates the React frontend application with WordPress.
 * Handles routing and asset loading.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AQOP_Frontend_Integration class.
 *
 * @since 1.0.0
 */
class AQOP_Frontend_Integration
{

    /**
     * Initialize integration.
     *
     * @since 1.0.0
     * @static
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'load_app'));
    }

    /**
     * Add rewrite rules for React app routes.
     *
     * @since 1.0.0
     * @static
     */
    public static function add_rewrite_rules()
    {
        // List of top-level routes handled by React app
        $routes = array(
            'dashboard',
            'settings',
            'leads',
            'login',
            'users',
            'analytics',
            'notifications',
            'follow-ups',
            'team-leads',
            'my-leads',
            'system-health'
        );

        $route_regex = '^(' . implode('|', $routes) . ')(/.*)?$';

        add_rewrite_rule(
            $route_regex,
            'index.php?aqop_app=1',
            'top'
        );
    }

    /**
     * Add query variable.
     *
     * @since 1.0.0
     * @static
     * @param array $vars Query variables.
     * @return array Modified query variables.
     */
    public static function add_query_vars($vars)
    {
        $vars[] = 'aqop_app';
        return $vars;
    }

    /**
     * Load React application.
     *
     * @since 1.0.0
     * @static
     */
    public static function load_app()
    {
        if (get_query_var('aqop_app')) {
            // Enqueue assets
            self::enqueue_assets();

            // Output HTML
            ?>
            <!doctype html>
            <html <?php language_attributes(); ?>>

            <head>
                <meta charset="<?php bloginfo('charset'); ?>" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <title><?php bloginfo('name'); ?> | Operation Platform</title>
                <?php wp_head(); ?>
                <style>
                    /* Reset WordPress margins if any */
                    html,
                    body {
                        margin: 0;
                        padding: 0;
                        height: 100%;
                    }

                    #wpadminbar {
                        display: none;
                    }

                    /* Hide admin bar for app view */
                </style>
            </head>

            <body>
                <div id="root"></div>
                <?php wp_footer(); ?>
            </body>

            </html>
            <?php
            exit;
        }
    }

    /**
     * Enqueue React app assets.
     *
     * @since 1.0.0
     * @static
     */
    public static function enqueue_assets()
    {
        $assets_dir = AQOP_PLUGIN_DIR . 'assets/app/';
        $assets_url = AQOP_PLUGIN_URL . 'assets/app/';

        if (!is_dir($assets_dir)) {
            return;
        }

        $files = scandir($assets_dir);
        foreach ($files as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $handle = 'aqop-app-' . sanitize_title(pathinfo($file, PATHINFO_FILENAME));

            if ('js' === $ext) {
                wp_enqueue_script(
                    $handle,
                    $assets_url . $file,
                    array(), // Dependencies managed by Vite
                    null, // Version managed by Vite hash
                    true
                );
            } elseif ('css' === $ext) {
                wp_enqueue_style(
                    $handle,
                    $assets_url . $file,
                    array(),
                    null
                );
            }
        }

        // Pass data to React
        wp_localize_script('aqop-app-index', 'aqopSettings', array(
            'apiUrl' => get_rest_url(null, 'aqop/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'user' => wp_get_current_user(),
        ));
    }
}
