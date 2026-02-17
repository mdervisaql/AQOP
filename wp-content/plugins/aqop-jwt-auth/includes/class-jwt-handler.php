<?php
/**
 * JWT Handler Class
 *
 * Core JWT token generation, validation, and management.
 * Implements HS256 algorithm with enterprise security features.
 *
 * === JWT AUTHENTICATION SYSTEM (Hour 1) ===
 * Generated: 2025-11-17
 * Security Level: Enterprise Grade
 * Algorithm: HS256 (HMAC-SHA256)
 * Token Expiry: Access 15min, Refresh 7days
 * === END JWT AUTHENTICATION ===
 *
 * @package AQOP_JWT_Auth
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_JWT_Handler class.
 *
 * Handles all JWT operations including token creation, validation, and blacklisting.
 *
 * @since 1.0.0
 */
class AQOP_JWT_Handler {

	/**
	 * Secret key for access tokens.
	 *
	 * @var string
	 */
	private static $access_secret;

	/**
	 * Secret key for refresh tokens.
	 *
	 * @var string
	 */
	private static $refresh_secret;

	/**
	 * Initialize the JWT handler.
	 *
	 * Sets up secret keys and schedules cleanup tasks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$access_secret  = self::get_or_generate_secret( 'aqop_jwt_access_secret' );
		self::$refresh_secret = self::get_or_generate_secret( 'aqop_jwt_refresh_secret' );
		
		// Authenticate REST API requests with JWT.
		add_filter( 'determine_current_user', array( __CLASS__, 'determine_current_user' ), 20 );
	}

	/**
	 * Get or generate secret key.
	 *
	 * Uses cryptographically secure random bytes for key generation.
	 *
	 * @since  1.0.0
	 * @param  string $option_name Option name to store the secret.
	 * @return string Secret key (64 character hex string).
	 */
	private static function get_or_generate_secret( $option_name ) {
		$secret = get_option( $option_name );
		
		if ( empty( $secret ) ) {
			// Generate 256-bit (32 bytes) cryptographically secure key.
			$secret = bin2hex( random_bytes( 32 ) );
			update_option( $option_name, $secret, false );
		}
		
		return $secret;
	}

	/**
	 * Create JWT token.
	 *
	 * Generates a signed JWT token with user data and metadata.
	 *
	 * @since  1.0.0
	 * @param  int    $user_id User ID.
	 * @param  string $type    Token type ('access' or 'refresh').
	 * @return string|WP_Error JWT token or error.
	 */
	public static function create_token( $user_id, $type = 'access' ) {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', __( 'User not found.', 'aqop-jwt-auth' ), array( 'status' => 404 ) );
		}

		// Determine expiry and secret based on token type.
		if ( 'refresh' === $type ) {
			$expiry = time() + AQOP_JWT_REFRESH_EXPIRY;
			$secret = self::$refresh_secret;
		} else {
			$expiry = time() + AQOP_JWT_ACCESS_EXPIRY;
			$secret = self::$access_secret;
		}

		// Get user role (primary role).
		$roles = $user->roles;
		$primary_role = ! empty( $roles ) ? $roles[0] : 'subscriber';

		// Get user capabilities.
		$capabilities = array_keys( $user->allcaps );

		// Get client metadata.
		$client_ip = self::get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Build JWT payload.
		$payload = array(
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => time(),
			'exp'  => $expiry,
			'sub'  => $user_id,
			'type' => $type,
			'data' => array(
				'user' => array(
					'id'           => $user_id,
					'username'     => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'role'         => $primary_role,
					'capabilities' => $capabilities,
				),
				'meta' => array(
					'ip'         => $client_ip,
					'user_agent' => $user_agent,
				),
			),
		);

		// Generate token.
		$token = self::encode( $payload, $secret );

		return $token;
	}

	/**
	 * Encode JWT token.
	 *
	 * Creates a JWT token using HS256 algorithm.
	 *
	 * @since  1.0.0
	 * @param  array  $payload Payload data.
	 * @param  string $secret  Secret key.
	 * @return string JWT token.
	 */
	private static function encode( $payload, $secret ) {
		// Create header.
		$header = array(
			'typ' => 'JWT',
			'alg' => 'HS256',
		);

		// Base64 encode header and payload.
		$header_encoded  = self::base64url_encode( wp_json_encode( $header ) );
		$payload_encoded = self::base64url_encode( wp_json_encode( $payload ) );

		// Create signature.
		$signature = hash_hmac( 'sha256', $header_encoded . '.' . $payload_encoded, $secret, true );
		$signature_encoded = self::base64url_encode( $signature );

		// Build JWT.
		return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
	}

	/**
	 * Decode and validate JWT token.
	 *
	 * @since  1.0.0
	 * @param  string $token JWT token.
	 * @param  string $type  Expected token type ('access' or 'refresh').
	 * @return array|WP_Error Decoded payload or error.
	 */
	public static function decode( $token, $type = 'access' ) {
		// Check if token is blacklisted.
		if ( self::is_blacklisted( $token ) ) {
			return new WP_Error( 'token_blacklisted', __( 'Token has been revoked.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Get appropriate secret.
		$secret = ( 'refresh' === $type ) ? self::$refresh_secret : self::$access_secret;

		// Split token into parts.
		$parts = explode( '.', $token );
		
		if ( count( $parts ) !== 3 ) {
			return new WP_Error( 'invalid_token', __( 'Invalid token format.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		list( $header_encoded, $payload_encoded, $signature_encoded ) = $parts;

		// Decode header and payload.
		$header  = json_decode( self::base64url_decode( $header_encoded ), true );
		$payload = json_decode( self::base64url_decode( $payload_encoded ), true );

		if ( ! $header || ! $payload ) {
			return new WP_Error( 'invalid_token', __( 'Invalid token data.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Verify algorithm.
		if ( ! isset( $header['alg'] ) || 'HS256' !== $header['alg'] ) {
			return new WP_Error( 'invalid_algorithm', __( 'Unsupported algorithm.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Verify signature using timing-safe comparison.
		$expected_signature = hash_hmac( 'sha256', $header_encoded . '.' . $payload_encoded, $secret, true );
		$actual_signature   = self::base64url_decode( $signature_encoded );

		if ( ! hash_equals( $expected_signature, $actual_signature ) ) {
			return new WP_Error( 'invalid_signature', __( 'Token signature verification failed.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Check token expiration.
		if ( ! isset( $payload['exp'] ) || time() >= $payload['exp'] ) {
			return new WP_Error( 'token_expired', __( 'Token has expired.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Verify token type.
		if ( ! isset( $payload['type'] ) || $payload['type'] !== $type ) {
			return new WP_Error( 'invalid_token_type', __( 'Invalid token type.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Verify issuer.
		if ( ! isset( $payload['iss'] ) || $payload['iss'] !== get_bloginfo( 'url' ) ) {
			return new WP_Error( 'invalid_issuer', __( 'Invalid token issuer.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Log IP changes (security monitoring).
		if ( isset( $payload['data']['meta']['ip'] ) ) {
			$token_ip  = $payload['data']['meta']['ip'];
			$current_ip = self::get_client_ip();
			
			if ( $token_ip !== $current_ip ) {
				// Log IP change but don't block (could be mobile network switching).
				error_log( sprintf(
					'JWT Auth: IP change detected for user %d. Token IP: %s, Current IP: %s',
					$payload['sub'],
					$token_ip,
					$current_ip
				) );
			}
		}

		return $payload;
	}

	/**
	 * Validate token quickly.
	 *
	 * Fast validation without full decoding.
	 *
	 * @since  1.0.0
	 * @param  string $token JWT token.
	 * @return bool True if valid.
	 */
	public static function validate_token( $token ) {
		$decoded = self::decode( $token, 'access' );
		return ! is_wp_error( $decoded );
	}

	/**
	 * Refresh access token.
	 *
	 * Generate new access token from valid refresh token.
	 *
	 * @since  1.0.0
	 * @param  string $refresh_token Refresh token.
	 * @return string|WP_Error New access token or error.
	 */
	public static function refresh_access_token( $refresh_token ) {
		// Decode refresh token.
		$payload = self::decode( $refresh_token, 'refresh' );
		
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		// Get user ID from payload.
		$user_id = isset( $payload['sub'] ) ? (int) $payload['sub'] : 0;
		
		if ( ! $user_id ) {
			return new WP_Error( 'invalid_user', __( 'Invalid user in token.', 'aqop-jwt-auth' ), array( 'status' => 401 ) );
		}

		// Generate new access token.
		$access_token = self::create_token( $user_id, 'access' );
		
		return $access_token;
	}

	/**
	 * Add token to blacklist.
	 *
	 * Blacklists a token on logout or security events.
	 *
	 * @since  1.0.0
	 * @param  string $token JWT token.
	 * @return bool True on success, false on failure.
	 */
	public static function blacklist_token( $token ) {
		global $wpdb;

		// Decode token to get expiry and user ID.
		$payload = self::decode( $token, 'access' );
		
		if ( is_wp_error( $payload ) ) {
			return false;
		}

		$user_id    = isset( $payload['sub'] ) ? (int) $payload['sub'] : 0;
		$expires_at = isset( $payload['exp'] ) ? $payload['exp'] : 0;

		if ( ! $user_id || ! $expires_at ) {
			return false;
		}

		// Hash the token (don't store raw tokens).
		$token_hash = hash( 'sha256', $token );

		// Insert into blacklist.
		$result = $wpdb->insert(
			$wpdb->prefix . 'aqop_jwt_blacklist',
			array(
				'token_hash'  => $token_hash,
				'user_id'     => $user_id,
				'expires_at'  => gmdate( 'Y-m-d H:i:s', $expires_at ),
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Check if token is blacklisted.
	 *
	 * @since  1.0.0
	 * @param  string $token JWT token.
	 * @return bool True if blacklisted.
	 */
	public static function is_blacklisted( $token ) {
		global $wpdb;

		$token_hash = hash( 'sha256', $token );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}aqop_jwt_blacklist WHERE token_hash = %s AND expires_at > %s",
				$token_hash,
				current_time( 'mysql', true )
			)
		);

		return (int) $exists > 0;
	}

	/**
	 * Cleanup expired blacklist entries.
	 *
	 * Removes tokens that have already expired.
	 *
	 * @since 1.0.0
	 */
	public static function cleanup_blacklist() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}aqop_jwt_blacklist WHERE expires_at <= %s",
				current_time( 'mysql', true )
			)
		);
	}

	/**
	 * Determine current user from JWT token.
	 *
	 * Integrates JWT authentication with WordPress authentication system.
	 *
	 * @since  1.0.0
	 * @param  int|bool $user_id Current user ID or false.
	 * @return int|bool User ID if authenticated, original value otherwise.
	 */
	public static function determine_current_user( $user_id ) {
		// Don't override if user already authenticated.
		if ( $user_id ) {
			return $user_id;
		}

		// Get token from Authorization header.
		$token = self::get_token_from_request();
		
		if ( ! $token ) {
			return $user_id;
		}

		// Decode and validate token.
		$payload = self::decode( $token, 'access' );
		
		if ( is_wp_error( $payload ) ) {
			return $user_id;
		}

		// Return authenticated user ID.
		return isset( $payload['sub'] ) ? (int) $payload['sub'] : $user_id;
	}

	/**
	 * Get JWT token from request.
	 *
	 * Extracts Bearer token from Authorization header.
	 *
	 * @since  1.0.0
	 * @return string|null Token or null if not found.
	 */
	public static function get_token_from_request() {
		$auth_header = null;

		// Check for Authorization header.
		if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
		} elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
		} elseif ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
			if ( isset( $headers['Authorization'] ) ) {
				$auth_header = sanitize_text_field( $headers['Authorization'] );
			}
		}

		if ( ! $auth_header ) {
			return null;
		}

		// Extract Bearer token.
		if ( preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Get client IP address.
	 *
	 * @since  1.0.0
	 * @return string Client IP address.
	 */
	private static function get_client_ip() {
		$ip = '';

		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// If multiple IPs, get the first one.
		if ( strpos( $ip, ',' ) !== false ) {
			$ips = explode( ',', $ip );
			$ip = trim( $ips[0] );
		}

		return $ip;
	}

	/**
	 * Base64 URL encode.
	 *
	 * @since  1.0.0
	 * @param  string $data Data to encode.
	 * @return string Encoded data.
	 */
	private static function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode.
	 *
	 * @since  1.0.0
	 * @param  string $data Data to decode.
	 * @return string Decoded data.
	 */
	private static function base64url_decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
}

