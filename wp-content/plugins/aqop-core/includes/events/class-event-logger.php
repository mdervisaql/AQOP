<?php
/**
 * Event Logger Class
 *
 * Core event tracking system for Operation Platform.
 * Logs all platform activities with temporal dimensions for analytics.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Event_Logger class.
 *
 * Handles all event logging, retrieval, and analytics queries.
 * Implements efficient caching and temporal calculations.
 *
 * @since 1.0.0
 */
class AQOP_Event_Logger {

	/**
	 * Cache for module IDs.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private static $module_cache = array();

	/**
	 * Cache for event type IDs.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private static $event_type_cache = array();

	/**
	 * Log an event.
	 *
	 * Creates a new event record with auto-calculated temporal fields.
	 * Triggers action hook for extensibility.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $module       Module code (e.g., 'leads', 'training').
	 * @param  string $event_type   Event type code (e.g., 'lead_created').
	 * @param  string $object_type  Object type being tracked (e.g., 'lead', 'session').
	 * @param  int    $object_id    Object ID.
	 * @param  array  $payload      Optional. Additional event data. Default empty array.
	 * @return int|false Event ID on success, false on failure.
	 */
	public static function log( $module, $event_type, $object_type, $object_id, $payload = array() ) {
		global $wpdb;

		try {
			// Get module ID.
			$module_id = self::get_module_id( $module );
			if ( ! $module_id ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'AQOP Event Logger: Invalid module "%s"', $module ) );
				return false;
			}

			// Get or create event type ID.
			$event_name = isset( $payload['event_name'] ) ? $payload['event_name'] : ucwords( str_replace( '_', ' ', $event_type ) );
			$event_type_id = self::get_or_create_event_type( $module, $event_type, $event_name );
			if ( ! $event_type_id ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'AQOP Event Logger: Failed to get/create event type "%s" for module "%s"', $event_type, $module ) );
				return false;
			}

			// Get current user ID.
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				$user_id = 0; // System user for cron/automated tasks.
			}

			// Get country ID from payload if provided.
			$country_id = null;
			if ( isset( $payload['country_code'] ) ) {
				$country_id = self::get_country_id( $payload['country_code'] );
			}

			// Calculate temporal fields.
			$temporal = self::calculate_temporal_fields();

			// Get IP address and user agent.
			$ip_address = self::get_client_ip();
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

			// Prepare payload JSON.
			$payload_json = ! empty( $payload ) ? wp_json_encode( $payload ) : null;

			// Insert event.
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'aq_events_log',
				array(
					'module_id'     => $module_id,
					'event_type_id' => $event_type_id,
					'user_id'       => $user_id,
					'country_id'    => $country_id,
					'object_type'   => $object_type,
					'object_id'     => $object_id,
					'created_at'    => $temporal['created_at'],
					'date_key'      => $temporal['date_key'],
					'time_key'      => $temporal['time_key'],
					'hour'          => $temporal['hour'],
					'day_of_week'   => $temporal['day_of_week'],
					'week_of_year'  => $temporal['week_of_year'],
					'month'         => $temporal['month'],
					'quarter'       => $temporal['quarter'],
					'year'          => $temporal['year'],
					'duration_ms'   => isset( $payload['duration_ms'] ) ? absint( $payload['duration_ms'] ) : null,
					'payload_json'  => $payload_json,
					'ip_address'    => $ip_address,
					'user_agent'    => $user_agent,
				),
				array(
					'%d', // module_id.
					'%d', // event_type_id.
					'%d', // user_id.
					'%d', // country_id.
					'%s', // object_type.
					'%d', // object_id.
					'%s', // created_at.
					'%d', // date_key.
					'%d', // time_key.
					'%d', // hour.
					'%d', // day_of_week.
					'%d', // week_of_year.
					'%d', // month.
					'%d', // quarter.
					'%d', // year.
					'%d', // duration_ms.
					'%s', // payload_json.
					'%s', // ip_address.
					'%s', // user_agent.
				)
			);

			if ( false === $inserted ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'AQOP Event Logger: Failed to insert event - ' . $wpdb->last_error );
				return false;
			}

			$event_id = $wpdb->insert_id;

			/**
			 * Fires after an event has been logged.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $event_id   The event ID.
			 * @param string $module     Module code.
			 * @param string $event_type Event type code.
			 * @param array  $payload    Event payload data.
			 */
			do_action( 'aqop_event_logged', $event_id, $module, $event_type, $payload );

			return $event_id;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Event Logger Exception: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get events for a specific object.
	 *
	 * Retrieves event history with user and module details.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string $object_type Object type.
	 * @param  int    $object_id   Object ID.
	 * @param  array  $args        Optional. Query arguments. Default empty array.
	 * @return array Array of event objects.
	 */
	public static function get_events( $object_type, $object_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize order.
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Sanitize orderby.
		$allowed_orderby = array( 'created_at', 'id', 'event_type_id' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';

		$query = $wpdb->prepare(
			"SELECT 
				e.*,
				u.display_name as user_name,
				m.module_name,
				et.event_name,
				et.event_code,
				et.severity
			FROM {$wpdb->prefix}aq_events_log e
			LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
			LEFT JOIN {$wpdb->prefix}aq_dim_modules m ON e.module_id = m.id
			LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
			WHERE e.object_type = %s AND e.object_id = %d
			ORDER BY e.{$orderby} {$order}
			LIMIT %d OFFSET %d",
			$object_type,
			$object_id,
			absint( $args['limit'] ),
			absint( $args['offset'] )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query );

		// Decode JSON payloads.
		if ( $results ) {
			foreach ( $results as $event ) {
				if ( $event->payload_json ) {
					$event->payload = json_decode( $event->payload_json, true );
				} else {
					$event->payload = array();
				}
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get event statistics.
	 *
	 * Returns event counts grouped by date and event type.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  string|null $module Optional. Module code to filter by. Default null (all modules).
	 * @param  int         $days   Optional. Number of days to look back. Default 7.
	 * @return array Array of statistics.
	 */
	public static function get_stats( $module = null, $days = 7 ) {
		global $wpdb;

		// Calculate date range.
		$end_date = current_time( 'Y-m-d' );
		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days", strtotime( $end_date ) ) );

		$start_date_key = (int) gmdate( 'Ymd', strtotime( $start_date ) );
		$end_date_key = (int) gmdate( 'Ymd', strtotime( $end_date ) );

		if ( $module ) {
			$module_id = self::get_module_id( $module );
			if ( ! $module_id ) {
				return array();
			}

			$query = $wpdb->prepare(
				"SELECT 
					d.full_date as date,
					et.event_code as event_type,
					et.event_name,
					COUNT(e.id) as count
				FROM {$wpdb->prefix}aq_events_log e
				LEFT JOIN {$wpdb->prefix}aq_dim_date d ON e.date_key = d.date_key
				LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
				WHERE e.date_key BETWEEN %d AND %d
				AND e.module_id = %d
				GROUP BY d.full_date, et.event_code, et.event_name
				ORDER BY d.full_date ASC, count DESC",
				$start_date_key,
				$end_date_key,
				$module_id
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT 
					d.full_date as date,
					et.event_code as event_type,
					et.event_name,
					COUNT(e.id) as count
				FROM {$wpdb->prefix}aq_events_log e
				LEFT JOIN {$wpdb->prefix}aq_dim_date d ON e.date_key = d.date_key
				LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
				WHERE e.date_key BETWEEN %d AND %d
				GROUP BY d.full_date, et.event_code, et.event_name
				ORDER BY d.full_date ASC, count DESC",
				$start_date_key,
				$end_date_key
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query );

		return $results ? $results : array();
	}

	/**
	 * Advanced event query.
	 *
	 * Query events with multiple filters, pagination, and caching.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  array $args Query arguments.
	 * @return array Query results with pagination info.
	 */
	public static function query( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'module'      => null,
			'event_type'  => null,
			'date_from'   => null,
			'date_to'     => null,
			'user_id'     => null,
			'country'     => null,
			'object_type' => null,
			'object_id'   => null,
			'limit'       => 50,
			'offset'      => 0,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Generate cache key.
		$cache_key = 'aqop_events_' . md5( wp_json_encode( $args ) );
		$cached = wp_cache_get( $cache_key, 'aqop_events' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Build WHERE clause.
		$where_clauses = array();
		$where_values = array();

		if ( $args['module'] ) {
			$module_id = self::get_module_id( $args['module'] );
			if ( $module_id ) {
				$where_clauses[] = 'e.module_id = %d';
				$where_values[] = $module_id;
			}
		}

		if ( $args['event_type'] ) {
			$where_clauses[] = 'et.event_code = %s';
			$where_values[] = $args['event_type'];
		}

		if ( $args['date_from'] ) {
			$date_from_key = (int) gmdate( 'Ymd', strtotime( $args['date_from'] ) );
			$where_clauses[] = 'e.date_key >= %d';
			$where_values[] = $date_from_key;
		}

		if ( $args['date_to'] ) {
			$date_to_key = (int) gmdate( 'Ymd', strtotime( $args['date_to'] ) );
			$where_clauses[] = 'e.date_key <= %d';
			$where_values[] = $date_to_key;
		}

		if ( $args['user_id'] ) {
			$where_clauses[] = 'e.user_id = %d';
			$where_values[] = absint( $args['user_id'] );
		}

		if ( $args['country'] ) {
			$country_id = self::get_country_id( $args['country'] );
			if ( $country_id ) {
				$where_clauses[] = 'e.country_id = %d';
				$where_values[] = $country_id;
			}
		}

		if ( $args['object_type'] ) {
			$where_clauses[] = 'e.object_type = %s';
			$where_values[] = $args['object_type'];
		}

		if ( $args['object_id'] ) {
			$where_clauses[] = 'e.object_id = %d';
			$where_values[] = absint( $args['object_id'] );
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Sanitize order.
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Sanitize orderby.
		$allowed_orderby = array( 'created_at', 'id', 'date_key', 'user_id' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';

		// Build count query.
		$count_sql = "SELECT COUNT(e.id)
			FROM {$wpdb->prefix}aq_events_log e
			LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
			{$where_sql}";

		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $count_sql );
		}

		// Build main query.
		$query_values = $where_values;
		$query_values[] = absint( $args['limit'] );
		$query_values[] = absint( $args['offset'] );

		$main_sql = "SELECT 
				e.*,
				u.display_name as user_name,
				m.module_name,
				m.module_code,
				et.event_name,
				et.event_code,
				et.severity,
				c.country_name_en,
				c.country_code
			FROM {$wpdb->prefix}aq_events_log e
			LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
			LEFT JOIN {$wpdb->prefix}aq_dim_modules m ON e.module_id = m.id
			LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
			LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON e.country_id = c.id
			{$where_sql}
			ORDER BY e.{$orderby} {$order}
			LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $main_sql, $query_values ) );

		// Decode JSON payloads.
		if ( $results ) {
			foreach ( $results as $event ) {
				if ( $event->payload_json ) {
					$event->payload = json_decode( $event->payload_json, true );
				} else {
					$event->payload = array();
				}
			}
		}

		// Calculate total pages.
		$pages = $args['limit'] > 0 ? ceil( $total / $args['limit'] ) : 1;

		$response = array(
			'results' => $results ? $results : array(),
			'total'   => (int) $total,
			'pages'   => (int) $pages,
			'limit'   => (int) $args['limit'],
			'offset'  => (int) $args['offset'],
		);

		// Cache for 5 minutes.
		wp_cache_set( $cache_key, $response, 'aqop_events', 300 );

		return $response;
	}

	/**
	 * Count events today.
	 *
	 * Returns the count of events logged today.
	 *
	 * @since  1.0.0
	 * @static
	 * @return int Event count.
	 */
	public static function count_events_today() {
		global $wpdb;

		$today_key = (int) current_time( 'Ymd' );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}aq_events_log WHERE date_key = %d",
				$today_key
			)
		);

		return (int) $count;
	}

	/**
	 * Count errors in last 24 hours.
	 *
	 * Returns the count of error and critical events in the last 24 hours.
	 *
	 * @since  1.0.0
	 * @static
	 * @return int Error count.
	 */
	public static function count_errors_24h() {
		global $wpdb;

		$time_24h_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$wpdb->prefix}aq_events_log e
				LEFT JOIN {$wpdb->prefix}aq_dim_event_types et ON e.event_type_id = et.id
				WHERE e.created_at >= %s
				AND et.severity IN ('error', 'critical')",
				$time_24h_ago
			)
		);

		return (int) $count;
	}

	/**
	 * Get module ID by code.
	 *
	 * Retrieves module ID from dimension table with caching.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $module_code Module code.
	 * @return int|null Module ID or null if not found.
	 */
	private static function get_module_id( $module_code ) {
		// Check cache.
		if ( isset( self::$module_cache[ $module_code ] ) ) {
			return self::$module_cache[ $module_code ];
		}

		global $wpdb;

		$module_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_modules WHERE module_code = %s AND is_active = 1",
				$module_code
			)
		);

		if ( $module_id ) {
			self::$module_cache[ $module_code ] = (int) $module_id;
			return (int) $module_id;
		}

		return null;
	}

	/**
	 * Get or create event type ID.
	 *
	 * Retrieves event type ID, creating it if it doesn't exist.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string      $module_code Module code.
	 * @param  string      $event_code  Event type code.
	 * @param  string|null $event_name  Optional. Event display name. Default null.
	 * @return int|null Event type ID or null on failure.
	 */
	private static function get_or_create_event_type( $module_code, $event_code, $event_name = null ) {
		$cache_key = $module_code . ':' . $event_code;

		// Check cache.
		if ( isset( self::$event_type_cache[ $cache_key ] ) ) {
			return self::$event_type_cache[ $cache_key ];
		}

		global $wpdb;

		$module_id = self::get_module_id( $module_code );
		if ( ! $module_id ) {
			return null;
		}

		// Try to get existing event type.
		$event_type_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_event_types WHERE module_id = %d AND event_code = %s",
				$module_id,
				$event_code
			)
		);

		if ( $event_type_id ) {
			self::$event_type_cache[ $cache_key ] = (int) $event_type_id;
			return (int) $event_type_id;
		}

		// Create new event type.
		if ( ! $event_name ) {
			$event_name = ucwords( str_replace( '_', ' ', $event_code ) );
		}

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'aq_dim_event_types',
			array(
				'module_id'      => $module_id,
				'event_code'     => $event_code,
				'event_name'     => $event_name,
				'event_category' => null,
				'severity'       => 'info',
				'is_active'      => 1,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( false !== $inserted ) {
			$event_type_id = $wpdb->insert_id;
			self::$event_type_cache[ $cache_key ] = (int) $event_type_id;
			return (int) $event_type_id;
		}

		return null;
	}

	/**
	 * Get country ID by country code.
	 *
	 * Retrieves country ID from dimension table.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string $country_code Country code (e.g., 'SA', 'AE').
	 * @return int|null Country ID or null if not found.
	 */
	private static function get_country_id( $country_code ) {
		global $wpdb;

		$country_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_dim_countries WHERE country_code = %s AND is_active = 1",
				$country_code
			)
		);

		return $country_id ? (int) $country_id : null;
	}

	/**
	 * Calculate temporal fields.
	 *
	 * Calculates all temporal dimensions for an event.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  string|null $datetime Optional. DateTime string. Default null (current time).
	 * @return array Temporal fields array.
	 */
	private static function calculate_temporal_fields( $datetime = null ) {
		if ( ! $datetime ) {
			$dt = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
		} else {
			$dt = new DateTime( $datetime, new DateTimeZone( wp_timezone_string() ) );
		}

		$date_key = (int) $dt->format( 'Ymd' );
		$time_key = (int) $dt->format( 'His' );
		$hour = (int) $dt->format( 'G' );
		$day_of_week_iso = (int) $dt->format( 'N' ); // 1=Monday, 7=Sunday.

		// Adjust to 1=Sunday, 7=Saturday.
		$day_of_week = ( 7 === $day_of_week_iso ) ? 1 : $day_of_week_iso + 1;

		$week_of_year = (int) $dt->format( 'W' );
		$month = (int) $dt->format( 'n' );
		$quarter = (int) ceil( $month / 3 );
		$year = (int) $dt->format( 'Y' );

		return array(
			'created_at'   => $dt->format( 'Y-m-d H:i:s' ),
			'date_key'     => $date_key,
			'time_key'     => $time_key,
			'hour'         => $hour,
			'day_of_week'  => $day_of_week,
			'week_of_year' => $week_of_year,
			'month'        => $month,
			'quarter'      => $quarter,
			'year'         => $year,
		);
	}

	/**
	 * Get client IP address.
	 *
	 * Retrieves the client's IP address, handling proxies.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @return string IP address.
	 */
	private static function get_client_ip() {
		$ip_address = '';

		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP.
		if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return $ip_address;
		}

		return '';
	}
}

