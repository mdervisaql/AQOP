<?php
/**
 * JWT Admin Class
 *
 * Handles the WordPress Admin settings page for JWT Authentication.
 *
 * @package AQOP_JWT_Auth
 * @since   1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AQOP_JWT_Admin
{

    /**
     * Initialize the admin settings.
     *
     * @since 1.1.0
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Add the settings page to the admin menu.
     *
     * @since 1.1.0
     */
    public static function add_admin_menu()
    {
        add_options_page(
            __('JWT Authentication Settings', 'aqop-jwt-auth'),
            __('JWT Auth', 'aqop-jwt-auth'),
            'manage_options',
            'aqop-jwt-auth',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings.
     *
     * @since 1.1.0
     */
    public static function register_settings()
    {
        register_setting('aqop_jwt_settings_group', 'aqop_jwt_allowed_roles');

        add_settings_section(
            'aqop_jwt_general_section',
            __('General Settings', 'aqop-jwt-auth'),
            null,
            'aqop-jwt-auth'
        );

        add_settings_field(
            'aqop_jwt_allowed_roles',
            __('Allowed Roles', 'aqop-jwt-auth'),
            array(__CLASS__, 'render_allowed_roles_field'),
            'aqop-jwt-auth',
            'aqop_jwt_general_section'
        );
    }

    /**
     * Render the Allowed Roles checkbox field.
     *
     * @since 1.1.0
     */
    public static function render_allowed_roles_field()
    {
        global $wp_roles;
        $all_roles = $wp_roles->get_names();

        // Get saved roles or default to empty array
        $allowed_roles = get_option('aqop_jwt_allowed_roles', array());

        // If empty (first run), default to the hardcoded list we had before
        if (empty($allowed_roles)) {
            $allowed_roles = array(
                'administrator',
                'operation_admin',
                'operation_manager',
                'aq_supervisor',
                'aq_agent',
            );
        }

        echo '<fieldset>';
        foreach ($all_roles as $role_key => $role_name) {
            $checked = in_array($role_key, $allowed_roles) ? 'checked="checked"' : '';
            echo '<label style="display:block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="aqop_jwt_allowed_roles[]" value="' . esc_attr($role_key) . '" ' . $checked . '> ' . esc_html($role_name);
            echo '</label>';
        }
        echo '<p class="description">' . __('Select which user roles are allowed to authenticate via JWT.', 'aqop-jwt-auth') . '</p>';
        echo '</fieldset>';
    }

    /**
     * Render the settings page HTML.
     *
     * @since 1.1.0
     */
    public static function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('aqop_jwt_settings_group');
                do_settings_sections('aqop-jwt-auth');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
