<?php
/**
 * Lead Details Handler.
 *
 * Provides helper methods for retrieving and preparing lead detail data.
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Lead_Details_Handler class.
 *
 * Aggregates lead data, notes, and presentation helpers for the lead detail view.
 *
 * @since 1.0.0
 */
class AQOP_Lead_Details_Handler {

	/**
	 * Retrieve a lead along with its notes.
	 *
	 * @since  1.0.0
	 * @param  int $lead_id Lead identifier.
	 * @return array|WP_Error Array with 'lead' object and 'notes' collection on success, WP_Error otherwise.
	 */
	public static function get_lead_with_notes( $lead_id ) {
		$lead_id = absint( $lead_id );

		if ( ! $lead_id ) {
			return new WP_Error( 'invalid_lead_id', __( 'معرف العميل المحتمل غير صالح.', 'aqop-leads' ) );
		}

		$lead = AQOP_Leads_Manager::get_lead( $lead_id );

		if ( ! $lead ) {
			return new WP_Error( 'lead_not_found', __( 'لم يتم العثور على العميل المحتمل المطلوب.', 'aqop-leads' ) );
		}

		global $wpdb;

		$notes_table = $wpdb->prefix . 'aq_leads_notes';
		$users_table = $wpdb->users;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					n.id,
					n.lead_id,
					n.user_id,
					n.note_text,
					n.created_at,
					u.display_name AS user_name,
					u.user_email   AS user_email
				FROM {$notes_table} n
				LEFT JOIN {$users_table} u ON n.user_id = u.ID
				WHERE n.lead_id = %d
				ORDER BY n.created_at DESC",
				$lead_id
			)
		);

		if ( null === $notes && $wpdb->last_error ) {
			return new WP_Error(
				'lead_notes_query_failed',
				sprintf(
					/* translators: %s database error message */
					__( 'تعذر جلب الملاحظات بسبب خطأ في قاعدة البيانات: %s', 'aqop-leads' ),
					$wpdb->last_error
				)
			);
		}

		return array(
			'lead'  => $lead,
			'notes' => $notes ? $notes : array(),
		);
	}

	/**
	 * Determine whether a user can view a given lead.
	 *
	 * @since  1.0.0
	 * @param  int|null $user_id Optional user ID. Defaults to current user.
	 * @param  int      $lead_id Lead identifier (unused for now but available for granular logic).
	 * @return bool True if the user can view the lead.
	 */
	public static function can_user_view_lead( $user_id = null, $lead_id = 0 ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		/**
		 * Capability list used to determine access.
		 *
		 * @since 1.0.0
		 * @param array $caps Default capabilities.
		 * @param int   $lead_id Lead ID.
		 */
		$required_caps = apply_filters(
			'aqop_leads_view_capabilities',
			array(
				'manage_leads',
				'manage_options',
				'operation_admin',
				'operation_manager',
			),
			$lead_id
		);

		foreach ( $required_caps as $capability ) {
			if ( user_can( $user_id, $capability ) ) {
				/**
				 * Fires when a user is allowed to view a lead.
				 *
				 * @since 1.0.0
				 *
				 * @param int $user_id User ID.
				 * @param int $lead_id Lead ID.
				 */
				do_action( 'aqop_leads_user_view_granted', $user_id, $lead_id );

				return true;
			}
		}

		/**
		 * Fires when a user is denied viewing a lead.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id User ID.
		 * @param int $lead_id Lead ID.
		 */
		do_action( 'aqop_leads_user_view_denied', $user_id, $lead_id );

		return false;
	}

	/**
	 * Prepare lead data for display in templates.
	 *
	 * @since  1.0.0
	 * @param  object|array $lead_data Raw lead data.
	 * @return array Formatted data ready for output.
	 */
	public static function format_lead_for_display( $lead_data ) {
		if ( empty( $lead_data ) ) {
			return array();
		}

		$lead = (array) $lead_data;
		
		// Check cache first
		if ( isset( $lead['id'] ) ) {
			$cache_key = 'aqop_lead_formatted_' . absint( $lead['id'] );
			$cached = wp_cache_get( $cache_key, 'aqop_leads' );
			
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$format_date = static function( $datetime ) {
			if ( empty( $datetime ) || '0000-00-00 00:00:00' === $datetime ) {
				return __( 'غير متوفر', 'aqop-leads' );
			}

			$timestamp = strtotime( $datetime );

			return $timestamp ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : __( 'غير متوفر', 'aqop-leads' );
		};

		$status_label = isset( $lead['status_name_en'] ) ? $lead['status_name_en'] : __( 'غير مصنف', 'aqop-leads' );
		$status_color = isset( $lead['status_color'] ) ? $lead['status_color'] : '#718096';

		$status_badge = sprintf(
			'<span class="aqop-status-badge" style="background-color:%1$s;">%2$s</span>',
			esc_attr( $status_color ),
			esc_html( $status_label )
		);

		$email = isset( $lead['email'] ) && is_email( $lead['email'] ) ? $lead['email'] : '';
		$phone = isset( $lead['phone'] ) ? preg_replace( '/\s+/', '', $lead['phone'] ) : '';

		$formatted = array(
			'id'                   => isset( $lead['id'] ) ? absint( $lead['id'] ) : 0,
			'name'                 => isset( $lead['name'] ) ? esc_html( $lead['name'] ) : '',
			'status_badge'         => $status_badge,
			'priority'             => isset( $lead['priority'] ) ? esc_html( ucfirst( $lead['priority'] ) ) : '',
			'country'              => isset( $lead['country_name_en'] ) ? esc_html( $lead['country_name_en'] ) : '',
			'source'               => isset( $lead['source_name'] ) ? esc_html( $lead['source_name'] ) : '',
			'campaign_name'        => self::get_campaign_name( isset( $lead['campaign_id'] ) ? $lead['campaign_id'] : 0 ),
			'assigned_to'          => isset( $lead['assigned_user_name'] ) ? esc_html( $lead['assigned_user_name'] ) : __( 'غير معيّن', 'aqop-leads' ),
			'email_link'           => $email ? sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) ) : __( 'غير متوفر', 'aqop-leads' ),
			'phone_link'           => $phone ? sprintf( '<a href="tel:%1$s">%2$s</a>', esc_attr( $phone ), esc_html( $lead['phone'] ) ) : __( 'غير متوفر', 'aqop-leads' ),
			'whatsapp_link'        => ! empty( $lead['whatsapp'] ) ? sprintf( '<a href="https://wa.me/%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_attr( preg_replace( '/\D+/', '', $lead['whatsapp'] ) ), esc_html( $lead['whatsapp'] ) ) : __( 'غير متوفر', 'aqop-leads' ),
			'created_date'         => $format_date( isset( $lead['created_at'] ) ? $lead['created_at'] : '' ),
			'updated_date'         => $format_date( isset( $lead['updated_at'] ) ? $lead['updated_at'] : '' ),
			'last_contact_display' => $format_date( isset( $lead['last_contact_at'] ) ? $lead['last_contact_at'] : '' ),
			'airtable_record_id'   => isset( $lead['airtable_record_id'] ) ? esc_html( $lead['airtable_record_id'] ) : '',
			'raw'                  => $lead,
		);

		/**
		 * Filter formatted lead data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array $formatted Formatted data.
		 * @param array $lead      Raw lead array.
		 */
		$formatted = apply_filters( 'aqop_lead_formatted_data', $formatted, $lead );
		
		// Cache for 5 minutes
		if ( isset( $lead['id'] ) ) {
			wp_cache_set( $cache_key, $formatted, 'aqop_leads', 300 );
		}
		
		return $formatted;
	}

	/**
	 * Get campaign name by ID.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  int $campaign_id Campaign ID.
	 * @return string Campaign name or empty string.
	 */
	private static function get_campaign_name( $campaign_id ) {
		if ( ! $campaign_id ) {
			return '';
		}
		
		global $wpdb;
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT name FROM {$wpdb->prefix}aq_leads_campaigns WHERE id = %d",
				$campaign_id
			)
		);
		
		return $name ? esc_html( $name ) : '';
	}
}

