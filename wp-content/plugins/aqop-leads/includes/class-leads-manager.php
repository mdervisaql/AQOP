<?php
/**
 * Leads Manager Class
 *
 * Handles all CRUD operations for leads.
 * Integrates with Event Logger, Airtable, and Notifications.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Leads_Manager class.
 *
 * Main business logic for leads management.
 *
 * @since 1.0.0
 */
class AQOP_Leads_Manager {

	/**
	 * Create a new lead.
	 *
	 * Creates a lead record with event logging and Airtable sync.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  array $data Lead data array.
	 * @return int|false Lead ID on success, false on failure.
	 */
	public static function create_lead( $data ) {
		global $wpdb;

		try {
			// Validate required fields.
			if ( empty( $data['name'] ) ) {
				throw new Exception( 'Lead name is required' );
			}

			// Prepare lead data.
			$lead_data = array(
				'name'             => sanitize_text_field( $data['name'] ),
				'email'            => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : null,
				'phone'            => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : null,
				'whatsapp'         => isset( $data['whatsapp'] ) ? sanitize_text_field( $data['whatsapp'] ) : null,
				'country_id'       => isset( $data['country_id'] ) ? absint( $data['country_id'] ) : null,
				'source_id'        => isset( $data['source_id'] ) ? absint( $data['source_id'] ) : null,
				'campaign_id'      => isset( $data['campaign_id'] ) ? absint( $data['campaign_id'] ) : null,
				'status_id'        => isset( $data['status_id'] ) ? absint( $data['status_id'] ) : 1,
				'assigned_to'      => isset( $data['assigned_to'] ) ? absint( $data['assigned_to'] ) : null,
				'priority'         => isset( $data['priority'] ) ? $data['priority'] : 'medium',
				'notes'            => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
				'custom_fields'    => isset( $data['custom_fields'] ) ? wp_json_encode( $data['custom_fields'] ) : null,
				'created_at'       => current_time( 'mysql' ),
			);

			// Insert lead.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'aq_leads',
				$lead_data,
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new Exception( 'Failed to insert lead: ' . $wpdb->last_error );
			}

			$lead_id = $wpdb->insert_id;

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_created',
					'lead',
					$lead_id,
					array(
						'name'     => $lead_data['name'],
						'email'    => $lead_data['email'],
						'phone'    => $lead_data['phone'],
						'country'  => $lead_data['country_id'],
						'source'   => $lead_data['source_id'],
						'priority' => $lead_data['priority'],
					)
				);
			}

			// Sync to Airtable.
			self::sync_to_airtable( $lead_id );

			/**
			 * Fires after a lead has been created.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $lead_id Lead ID.
			 * @param array $data    Lead data.
			 */
			do_action( 'aqop_lead_created', $lead_id, $data );

			return $lead_id;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Update a lead.
	 *
	 * Updates lead data with event logging and Airtable sync.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int   $lead_id Lead ID.
	 * @param  array $data    Updated data.
	 * @return bool True on success, false on failure.
	 */
	public static function update_lead( $lead_id, $data ) {
		global $wpdb;

		try {
			$lead_id = absint( $lead_id );

			if ( ! $lead_id ) {
				throw new Exception( 'Invalid lead ID' );
			}

			// Get current lead data.
			$old_lead = self::get_lead( $lead_id );
			if ( ! $old_lead ) {
				throw new Exception( 'Lead not found' );
			}

			// Prepare update data.
			$update_data = array();
			$update_format = array();

			$allowed_fields = array( 'name', 'email', 'phone', 'whatsapp', 'country_id', 'source_id', 'campaign_id', 'status_id', 'assigned_to', 'priority', 'notes', 'custom_fields' );

			foreach ( $allowed_fields as $field ) {
				if ( isset( $data[ $field ] ) ) {
					if ( 'custom_fields' === $field ) {
						$update_data[ $field ] = wp_json_encode( $data[ $field ] );
						$update_format[] = '%s';
					} elseif ( in_array( $field, array( 'country_id', 'source_id', 'campaign_id', 'status_id', 'assigned_to' ), true ) ) {
						$update_data[ $field ] = absint( $data[ $field ] );
						$update_format[] = '%d';
					} else {
						$update_data[ $field ] = sanitize_text_field( $data[ $field ] );
						$update_format[] = '%s';
					}
				}
			}

			$update_data['updated_at'] = current_time( 'mysql' );
			$update_format[] = '%s';

			// Update lead.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$wpdb->prefix . 'aq_leads',
				$update_data,
				array( 'id' => $lead_id ),
				$update_format,
				array( '%d' )
			);

			if ( false === $updated ) {
				throw new Exception( 'Failed to update lead: ' . $wpdb->last_error );
			}

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_updated',
					'lead',
					$lead_id,
					array(
						'updated_fields' => array_keys( $update_data ),
						'old_data'       => $old_lead,
					)
				);
			}

			// Sync to Airtable.
			self::sync_to_airtable( $lead_id );

			/**
			 * Fires after a lead has been updated.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $lead_id Lead ID.
			 * @param array $data    Updated data.
			 * @param array $old_lead Old lead data.
			 */
			do_action( 'aqop_lead_updated', $lead_id, $data, $old_lead );

			return true;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get a lead.
	 *
	 * Retrieves complete lead data.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int $lead_id Lead ID.
	 * @return object|false Lead object or false if not found.
	 */
	public static function get_lead( $lead_id ) {
		global $wpdb;

		$lead_id = absint( $lead_id );

		if ( ! $lead_id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$lead = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					l.*,
					s.status_name_en,
					s.status_name_ar,
					s.color as status_color,
					src.source_name,
					c.country_name_en,
					c.country_name_ar,
					u.display_name as assigned_user_name
				FROM {$wpdb->prefix}aq_leads l
				LEFT JOIN {$wpdb->prefix}aq_leads_status s ON l.status_id = s.id
				LEFT JOIN {$wpdb->prefix}aq_leads_sources src ON l.source_id = src.id
				LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON l.country_id = c.id
				LEFT JOIN {$wpdb->users} u ON l.assigned_to = u.ID
				WHERE l.id = %d",
				$lead_id
			)
		);

		if ( $lead && $lead->custom_fields ) {
			$lead->custom_fields = json_decode( $lead->custom_fields, true );
		}

		return $lead ? $lead : false;
	}

	/**
	 * Delete a lead.
	 *
	 * Permanently deletes a lead and related data.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int $lead_id Lead ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_lead( $lead_id ) {
		global $wpdb;

		try {
			$lead_id = absint( $lead_id );

			if ( ! $lead_id ) {
				throw new Exception( 'Invalid lead ID' );
			}

			// Get lead data for logging.
			$lead = self::get_lead( $lead_id );
			if ( ! $lead ) {
				throw new Exception( 'Lead not found' );
			}

			// Delete notes.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->prefix . 'aq_leads_notes',
				array( 'lead_id' => $lead_id ),
				array( '%d' )
			);

			// Delete lead.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				$wpdb->prefix . 'aq_leads',
				array( 'id' => $lead_id ),
				array( '%d' )
			);

			if ( false === $deleted ) {
				throw new Exception( 'Failed to delete lead' );
			}

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_deleted',
					'lead',
					$lead_id,
					array(
						'name'  => $lead->name,
						'email' => $lead->email,
					)
				);
			}

			/**
			 * Fires after a lead has been deleted.
			 *
			 * @since 1.0.0
			 *
			 * @param int    $lead_id Lead ID.
			 * @param object $lead    Lead data before deletion.
			 */
			do_action( 'aqop_lead_deleted', $lead_id, $lead );

			return true;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Assign lead to user.
	 *
	 * Assigns a lead to a specific user.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int $lead_id Lead ID.
	 * @param  int $user_id User ID.
	 * @return bool True on success, false on failure.
	 */
	public static function assign_lead( $lead_id, $user_id ) {
		global $wpdb;

		try {
			$lead_id = absint( $lead_id );
			$user_id = absint( $user_id );

			if ( ! $lead_id || ! $user_id ) {
				throw new Exception( 'Invalid lead ID or user ID' );
			}

			// Verify user exists.
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				throw new Exception( 'User not found' );
			}

			// Update assignment.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$wpdb->prefix . 'aq_leads',
				array(
					'assigned_to' => $user_id,
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'id' => $lead_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				throw new Exception( 'Failed to assign lead' );
			}

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_assigned',
					'lead',
					$lead_id,
					array(
						'assigned_to'   => $user_id,
						'assigned_name' => $user->display_name,
					)
				);
			}

			// Sync to Airtable.
			self::sync_to_airtable( $lead_id );

			/**
			 * Fires after a lead has been assigned.
			 *
			 * @since 1.0.0
			 *
			 * @param int $lead_id Lead ID.
			 * @param int $user_id User ID.
			 */
			do_action( 'aqop_lead_assigned', $lead_id, $user_id );

			return true;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Change lead status.
	 *
	 * Updates lead status with event logging.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int $lead_id   Lead ID.
	 * @param  int $status_id New status ID.
	 * @return bool True on success, false on failure.
	 */
	public static function change_status( $lead_id, $status_id ) {
		global $wpdb;

		try {
			$lead_id = absint( $lead_id );
			$status_id = absint( $status_id );

			if ( ! $lead_id || ! $status_id ) {
				throw new Exception( 'Invalid lead ID or status ID' );
			}

			// Get old status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_status_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status_id FROM {$wpdb->prefix}aq_leads WHERE id = %d",
					$lead_id
				)
			);

			// Update status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$wpdb->prefix . 'aq_leads',
				array(
					'status_id'  => $status_id,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $lead_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				throw new Exception( 'Failed to update status' );
			}

			// Get status names.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_status_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status_name_en FROM {$wpdb->prefix}aq_leads_status WHERE id = %d",
					$old_status_id
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$new_status_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status_name_en FROM {$wpdb->prefix}aq_leads_status WHERE id = %d",
					$status_id
				)
			);

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_status_changed',
					'lead',
					$lead_id,
					array(
						'old_status_id'   => $old_status_id,
						'new_status_id'   => $status_id,
						'old_status_name' => $old_status_name,
						'new_status_name' => $new_status_name,
					)
				);
			}

			// Sync to Airtable.
			self::sync_to_airtable( $lead_id );

			/**
			 * Fires after lead status has been changed.
			 *
			 * @since 1.0.0
			 *
			 * @param int $lead_id   Lead ID.
			 * @param int $old_status_id Old status ID.
			 * @param int $new_status_id New status ID.
			 */
			do_action( 'aqop_lead_status_changed', $lead_id, $old_status_id, $status_id );

			return true;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Add note to lead.
	 *
	 * Adds a note to a lead.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int    $lead_id   Lead ID.
	 * @param  string $note_text Note text.
	 * @param  int    $user_id   Optional. User ID. Default current user.
	 * @return int|false Note ID on success, false on failure.
	 */
	public static function add_note( $lead_id, $note_text, $user_id = null ) {
		global $wpdb;

		try {
			$lead_id = absint( $lead_id );

			if ( ! $lead_id || empty( $note_text ) ) {
				throw new Exception( 'Invalid parameters' );
			}

			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			// Insert note.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'aq_leads_notes',
				array(
					'lead_id'    => $lead_id,
					'user_id'    => $user_id,
					'note_text'  => sanitize_textarea_field( $note_text ),
					'created_at' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( false === $inserted ) {
				throw new Exception( 'Failed to add note' );
			}

			$note_id = $wpdb->insert_id;

			// Update last_contact_at.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->prefix . 'aq_leads',
				array( 'last_contact_at' => current_time( 'mysql' ) ),
				array( 'id' => $lead_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Log event.
			if ( class_exists( 'AQOP_Event_Logger' ) ) {
				AQOP_Event_Logger::log(
					'leads',
					'lead_note_added',
					'lead',
					$lead_id,
					array(
						'note_id' => $note_id,
						'user_id' => $user_id,
					)
				);
			}

			/**
			 * Fires after a note has been added.
			 *
			 * @since 1.0.0
			 *
			 * @param int $note_id Note ID.
			 * @param int $lead_id Lead ID.
			 */
			do_action( 'aqop_lead_note_added', $note_id, $lead_id );

			return $note_id;

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'AQOP Leads Manager: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get lead notes.
	 *
	 * Retrieves all notes for a lead.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  int $lead_id Lead ID.
	 * @return array Array of note objects.
	 */
	public static function get_notes( $lead_id ) {
		global $wpdb;

		$lead_id = absint( $lead_id );

		if ( ! $lead_id ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					n.*,
					u.display_name as user_name
				FROM {$wpdb->prefix}aq_leads_notes n
				LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID
				WHERE n.lead_id = %d
				ORDER BY n.created_at DESC",
				$lead_id
			)
		);

		return $notes ? $notes : array();
	}

	/**
	 * Sync lead to Airtable.
	 *
	 * Syncs lead data to Airtable.
	 *
	 * @since  1.0.0
	 * @static
	 * @access private
	 * @param  int $lead_id Lead ID.
	 * @return bool True on success, false on failure.
	 */
	private static function sync_to_airtable( $lead_id ) {
		if ( ! class_exists( 'AQOP_Integrations_Hub' ) ) {
			return false;
		}

		$lead = self::get_lead( $lead_id );
		if ( ! $lead ) {
			return false;
		}

		// Prepare Airtable data.
		$airtable_data = array(
			'Name'           => $lead->name,
			'Email'          => $lead->email,
			'Phone'          => $lead->phone,
			'WhatsApp'       => $lead->whatsapp,
			'Country'        => $lead->country_name_en,
			'Status'         => $lead->status_name_en,
			'Source'         => $lead->source_name,
			'Priority'       => ucfirst( $lead->priority ),
			'Assigned To'    => $lead->assigned_user_name,
			'Created Date'   => $lead->created_at,
			'Last Updated'   => $lead->updated_at,
			'WordPress ID'   => $lead_id,
		);

		// Sync to Airtable.
		$result = AQOP_Integrations_Hub::sync_to_airtable( 'leads', $lead_id, $airtable_data );

		// Update airtable_record_id if successful.
		if ( $result['success'] && isset( $result['airtable_id'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->prefix . 'aq_leads',
				array( 'airtable_record_id' => $result['airtable_id'] ),
				array( 'id' => $lead_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $result['success'];
	}

	/**
	 * Query leads.
	 *
	 * Advanced lead querying with filters.
	 *
	 * @since  1.0.0
	 * @static
	 * @param  array $args Query arguments.
	 * @return array Query results.
	 */
	public static function query_leads( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'      => null,
			'country'     => null,
			'source'      => null,
			'assigned_to' => null,
			'priority'    => null,
			'search'      => null,
			'limit'       => 50,
			'offset'      => 0,
			'orderby'     => 'created_at',
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array();
		$where_values = array();

		if ( $args['status'] ) {
			$where_clauses[] = 'l.status_id = %d';
			$where_values[] = absint( $args['status'] );
		}

		if ( $args['country'] ) {
			$where_clauses[] = 'l.country_id = %d';
			$where_values[] = absint( $args['country'] );
		}

		if ( $args['source'] ) {
			$where_clauses[] = 'l.source_id = %d';
			$where_values[] = absint( $args['source'] );
		}

		if ( $args['assigned_to'] ) {
			$where_clauses[] = 'l.assigned_to = %d';
			$where_values[] = absint( $args['assigned_to'] );
		}

		if ( $args['priority'] ) {
			$where_clauses[] = 'l.priority = %s';
			$where_values[] = $args['priority'];
		}

		if ( $args['search'] ) {
			$where_clauses[] = '(l.name LIKE %s OR l.email LIKE %s OR l.phone LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		// Count total.
		$count_sql = "SELECT COUNT(l.id) FROM {$wpdb->prefix}aq_leads l {$where_sql}";

		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $count_sql );
		}

		// Get results.
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby = in_array( $args['orderby'], array( 'created_at', 'updated_at', 'name', 'id' ), true ) ? $args['orderby'] : 'created_at';

		$query_values = $where_values;
		$query_values[] = absint( $args['limit'] );
		$query_values[] = absint( $args['offset'] );

		$main_sql = "SELECT 
				l.*,
				s.status_name_en,
				s.status_name_ar,
				s.color as status_color,
				src.source_name,
				c.country_name_en,
				u.display_name as assigned_user_name
			FROM {$wpdb->prefix}aq_leads l
			LEFT JOIN {$wpdb->prefix}aq_leads_status s ON l.status_id = s.id
			LEFT JOIN {$wpdb->prefix}aq_leads_sources src ON l.source_id = src.id
			LEFT JOIN {$wpdb->prefix}aq_dim_countries c ON l.country_id = c.id
			LEFT JOIN {$wpdb->users} u ON l.assigned_to = u.ID
			{$where_sql}
			ORDER BY l.{$orderby} {$order}
			LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $main_sql, $query_values ) );

		return array(
			'results' => $results ? $results : array(),
			'total'   => (int) $total,
			'pages'   => $args['limit'] > 0 ? ceil( $total / $args['limit'] ) : 1,
		);
	}
}

