<?php
/**
 * Permissions Class
 *
 * Handles permission checks for Operation Platform.
 * Provides convenient methods to check user capabilities and access rights.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Permissions class.
 *
 * Static methods for checking Operation Platform permissions.
 * Results are cached during the request for performance.
 *
 * @since 1.0.0
 */
class AQOP_Permissions {

	/**
	 * Cache for permission checks.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private static $permission_cache = array();

	/**
	 * Check if user can access Control Center.
	 *
	 * Users with operation_admin or operation_manager roles can access.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user can access, false otherwise.
	 */
	public static function can_access_control_center( $user_id = null ) {
		$cache_key = 'access_control_center_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$result = current_user_can( 'view_control_center' );
		} else {
			$result = user_can( $user_id, 'view_control_center' );
		}

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Check if user can manage notifications.
	 *
	 * Only operation_admin can manage notification rules.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user can manage notifications, false otherwise.
	 */
	public static function can_manage_notifications( $user_id = null ) {
		$cache_key = 'manage_notifications_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$result = current_user_can( 'manage_notification_rules' );
		} else {
			$result = user_can( $user_id, 'manage_notification_rules' );
		}

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Check if user can view event logs.
	 *
	 * Users with operation_admin or operation_manager can view logs.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user can view logs, false otherwise.
	 */
	public static function can_view_events( $user_id = null ) {
		$cache_key = 'view_events_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$result = current_user_can( 'view_event_logs' );
		} else {
			$result = user_can( $user_id, 'view_event_logs' );
		}

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Check if user can export analytics data.
	 *
	 * Users with export_analytics capability can export.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user can export data, false otherwise.
	 */
	public static function can_export_data( $user_id = null ) {
		$cache_key = 'export_data_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$result = current_user_can( 'export_analytics' );
		} else {
			$result = user_can( $user_id, 'export_analytics' );
		}

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Check if user can manage integrations.
	 *
	 * Only operation_admin can manage integrations (Airtable, Dropbox, etc.).
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user can manage integrations, false otherwise.
	 */
	public static function can_manage_integrations( $user_id = null ) {
		$cache_key = 'manage_integrations_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$result = current_user_can( 'manage_integrations' );
		} else {
			$result = user_can( $user_id, 'manage_integrations' );
		}

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Get modules accessible by user.
	 *
	 * Returns an array of module codes that the user has access to.
	 * operation_admin gets all modules, others get filtered list based on capabilities.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return array Array of module codes (e.g., ['leads', 'training', 'kb']).
	 */
	public static function get_user_modules_access( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Check cache.
		$cache_key = 'aqop_user_modules_' . $user_id;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$modules = array();

		// If user is operation_admin, they get all modules.
		if ( user_can( $user_id, 'operation_admin' ) ) {
			$modules = array( 'core', 'leads', 'training', 'kb' );
		} else {
			// For other users, check specific module capabilities.
			// This can be extended as more modules are added.

			if ( user_can( $user_id, 'view_control_center' ) ) {
				$modules[] = 'core';
			}

			/**
			 * Filter the modules accessible by a user.
			 *
			 * Allows third-party modules to add themselves to the accessible list.
			 *
			 * @since 1.0.0
			 *
			 * @param array $modules Array of module codes.
			 * @param int   $user_id User ID.
			 */
			$modules = apply_filters( 'aqop_user_modules_access', $modules, $user_id );
		}

		// Cache for 5 minutes.
		set_transient( $cache_key, $modules, 5 * MINUTE_IN_SECONDS );

		return $modules;
	}

	/**
	 * Check capability or die.
	 *
	 * Checks if current user has a specific capability.
	 * If not, displays an error message and dies.
	 * Useful for protecting admin pages and AJAX handlers.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string      $capability Required capability.
	 * @param  string|null $message    Optional. Custom error message. Default null.
	 * @return void Dies if user doesn't have capability.
	 */
	public static function check_or_die( $capability, $message = null ) {
		if ( ! current_user_can( $capability ) ) {
			if ( null === $message ) {
				$message = __( 'عذراً، ليس لديك صلاحية للوصول لهذه الصفحة', 'aqop-core' );
			}

			// Log unauthorized access attempt.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'unauthorized_access_attempt',
					'security',
					0,
					array(
						'capability'   => $capability,
						'user_id'      => get_current_user_id(),
						'request_uri'  => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
						'severity'     => 'warning',
					)
				);
			}

			wp_die(
				esc_html( $message ),
				esc_html__( 'Access Denied', 'aqop-core' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Check if user has any operation role.
	 *
	 * Checks if user has operation_admin or operation_manager role.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return bool True if user has any operation role, false otherwise.
	 */
	public static function has_any_operation_role( $user_id = null ) {
		$cache_key = 'has_operation_role_' . ( $user_id ?? get_current_user_id() );

		if ( isset( self::$permission_cache[ $cache_key ] ) ) {
			return self::$permission_cache[ $cache_key ];
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$operation_roles = array( 'operation_admin', 'operation_manager' );
		$user_roles = (array) $user->roles;

		$result = ! empty( array_intersect( $operation_roles, $user_roles ) );

		self::$permission_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Get user's highest operation role.
	 *
	 * Returns the highest operation role for a user.
	 * Priority: operation_admin > operation_manager > none
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (current user).
	 * @return string|null Role slug or null if user has no operation role.
	 */
	public static function get_user_operation_role( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return null;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return null;
		}

		$user_roles = (array) $user->roles;

		// Check in priority order.
		if ( in_array( 'operation_admin', $user_roles, true ) ) {
			return 'operation_admin';
		}

		if ( in_array( 'operation_manager', $user_roles, true ) ) {
			return 'operation_manager';
		}

		return null;
	}

	/**
	 * Clear permission cache.
	 *
	 * Clears the in-memory permission cache.
	 * Useful after role changes.
	 *
	 * @since  1.0.0
	 * @static
	 * @return void
	 */
	public static function clear_cache() {
		self::$permission_cache = array();

		/**
		 * Fires after permission cache has been cleared.
		 *
		 * @since 1.0.0
		 */
		do_action( 'aqop_permissions_cache_cleared' );
	}

	/**
	 * Clear user modules cache.
	 *
	 * Clears the transient cache for user modules access.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int|null $user_id Optional. User ID. Default null (all users).
	 * @return void
	 */
	public static function clear_modules_cache( $user_id = null ) {
		if ( null === $user_id ) {
			// Clear for all users would require more complex logic.
			// For now, we'll just clear the current user.
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			delete_transient( 'aqop_user_modules_' . $user_id );
		}
	}
}

