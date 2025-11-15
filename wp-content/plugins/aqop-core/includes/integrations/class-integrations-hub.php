<?php
/**
 * Integrations Hub Class
 *
 * Manages integrations with external services.
 * Supports Airtable, Dropbox, Telegram, and generic webhooks.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Integrations_Hub class.
 *
 * Handles all external service integrations with retry logic,
 * error handling, and event logging.
 *
 * Configuration (add to wp-config.php):
 * define( 'AQOP_AIRTABLE_API_KEY', 'your_api_key' );
 * define( 'AQOP_AIRTABLE_BASE_ID', 'your_base_id' );
 * define( 'AQOP_AIRTABLE_TABLE_NAME', 'your_table_name' );
 * define( 'AQOP_DROPBOX_ACCESS_TOKEN', 'your_token' );
 * define( 'AQOP_TELEGRAM_BOT_TOKEN', 'your_bot_token' );
 *
 * @since 1.0.0
 */
class AQOP_Integrations_Hub {

	/**
	 * Airtable API base URL.
	 *
	 * @var string
	 */
	const AIRTABLE_API_URL = 'https://api.airtable.com/v0';

	/**
	 * Dropbox API base URL.
	 *
	 * @var string
	 */
	const DROPBOX_API_URL = 'https://api.dropboxapi.com/2';

	/**
	 * Dropbox content API URL.
	 *
	 * @var string
	 */
	const DROPBOX_CONTENT_URL = 'https://content.dropboxapi.com/2';

	/**
	 * Telegram API base URL.
	 *
	 * @var string
	 */
	const TELEGRAM_API_URL = 'https://api.telegram.org';

	/**
	 * Sync record to Airtable.
	 *
	 * Creates or updates a record in Airtable with automatic retry logic.
	 *
	 * Usage:
	 * ```php
	 * $result = AQOP_Integrations_Hub::sync_to_airtable(
	 *     'leads',
	 *     123,
	 *     array(
	 *         'Name' => 'John Doe',
	 *         'Email' => 'john@example.com',
	 *         'Status' => 'Hot',
	 *     )
	 * );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $module           Module name.
	 * @param  int    $record_id        Record ID.
	 * @param  array  $data             Data to sync (Airtable field names as keys).
	 * @param  array  $airtable_config  Optional. Override config. Default empty array.
	 * @return array Sync result array.
	 */
	public static function sync_to_airtable( $module, $record_id, $data, $airtable_config = array() ) {
		$start_time = microtime( true );

		try {
			// Get configuration.
			$config = self::get_integration_config( 'airtable' );

			if ( ! empty( $airtable_config ) ) {
				$config = array_merge( $config, $airtable_config );
			}

			if ( empty( $config['api_key'] ) || empty( $config['base_id'] ) || empty( $config['table_name'] ) ) {
				throw new Exception( 'Airtable configuration missing' );
			}

			// Check if record already has Airtable ID.
			$airtable_record_id = get_post_meta( $record_id, 'airtable_record_id', true );

			$url = self::AIRTABLE_API_URL . '/' . $config['base_id'] . '/' . urlencode( $config['table_name'] );
			$method = 'POST';

			if ( $airtable_record_id ) {
				// Update existing record.
				$url .= '/' . $airtable_record_id;
				$method = 'PATCH';
			}

			// Transform data for Airtable.
			$fields = array();
			foreach ( $data as $field => $value ) {
				$fields[ $field ] = self::transform_field_for_airtable( $value );
			}

			$body = array( 'fields' => $fields );

			// Make request with retry logic.
			$response = self::retry_with_backoff(
				function() use ( $url, $method, $body, $config ) {
					$args = array(
						'method'  => $method,
						'headers' => array(
							'Authorization' => 'Bearer ' . $config['api_key'],
							'Content-Type'  => 'application/json',
						),
						'body'    => wp_json_encode( $body ),
						'timeout' => 30,
					);

					if ( 'POST' === $method ) {
						return wp_remote_post( $url, $args );
					} else {
						return wp_remote_request( $url, $args );
					}
				},
				3
			);

			$http_code = wp_remote_retrieve_response_code( $response );
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_wp_error( $response ) || $http_code < 200 || $http_code >= 300 ) {
				$error_message = is_wp_error( $response ) ? $response->get_error_message() : ( $response_body['error']['message'] ?? 'Unknown error' );
				throw new Exception( $error_message );
			}

			// Save Airtable record ID.
			$new_airtable_id = $response_body['id'] ?? $airtable_record_id;
			if ( $new_airtable_id ) {
				update_post_meta( $record_id, 'airtable_record_id', $new_airtable_id );
				update_post_meta( $record_id, 'airtable_last_sync', current_time( 'mysql' ) );
			}

			// Calculate duration.
			$duration_ms = ( microtime( true ) - $start_time ) * 1000;

			// Log success.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					$module,
					'airtable_sync_success',
					$module,
					$record_id,
					array(
						'airtable_id' => $new_airtable_id,
						'method'      => $method,
						'duration_ms' => $duration_ms,
						'severity'    => 'info',
					)
				);
			}

			return array(
				'success'     => true,
				'airtable_id' => $new_airtable_id,
				'message'     => 'Successfully synced to Airtable',
				'method'      => $method,
			);

		} catch ( Exception $e ) {
			// Log failure.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					$module,
					'airtable_sync_failed',
					$module,
					$record_id,
					array(
						'error'    => $e->getMessage(),
						'severity' => 'error',
					)
				);
			}

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Get Airtable record.
	 *
	 * Retrieves a single record from Airtable.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $base_id   Airtable base ID.
	 * @param  string $table     Table name.
	 * @param  string $record_id Airtable record ID.
	 * @return array|false Record data or false on failure.
	 */
	public static function get_airtable_record( $base_id, $table, $record_id ) {
		try {
			$config = self::get_integration_config( 'airtable' );

			if ( empty( $config['api_key'] ) ) {
				return false;
			}

			$url = self::AIRTABLE_API_URL . '/' . $base_id . '/' . urlencode( $table ) . '/' . $record_id;

			$response = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $config['api_key'],
					),
					'timeout' => 15,
				)
			);

			$http_code = wp_remote_retrieve_response_code( $response );

			if ( is_wp_error( $response ) || $http_code !== 200 ) {
				return false;
			}

			return json_decode( wp_remote_retrieve_body( $response ), true );

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Upload file to Dropbox.
	 *
	 * Uploads a file to Dropbox and optionally creates a share link.
	 *
	 * Usage:
	 * ```php
	 * $result = AQOP_Integrations_Hub::upload_to_dropbox(
	 *     '/path/to/file.pdf',
	 *     '/Leads/SA/document.pdf'
	 * );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $file_path         Local file path.
	 * @param  string $dropbox_path      Dropbox destination path.
	 * @param  bool   $create_share_link Optional. Create share link. Default true.
	 * @return array Upload result array.
	 */
	public static function upload_to_dropbox( $file_path, $dropbox_path, $create_share_link = true ) {
		try {
			$config = self::get_integration_config( 'dropbox' );

			if ( empty( $config['access_token'] ) ) {
				throw new Exception( 'Dropbox access token not configured' );
			}

			if ( ! file_exists( $file_path ) ) {
				throw new Exception( 'File not found: ' . $file_path );
			}

			// Read file contents.
			$file_contents = file_get_contents( $file_path );

			if ( false === $file_contents ) {
				throw new Exception( 'Failed to read file' );
			}

			// Upload file.
			$upload_url = self::DROPBOX_CONTENT_URL . '/files/upload';

			$response = wp_remote_post(
				$upload_url,
				array(
					'headers' => array(
						'Authorization'   => 'Bearer ' . $config['access_token'],
						'Content-Type'    => 'application/octet-stream',
						'Dropbox-API-Arg' => wp_json_encode(
							array(
								'path' => $dropbox_path,
								'mode' => 'overwrite',
							)
						),
					),
					'body'    => $file_contents,
					'timeout' => 60,
				)
			);

			$http_code = wp_remote_retrieve_response_code( $response );

			if ( is_wp_error( $response ) || $http_code !== 200 ) {
				$error_message = is_wp_error( $response ) ? $response->get_error_message() : 'Upload failed';
				throw new Exception( $error_message );
			}

			$share_url = '';

			// Create share link if requested.
			if ( $create_share_link ) {
				$share_result = self::create_dropbox_share_link( $dropbox_path, $config['access_token'] );
				$share_url = $share_result['url'] ?? '';
			}

			// Log success.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'dropbox_upload_success',
					'file',
					0,
					array(
						'file_path'    => $file_path,
						'dropbox_path' => $dropbox_path,
						'share_url'    => $share_url,
						'severity'     => 'info',
					)
				);
			}

			return array(
				'success' => true,
				'path'    => $dropbox_path,
				'url'     => $share_url,
				'message' => 'File uploaded successfully',
			);

		} catch ( Exception $e ) {
			// Log failure.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'dropbox_upload_failed',
					'file',
					0,
					array(
						'file_path' => $file_path,
						'error'     => $e->getMessage(),
						'severity'  => 'error',
					)
				);
			}

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Create Dropbox share link.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $path         Dropbox file path.
	 * @param  string $access_token Dropbox access token.
	 * @return array Share link result.
	 */
	private static function create_dropbox_share_link( $path, $access_token ) {
		$url = self::DROPBOX_API_URL . '/sharing/create_shared_link_with_settings';

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'path' => $path,
					)
				),
				'timeout' => 15,
			)
		);

		$http_code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_wp_error( $response ) && 200 === $http_code && isset( $body['url'] ) ) {
			return array( 'url' => $body['url'] );
		}

		return array();
	}

	/**
	 * Send Telegram message.
	 *
	 * Sends a message via Telegram Bot API.
	 *
	 * Usage:
	 * ```php
	 * $result = AQOP_Integrations_Hub::send_telegram(
	 *     '@sales_team',
	 *     '<b>New Lead!</b>\nName: John Doe\nPhone: +123456789'
	 * );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $chat_id    Chat ID or channel username.
	 * @param  string $message    Message text.
	 * @param  string $parse_mode Optional. Parse mode (HTML, Markdown). Default 'HTML'.
	 * @param  array  $config     Optional. Override config. Default empty array.
	 * @return array Send result array.
	 */
	public static function send_telegram( $chat_id, $message, $parse_mode = 'HTML', $config = array() ) {
		try {
			$telegram_config = self::get_integration_config( 'telegram' );

			if ( ! empty( $config ) ) {
				$telegram_config = array_merge( $telegram_config, $config );
			}

			if ( empty( $telegram_config['bot_token'] ) ) {
				throw new Exception( 'Telegram bot token not configured' );
			}

			$url = self::TELEGRAM_API_URL . '/bot' . $telegram_config['bot_token'] . '/sendMessage';

			$response = wp_remote_post(
				$url,
				array(
					'body'    => array(
						'chat_id'    => $chat_id,
						'text'       => $message,
						'parse_mode' => $parse_mode,
					),
					'timeout' => 15,
				)
			);

			$http_code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_wp_error( $response ) || ! isset( $body['ok'] ) || ! $body['ok'] ) {
				$error_message = is_wp_error( $response ) ? $response->get_error_message() : ( $body['description'] ?? 'Unknown error' );
				throw new Exception( $error_message );
			}

			$message_id = $body['result']['message_id'] ?? 0;

			// Log success.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'telegram_message_sent',
					'notification',
					0,
					array(
						'chat_id'    => $chat_id,
						'message_id' => $message_id,
						'severity'   => 'info',
					)
				);
			}

			return array(
				'success'    => true,
				'message_id' => $message_id,
				'message'    => 'Message sent successfully',
			);

		} catch ( Exception $e ) {
			// Log failure.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'telegram_message_failed',
					'notification',
					0,
					array(
						'chat_id'  => $chat_id,
						'error'    => $e->getMessage(),
						'severity' => 'error',
					)
				);
			}

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Send webhook.
	 *
	 * Sends data to a webhook URL.
	 *
	 * Usage:
	 * ```php
	 * $result = AQOP_Integrations_Hub::send_webhook(
	 *     'https://hooks.example.com/webhook',
	 *     array( 'event' => 'lead_created', 'data' => $lead_data )
	 * );
	 * ```
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $url     Webhook URL.
	 * @param  array  $payload Data to send.
	 * @param  string $method  Optional. HTTP method. Default 'POST'.
	 * @param  array  $headers Optional. Custom headers. Default empty array.
	 * @return array Webhook result array.
	 */
	public static function send_webhook( $url, $payload, $method = 'POST', $headers = array() ) {
		$start_time = microtime( true );

		try {
			$default_headers = array(
				'Content-Type' => 'application/json',
			);

			$args = array(
				'method'  => strtoupper( $method ),
				'headers' => array_merge( $default_headers, $headers ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			);

			$response = wp_remote_request( $url, $args );

			$http_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$duration_ms = ( microtime( true ) - $start_time ) * 1000;

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			// Log webhook.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'webhook_sent',
					'webhook',
					0,
					array(
						'url'         => $url,
						'method'      => $method,
						'http_code'   => $http_code,
						'duration_ms' => $duration_ms,
						'severity'    => ( $http_code >= 200 && $http_code < 300 ) ? 'info' : 'warning',
					)
				);
			}

			return array(
				'success'   => ( $http_code >= 200 && $http_code < 300 ),
				'http_code' => $http_code,
				'response'  => json_decode( $response_body, true ),
				'message'   => ( $http_code >= 200 && $http_code < 300 ) ? 'Webhook sent successfully' : 'Webhook failed',
			);

		} catch ( Exception $e ) {
			// Log failure.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'core',
					'webhook_failed',
					'webhook',
					0,
					array(
						'url'      => $url,
						'error'    => $e->getMessage(),
						'severity' => 'error',
					)
				);
			}

			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Check integration health.
	 *
	 * Tests connection to an integration service.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $integration Integration name (airtable, dropbox, telegram).
	 * @return array Health status array.
	 */
	public static function check_integration_health( $integration ) {
		$status = array(
			'status'       => 'error',
			'message'      => 'Unknown integration',
			'last_checked' => current_time( 'mysql' ),
		);

		try {
			switch ( $integration ) {
				case 'airtable':
					$config = self::get_integration_config( 'airtable' );
					if ( ! empty( $config['api_key'] ) && ! empty( $config['base_id'] ) ) {
						// Try to list bases (lightweight check).
						$response = wp_remote_get(
							self::AIRTABLE_API_URL . '/meta/bases',
							array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $config['api_key'],
								),
								'timeout' => 10,
							)
						);
						$http_code = wp_remote_retrieve_response_code( $response );
						$status['status'] = ( ! is_wp_error( $response ) && $http_code === 200 ) ? 'ok' : 'error';
						$status['message'] = ( 'ok' === $status['status'] ) ? 'Connected' : 'Connection failed';
					} else {
						$status['message'] = 'Not configured';
					}
					break;

				case 'dropbox':
					$config = self::get_integration_config( 'dropbox' );
					if ( ! empty( $config['access_token'] ) ) {
						// Get account info (lightweight check).
						$response = wp_remote_post(
							self::DROPBOX_API_URL . '/users/get_current_account',
							array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $config['access_token'],
								),
								'timeout' => 10,
							)
						);
						$http_code = wp_remote_retrieve_response_code( $response );
						$status['status'] = ( ! is_wp_error( $response ) && $http_code === 200 ) ? 'ok' : 'error';
						$status['message'] = ( 'ok' === $status['status'] ) ? 'Connected' : 'Connection failed';
					} else {
						$status['message'] = 'Not configured';
					}
					break;

				case 'telegram':
					$config = self::get_integration_config( 'telegram' );
					if ( ! empty( $config['bot_token'] ) ) {
						// Get bot info.
						$response = wp_remote_get(
							self::TELEGRAM_API_URL . '/bot' . $config['bot_token'] . '/getMe',
							array( 'timeout' => 10 )
						);
						$body = json_decode( wp_remote_retrieve_body( $response ), true );
						$status['status'] = ( ! is_wp_error( $response ) && isset( $body['ok'] ) && $body['ok'] ) ? 'ok' : 'error';
						$status['message'] = ( 'ok' === $status['status'] ) ? 'Connected' : 'Connection failed';
					} else {
						$status['message'] = 'Not configured';
					}
					break;
			}
		} catch ( Exception $e ) {
			$status['message'] = $e->getMessage();
		}

		// Cache status.
		self::cache_integration_status( $integration, $status );

		return $status;
	}

	/**
	 * Get integration status.
	 *
	 * Returns cached integration status or checks health if cache expired.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $integration Integration name.
	 * @return array Status array.
	 */
	public static function get_integration_status( $integration ) {
		$cache_key = 'aqop_integration_status_' . $integration;
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Check health if not cached.
		return self::check_integration_health( $integration );
	}

	/**
	 * Retry with exponential backoff.
	 *
	 * Executes a callback with retry logic and exponential backoff.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  callable $callback    Callback function to execute.
	 * @param  int      $max_retries Maximum retry attempts. Default 3.
	 * @return mixed Callback result.
	 * @throws Exception If all retries fail.
	 */
	private static function retry_with_backoff( $callback, $max_retries = 3 ) {
		$attempt = 0;
		$last_exception = null;

		while ( $attempt < $max_retries ) {
			try {
				return $callback();
			} catch ( Exception $e ) {
				$last_exception = $e;
				$attempt++;

				if ( $attempt < $max_retries ) {
					// Exponential backoff: 1s, 2s, 4s.
					$sleep_seconds = pow( 2, $attempt - 1 );
					sleep( $sleep_seconds );
				}
			}
		}

		// All retries failed.
		if ( $last_exception ) {
			throw $last_exception;
		}

		throw new Exception( 'All retry attempts failed' );
	}

	/**
	 * Transform field for Airtable.
	 *
	 * Converts WordPress field value to Airtable-compatible format.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  mixed $value Field value.
	 * @return mixed Transformed value.
	 */
	private static function transform_field_for_airtable( $value ) {
		// Handle null.
		if ( null === $value ) {
			return null;
		}

		// Handle dates - convert to ISO 8601.
		if ( $value instanceof DateTime ) {
			return $value->format( 'c' );
		}

		// Handle date strings.
		if ( is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', $value ) ) {
			$datetime = new DateTime( $value );
			return $datetime->format( 'c' );
		}

		// Handle arrays (for multiselect, attachments).
		if ( is_array( $value ) ) {
			return $value;
		}

		// Handle booleans.
		if ( is_bool( $value ) ) {
			return $value;
		}

		// Handle numbers.
		if ( is_numeric( $value ) ) {
			return $value + 0; // Convert to int or float.
		}

		// Return as string.
		return (string) $value;
	}

	/**
	 * Get integration configuration.
	 *
	 * Retrieves configuration from wp-config.php constants.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $integration Integration name.
	 * @return array Configuration array.
	 */
	private static function get_integration_config( $integration ) {
		$config = array();

		switch ( $integration ) {
			case 'airtable':
				$config = array(
					'api_key'    => defined( 'AQOP_AIRTABLE_API_KEY' ) ? AQOP_AIRTABLE_API_KEY : '',
					'base_id'    => defined( 'AQOP_AIRTABLE_BASE_ID' ) ? AQOP_AIRTABLE_BASE_ID : '',
					'table_name' => defined( 'AQOP_AIRTABLE_TABLE_NAME' ) ? AQOP_AIRTABLE_TABLE_NAME : '',
				);
				break;

			case 'dropbox':
				$config = array(
					'access_token' => defined( 'AQOP_DROPBOX_ACCESS_TOKEN' ) ? AQOP_DROPBOX_ACCESS_TOKEN : '',
				);
				break;

			case 'telegram':
				$config = array(
					'bot_token' => defined( 'AQOP_TELEGRAM_BOT_TOKEN' ) ? AQOP_TELEGRAM_BOT_TOKEN : '',
				);
				break;
		}

		return $config;
	}

	/**
	 * Cache integration status.
	 *
	 * Stores integration status in transient cache.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $integration Integration name.
	 * @param  array  $status      Status array.
	 * @param  int    $expiry      Optional. Cache expiry in seconds. Default 300 (5 minutes).
	 * @return bool True on success.
	 */
	private static function cache_integration_status( $integration, $status, $expiry = 300 ) {
		$cache_key = 'aqop_integration_status_' . $integration;
		return set_transient( $cache_key, $status, $expiry );
	}
}

