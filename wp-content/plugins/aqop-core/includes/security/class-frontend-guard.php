<?php
/**
 * Frontend Guard Class
 *
 * Provides security layer for frontend pages and AJAX requests.
 * Handles authentication, authorization, rate limiting, and input validation.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Frontend_Guard class.
 *
 * Multi-layer security system for Operation Platform frontend.
 * Protects pages, validates requests, and prevents abuse.
 *
 * @since 1.0.0
 */
class AQOP_Frontend_Guard {

	/**
	 * Cache for client IP.
	 *
	 * @var string|null
	 * @since 1.0.0
	 */
	private static $client_ip = null;

	/**
	 * Cache for user agent.
	 *
	 * @var string|null
	 * @since 1.0.0
	 */
	private static $user_agent = null;

	/**
	 * Check page access.
	 *
	 * Verifies user authentication and authorization for frontend pages.
	 * Redirects to login if not authenticated, dies if no permission.
	 *
	 * Usage:
	 * ```php
	 * // At top of frontend page
	 * AQOP_Frontend_Guard::check_page_access( 'view_control_center' );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string|null $capability   Optional. Required capability. Default null (just login required).
	 * @param  string      $redirect_url Optional. Login redirect URL. Default '/operation-login/'.
	 * @return bool True if access granted (only reached if user has access).
	 */
	public static function check_page_access( $capability = null, $redirect_url = '/operation-login/' ) {
		$user_id = get_current_user_id();

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			// Log unauthorized access attempt.
			self::log_security_event(
				'unauthorized_access_attempt',
				array(
					'reason'       => 'not_logged_in',
					'capability'   => $capability,
					'request_uri'  => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
					'severity'     => 'warning',
				)
			);

			// Redirect to login page.
			wp_safe_redirect( home_url( $redirect_url ) );
			exit;
		}

		// Check capability if provided.
		if ( null !== $capability && ! current_user_can( $capability ) ) {
			// Log access denied.
			self::log_security_event(
				'access_denied',
				array(
					'reason'       => 'insufficient_permissions',
					'capability'   => $capability,
					'user_id'      => $user_id,
					'request_uri'  => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
					'severity'     => 'warning',
				)
			);

			// Display error and die.
			wp_die(
				esc_html__( 'عذراً، ليس لديك صلاحية للوصول لهذه الصفحة', 'aqop-core' ),
				esc_html__( 'Access Denied', 'aqop-core' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		// Log successful access.
		self::log_security_event(
			'page_accessed',
			array(
				'capability'  => $capability,
				'user_id'     => $user_id,
				'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
				'severity'    => 'info',
			)
		);

		return true;
	}

	/**
	 * Verify AJAX request.
	 *
	 * Validates AJAX requests with nonce verification and capability checks.
	 * Sends JSON error response if verification fails.
	 *
	 * Usage:
	 * ```php
	 * add_action( 'wp_ajax_my_action', 'my_ajax_handler' );
	 * function my_ajax_handler() {
	 *     AQOP_Frontend_Guard::verify_ajax_request( 'my_action', 'manage_operation' );
	 *     // Process request
	 * }
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string      $action     AJAX action name.
	 * @param  string|null $capability Optional. Required capability. Default null.
	 * @return bool True if verified (only reached if valid).
	 */
	public static function verify_ajax_request( $action, $capability = null ) {
		// Verify nonce.
		$nonce_check = check_ajax_referer( $action, 'security', false );

		if ( false === $nonce_check ) {
			self::log_security_event(
				'invalid_nonce',
				array(
					'action'      => $action,
					'user_id'     => get_current_user_id(),
					'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
					'severity'    => 'warning',
				)
			);

			wp_send_json_error(
				array(
					'message' => __( 'خطأ في التحقق الأمني. يرجى تحديث الصفحة والمحاولة مرة أخرى.', 'aqop-core' ),
				),
				403
			);
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			self::log_security_event(
				'ajax_unauthorized',
				array(
					'action'   => $action,
					'reason'   => 'not_logged_in',
					'severity' => 'warning',
				)
			);

			wp_send_json_error(
				array(
					'message' => __( 'يجب تسجيل الدخول للقيام بهذا الإجراء.', 'aqop-core' ),
				),
				401
			);
		}

		// Check capability if provided.
		if ( null !== $capability && ! current_user_can( $capability ) ) {
			self::log_security_event(
				'ajax_access_denied',
				array(
					'action'     => $action,
					'capability' => $capability,
					'user_id'    => get_current_user_id(),
					'severity'   => 'warning',
				)
			);

			wp_send_json_error(
				array(
					'message' => __( 'ليس لديك صلاحية للقيام بهذا الإجراء.', 'aqop-core' ),
				),
				403
			);
		}

		// Log successful verification.
		self::log_security_event(
			'ajax_verified',
			array(
				'action'     => $action,
				'capability' => $capability,
				'user_id'    => get_current_user_id(),
				'severity'   => 'info',
			)
		);

		return true;
	}

	/**
	 * Check rate limit.
	 *
	 * Prevents abuse by limiting requests per time window.
	 * Uses transients for tracking request counts.
	 *
	 * Usage:
	 * ```php
	 * if ( ! AQOP_Frontend_Guard::check_rate_limit( 'export_data', 10, 60 ) ) {
	 *     wp_die( 'Rate limit exceeded' );
	 * }
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $action         Action identifier.
	 * @param  int    $max_requests   Maximum requests allowed. Default 60.
	 * @param  int    $window_seconds Time window in seconds. Default 60.
	 * @return bool True if allowed, false if rate limit exceeded.
	 */
	public static function check_rate_limit( $action, $max_requests = 60, $window_seconds = 60 ) {
		$user_id = get_current_user_id();
		$ip = self::get_client_ip();

		// Generate unique key.
		$transient_key = 'aqop_rate_' . $action . '_' . $user_id . '_' . md5( $ip );

		// Get current count.
		$count = get_transient( $transient_key );

		if ( false === $count ) {
			// First request in window.
			set_transient( $transient_key, 1, $window_seconds );
			return true;
		}

		// Increment count.
		$count = (int) $count;

		if ( $count >= $max_requests ) {
			// Rate limit exceeded.
			self::log_security_event(
				'rate_limit_exceeded',
				array(
					'action'       => $action,
					'user_id'      => $user_id,
					'ip_address'   => $ip,
					'count'        => $count,
					'max_requests' => $max_requests,
					'window'       => $window_seconds,
					'severity'     => 'warning',
				)
			);

			return false;
		}

		// Update count.
		set_transient( $transient_key, $count + 1, $window_seconds );

		return true;
	}

	/**
	 * Sanitize request data.
	 *
	 * Applies sanitization based on field rules.
	 * Prevents XSS and other injection attacks.
	 *
	 * Usage:
	 * ```php
	 * $clean_data = AQOP_Frontend_Guard::sanitize_request(
	 *     $_POST,
	 *     array(
	 *         'name'  => 'text',
	 *         'email' => 'email',
	 *         'age'   => 'int',
	 *         'url'   => 'url',
	 *     )
	 * );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  array $data  Data to sanitize.
	 * @param  array $rules Sanitization rules (field => type).
	 * @return array Sanitized data.
	 */
	public static function sanitize_request( $data, $rules = array() ) {
		$sanitized = array();

		foreach ( $rules as $field => $type ) {
			if ( ! isset( $data[ $field ] ) ) {
				continue;
			}

			$value = $data[ $field ];

			switch ( $type ) {
				case 'text':
					$sanitized[ $field ] = sanitize_text_field( $value );
					break;

				case 'email':
					$sanitized[ $field ] = sanitize_email( $value );
					break;

				case 'int':
					$sanitized[ $field ] = absint( $value );
					break;

				case 'url':
					$sanitized[ $field ] = esc_url_raw( $value );
					break;

				case 'html':
					$sanitized[ $field ] = wp_kses_post( $value );
					break;

				case 'textarea':
					$sanitized[ $field ] = sanitize_textarea_field( $value );
					break;

				case 'key':
					$sanitized[ $field ] = sanitize_key( $value );
					break;

				case 'array':
					if ( is_array( $value ) ) {
						$sanitized[ $field ] = array_map( 'sanitize_text_field', $value );
					} else {
						$sanitized[ $field ] = array();
					}
					break;

				case 'json':
					if ( is_string( $value ) ) {
						$decoded = json_decode( $value, true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							$sanitized[ $field ] = $decoded;
						} else {
							$sanitized[ $field ] = null;
						}
					} else {
						$sanitized[ $field ] = $value;
					}
					break;

				default:
					// Default to text sanitization.
					$sanitized[ $field ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate request data.
	 *
	 * Validates data against rules and returns validation result.
	 * Does not sanitize - use sanitize_request() first.
	 *
	 * Usage:
	 * ```php
	 * $validation = AQOP_Frontend_Guard::validate_request(
	 *     $data,
	 *     array(
	 *         'name'  => array( 'required', 'min:3' ),
	 *         'email' => array( 'required', 'email' ),
	 *         'age'   => array( 'numeric', 'min:18' ),
	 *     )
	 * );
	 *
	 * if ( ! $validation['valid'] ) {
	 *     foreach ( $validation['errors'] as $field => $error ) {
	 *         echo $error;
	 *     }
	 * }
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  array $data  Data to validate.
	 * @param  array $rules Validation rules (field => array of rules).
	 * @return array Validation result ['valid' => bool, 'errors' => array].
	 */
	public static function validate_request( $data, $rules = array() ) {
		$errors = array();

		foreach ( $rules as $field => $field_rules ) {
			if ( ! is_array( $field_rules ) ) {
				$field_rules = array( $field_rules );
			}

			$value = isset( $data[ $field ] ) ? $data[ $field ] : null;

			foreach ( $field_rules as $rule ) {
				// Parse rule and parameter (e.g., 'min:3').
				$rule_parts = explode( ':', $rule, 2 );
				$rule_name = $rule_parts[0];
				$rule_param = isset( $rule_parts[1] ) ? $rule_parts[1] : null;

				switch ( $rule_name ) {
					case 'required':
						if ( empty( $value ) && '0' !== $value ) {
							/* translators: %s: Field name */
							$errors[ $field ] = sprintf( __( 'حقل %s مطلوب', 'aqop-core' ), $field );
						}
						break;

					case 'email':
						if ( ! empty( $value ) && ! is_email( $value ) ) {
							/* translators: %s: Field name */
							$errors[ $field ] = sprintf( __( 'حقل %s يجب أن يكون بريد إلكتروني صحيح', 'aqop-core' ), $field );
						}
						break;

					case 'numeric':
						if ( ! empty( $value ) && ! is_numeric( $value ) ) {
							/* translators: %s: Field name */
							$errors[ $field ] = sprintf( __( 'حقل %s يجب أن يكون رقماً', 'aqop-core' ), $field );
						}
						break;

					case 'min':
						if ( ! empty( $value ) && $rule_param ) {
							if ( is_numeric( $value ) && $value < $rule_param ) {
								/* translators: 1: Field name, 2: Minimum value */
								$errors[ $field ] = sprintf( __( 'حقل %1$s يجب أن يكون على الأقل %2$s', 'aqop-core' ), $field, $rule_param );
							} elseif ( is_string( $value ) && strlen( $value ) < $rule_param ) {
								/* translators: 1: Field name, 2: Minimum length */
								$errors[ $field ] = sprintf( __( 'حقل %1$s يجب أن يحتوي على الأقل %2$s حرف', 'aqop-core' ), $field, $rule_param );
							}
						}
						break;

					case 'max':
						if ( ! empty( $value ) && $rule_param ) {
							if ( is_numeric( $value ) && $value > $rule_param ) {
								/* translators: 1: Field name, 2: Maximum value */
								$errors[ $field ] = sprintf( __( 'حقل %1$s يجب أن يكون بحد أقصى %2$s', 'aqop-core' ), $field, $rule_param );
							} elseif ( is_string( $value ) && strlen( $value ) > $rule_param ) {
								/* translators: 1: Field name, 2: Maximum length */
								$errors[ $field ] = sprintf( __( 'حقل %1$s يجب أن يحتوي على بحد أقصى %2$s حرف', 'aqop-core' ), $field, $rule_param );
							}
						}
						break;

					case 'url':
						if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
							/* translators: %s: Field name */
							$errors[ $field ] = sprintf( __( 'حقل %s يجب أن يكون رابط صحيح', 'aqop-core' ), $field );
						}
						break;

					case 'in':
						if ( $rule_param ) {
							$allowed_values = explode( ',', $rule_param );
							if ( ! empty( $value ) && ! in_array( $value, $allowed_values, true ) ) {
								/* translators: %s: Field name */
								$errors[ $field ] = sprintf( __( 'حقل %s يحتوي على قيمة غير صحيحة', 'aqop-core' ), $field );
							}
						}
						break;
				}
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Get client IP address.
	 *
	 * Retrieves the client's IP address, handling proxies and load balancers.
	 * Result is cached for the request lifetime.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return string IP address.
	 */
	public static function get_client_ip() {
		// Return cached value if available.
		if ( null !== self::$client_ip ) {
			return self::$client_ip;
		}

		$ip_address = '';

		// Check for proxy headers.
		$headers = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// Handle comma-separated IPs (proxy chain).
				if ( strpos( $ip_address, ',' ) !== false ) {
					$ips = explode( ',', $ip_address );
					$ip_address = trim( $ips[0] );
				}

				// Validate IP.
				if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
					break;
				}

				$ip_address = '';
			}
		}

		// Cache the result.
		self::$client_ip = $ip_address;

		return $ip_address;
	}

	/**
	 * Get user agent.
	 *
	 * Retrieves and sanitizes the user agent string.
	 * Result is cached for the request lifetime.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return string User agent string.
	 */
	public static function get_user_agent() {
		// Return cached value if available.
		if ( null !== self::$user_agent ) {
			return self::$user_agent;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Cache the result.
		self::$user_agent = $user_agent;

		return $user_agent;
	}

	/**
	 * Log security event.
	 *
	 * Logs security-related events using the Event Logger.
	 * Automatically includes IP address and user agent.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $event_type Event type code.
	 * @param  array  $details    Event details. Default empty array.
	 * @return int|false Event ID on success, false on failure.
	 */
	private static function log_security_event( $event_type, $details = array() ) {
		if ( ! class_exists( 'AQOP_Event_Logger' ) ) {
			return false;
		}

		// Add IP and user agent to details.
		$details['ip_address'] = self::get_client_ip();
		$details['user_agent'] = self::get_user_agent();

		return AQOP_Event_Logger::log(
			'core',
			$event_type,
			'security',
			0,
			$details
		);
	}

	/**
	 * Create nonce.
	 *
	 * Wrapper for wp_create_nonce() with logging.
	 *
	 * Usage:
	 * ```php
	 * $nonce = AQOP_Frontend_Guard::create_nonce( 'my_action' );
	 * echo '<input type="hidden" name="security" value="' . esc_attr( $nonce ) . '">';
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $action Action name.
	 * @return string Nonce token.
	 */
	public static function create_nonce( $action ) {
		$nonce = wp_create_nonce( $action );

		// Log nonce creation (info level).
		self::log_security_event(
			'nonce_created',
			array(
				'action'   => $action,
				'user_id'  => get_current_user_id(),
				'severity' => 'info',
			)
		);

		return $nonce;
	}

	/**
	 * Verify nonce.
	 *
	 * Wrapper for wp_verify_nonce() with logging.
	 *
	 * Usage:
	 * ```php
	 * if ( AQOP_Frontend_Guard::verify_nonce( $_POST['security'], 'my_action' ) ) {
	 *     // Valid
	 * }
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $nonce  Nonce token.
	 * @param  string $action Action name.
	 * @return bool True if valid, false otherwise.
	 */
	public static function verify_nonce( $nonce, $action ) {
		$result = wp_verify_nonce( $nonce, $action );

		if ( false === $result || 0 === $result ) {
			// Log failed verification.
			self::log_security_event(
				'nonce_verification_failed',
				array(
					'action'   => $action,
					'user_id'  => get_current_user_id(),
					'severity' => 'warning',
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Clear rate limit for user/action.
	 *
	 * Useful for testing or administrative actions.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string   $action  Action identifier.
	 * @param  int|null $user_id Optional. User ID. Default current user.
	 * @return bool True if cleared.
	 */
	public static function clear_rate_limit( $action, $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$ip = self::get_client_ip();
		$transient_key = 'aqop_rate_' . $action . '_' . $user_id . '_' . md5( $ip );

		return delete_transient( $transient_key );
	}
}

