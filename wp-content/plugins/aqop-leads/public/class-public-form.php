<?php
/**
 * Public Lead Form
 *
 * Provides a shortcode-based lead capture form for website visitors.
 *
 * Usage:
 * [aqop_lead_form]
 * [aqop_lead_form source="facebook" campaign="summer_2024" redirect="/thank-you"]
 * [aqop_lead_form button_text="Get Started" show_whatsapp="no" show_country="yes"]
 *
 * @package AQOP_Leads
 * @since   1.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AQOP_Public_Form class.
 *
 * Handles public lead submission form functionality.
 *
 * @since 1.0.7
 */
class AQOP_Public_Form {

	/**
	 * Constructor.
	 *
	 * @since 1.0.7
	 */
	public function __construct() {
		// === PUBLIC FORM (Phase 3.2) ===
		add_shortcode( 'aqop_lead_form', array( $this, 'render_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_aqop_submit_lead_form', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_nopriv_aqop_submit_lead_form', array( $this, 'handle_form_submission' ) );
		// === END PUBLIC FORM ===
	}

	/**
	 * Render lead form shortcode.
	 *
	 * @since 1.0.7
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'source'        => 'website',
				'campaign'      => '',
				'redirect'      => '',
				'button_text'   => __( 'Submit', 'aqop-leads' ),
				'show_whatsapp' => 'yes',
				'show_country'  => 'yes',
			),
			$atts,
			'aqop_lead_form'
		);

		ob_start();
		?>
		<div class="aqop-lead-form-container" data-source="<?php echo esc_attr( $atts['source'] ); ?>" data-campaign="<?php echo esc_attr( $atts['campaign'] ); ?>" data-redirect="<?php echo esc_attr( $atts['redirect'] ); ?>">
			
			<!-- Success Message (hidden by default) -->
			<div class="aqop-form-message aqop-form-success" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e( 'Thank you! Your information has been submitted successfully.', 'aqop-leads' ); ?></p>
			</div>

			<!-- Error Message (hidden by default) -->
			<div class="aqop-form-message aqop-form-error" style="display: none;">
				<span class="dashicons dashicons-warning"></span>
				<p><?php esc_html_e( 'An error occurred. Please try again.', 'aqop-leads' ); ?></p>
			</div>

			<!-- Form -->
			<form class="aqop-lead-form" method="post">
				<?php wp_nonce_field( 'aqop_submit_lead', 'aqop_lead_nonce' ); ?>

				<!-- Name Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-name">
						<?php esc_html_e( 'Full Name', 'aqop-leads' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="text" 
						id="aqop-lead-name" 
						name="lead_name" 
						required
						placeholder="<?php esc_attr_e( 'Enter your full name', 'aqop-leads' ); ?>"
					>
				</div>

				<!-- Email Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-email">
						<?php esc_html_e( 'Email Address', 'aqop-leads' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="email" 
						id="aqop-lead-email" 
						name="lead_email" 
						required
						placeholder="<?php esc_attr_e( 'your@email.com', 'aqop-leads' ); ?>"
					>
				</div>

				<!-- Phone Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-phone">
						<?php esc_html_e( 'Phone Number', 'aqop-leads' ); ?>
						<span class="required">*</span>
					</label>
					<input 
						type="tel" 
						id="aqop-lead-phone" 
						name="lead_phone" 
						required
						placeholder="<?php esc_attr_e( '+966 50 123 4567', 'aqop-leads' ); ?>"
					>
				</div>

				<?php if ( 'yes' === $atts['show_whatsapp'] ) : ?>
				<!-- WhatsApp Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-whatsapp">
						<?php esc_html_e( 'WhatsApp Number', 'aqop-leads' ); ?>
					</label>
					<input 
						type="tel" 
						id="aqop-lead-whatsapp" 
						name="lead_whatsapp"
						placeholder="<?php esc_attr_e( 'Same as phone or different', 'aqop-leads' ); ?>"
					>
					<small><?php esc_html_e( 'Leave empty to use phone number', 'aqop-leads' ); ?></small>
				</div>
				<?php endif; ?>

				<?php if ( 'yes' === $atts['show_country'] ) : ?>
				<!-- Country Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-country">
						<?php esc_html_e( 'Country', 'aqop-leads' ); ?>
					</label>
					<select id="aqop-lead-country" name="lead_country">
						<option value=""><?php esc_html_e( 'Select your country', 'aqop-leads' ); ?></option>
						<?php
						global $wpdb;
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$countries = $wpdb->get_results(
							"SELECT id, country_code, country_name_en 
							 FROM {$wpdb->prefix}aq_dim_countries 
							 WHERE is_active = 1 
							 ORDER BY country_name_en ASC"
						);
						foreach ( $countries as $country ) {
							printf(
								'<option value="%d">%s</option>',
								esc_attr( $country->id ),
								esc_html( $country->country_name_en )
							);
						}
						?>
					</select>
				</div>
				<?php endif; ?>

				<!-- Message Field -->
				<div class="aqop-form-field">
					<label for="aqop-lead-message">
						<?php esc_html_e( 'Message', 'aqop-leads' ); ?>
					</label>
					<textarea 
						id="aqop-lead-message" 
						name="lead_message" 
						rows="4"
						placeholder="<?php esc_attr_e( 'Tell us how we can help you...', 'aqop-leads' ); ?>"
					></textarea>
				</div>

				<!-- Submit Button -->
				<div class="aqop-form-field">
					<button type="submit" class="aqop-submit-button">
						<span class="button-text"><?php echo esc_html( $atts['button_text'] ); ?></span>
						<span class="button-spinner" style="display: none;">
							<span class="spinner-icon">⏳</span>
							<?php esc_html_e( 'Submitting...', 'aqop-leads' ); ?>
						</span>
					</button>
				</div>

				<!-- Privacy Notice -->
				<div class="aqop-form-privacy">
					<small>
						<?php
						printf(
							/* translators: %s: privacy policy link */
							esc_html__( 'By submitting this form, you agree to our %s.', 'aqop-leads' ),
							'<a href="' . esc_url( get_privacy_policy_url() ) . '" target="_blank">' . esc_html__( 'privacy policy', 'aqop-leads' ) . '</a>'
						);
						?>
					</small>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle form submission via AJAX.
	 *
	 * @since 1.0.7
	 */
	public function handle_form_submission() {
		// Verify nonce
		if ( ! isset( $_POST['aqop_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aqop_lead_nonce'] ) ), 'aqop_submit_lead' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'aqop-leads' ),
				),
				403
			);
		}

		// Rate limiting check
		if ( class_exists( 'AQOP_Frontend_Guard' ) ) {
			if ( ! AQOP_Frontend_Guard::check_rate_limit( 'lead_submission', 5, 3600 ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Too many submissions. Please try again later.', 'aqop-leads' ),
					),
					429
				);
			}
		}

		// Validate required fields
		$required_fields = array( 'lead_name', 'lead_email', 'lead_phone' );
		foreach ( $required_fields as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error(
					array(
						'message' => sprintf(
							/* translators: %s: field name */
							__( '%s is required.', 'aqop-leads' ),
							ucfirst( str_replace( 'lead_', '', $field ) )
						),
					),
					400
				);
			}
		}

		// Sanitize inputs
		$name = sanitize_text_field( wp_unslash( $_POST['lead_name'] ) );
		$email = sanitize_email( wp_unslash( $_POST['lead_email'] ) );
		$phone = sanitize_text_field( wp_unslash( $_POST['lead_phone'] ) );
		$whatsapp = ! empty( $_POST['lead_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_whatsapp'] ) ) : $phone;
		$country_id = ! empty( $_POST['lead_country'] ) ? absint( $_POST['lead_country'] ) : null;
		$message = ! empty( $_POST['lead_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lead_message'] ) ) : '';
		$source = ! empty( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'website';
		$campaign = ! empty( $_POST['campaign'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign'] ) ) : '';

		// Validate email
		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter a valid email address.', 'aqop-leads' ),
				),
				400
			);
		}

		// Get source ID from source name
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$source_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}aq_leads_sources WHERE source_code = %s OR source_name = %s",
				$source,
				$source
			)
		);

		// Get campaign ID if provided
		$campaign_id = null;
		if ( ! empty( $campaign ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$campaign_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}aq_leads_campaigns WHERE name = %s OR id = %d",
					$campaign,
					absint( $campaign )
				)
			);
		}

		// Get default "pending" status ID
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$status_id = $wpdb->get_var(
			"SELECT id FROM {$wpdb->prefix}aq_leads_status WHERE status_code = 'pending' LIMIT 1"
		);

		// Create lead
		$lead_data = array(
			'name'        => $name,
			'email'       => $email,
			'phone'       => $phone,
			'whatsapp'    => $whatsapp,
			'country_id'  => $country_id,
			'source_id'   => $source_id ? $source_id : null,
			'campaign_id' => $campaign_id,
			'status_id'   => $status_id ? $status_id : 1,
			'priority'    => 'medium',
		);

		$lead_id = AQOP_Leads_Manager::create_lead( $lead_data );

		if ( ! $lead_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to submit your information. Please try again or contact us directly.', 'aqop-leads' ),
				),
				500
			);
		}

		// Add message as note if provided
		if ( ! empty( $message ) ) {
			AQOP_Leads_Manager::add_note( $lead_id, $message, 0 ); // User ID 0 for public submission
		}

		// Send notification email to admin
		$this->send_notification_email( $lead_id, $lead_data, $message );

		// Log form submission event
		if ( class_exists( 'AQOP_Event_Logger' ) ) {
			AQOP_Event_Logger::log(
				'leads',
				'public_form_submission',
				'lead',
				$lead_id,
				array(
					'source'   => $source,
					'campaign' => $campaign,
					'country'  => $country_id,
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Thank you! Your information has been submitted successfully. We will contact you soon.', 'aqop-leads' ),
				'lead_id' => $lead_id,
			)
		);
	}

	/**
	 * Send notification email to admin.
	 *
	 * @since 1.0.7
	 * @param int    $lead_id   Lead ID.
	 * @param array  $lead_data Lead data.
	 * @param string $message   User message.
	 */
	private function send_notification_email( $lead_id, $lead_data, $message = '' ) {
		$admin_email = get_option( 'admin_email' );
		
		$subject = sprintf(
			/* translators: %s: lead name */
			__( '[New Lead] %s submitted contact form', 'aqop-leads' ),
			$lead_data['name']
		);

		$body = sprintf(
			__( "A new lead has been submitted via the website:\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nCONTACT INFORMATION\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nName: %s\nEmail: %s\nPhone: %s\nWhatsApp: %s\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nMESSAGE\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n%s\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\nACTIONS\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nView in Admin Panel:\n%s\n\nSubmitted: %s", 'aqop-leads' ),
			$lead_data['name'],
			$lead_data['email'],
			$lead_data['phone'],
			$lead_data['whatsapp'],
			$message ? $message : __( '(No message provided)', 'aqop-leads' ),
			admin_url( 'admin.php?page=aqop-leads-view&lead_id=' . $lead_id ),
			current_time( 'mysql' )
		);

		// Send email
		wp_mail( $admin_email, $subject, $body );

		// Send to Telegram if configured
		if ( class_exists( 'AQOP_Integrations_Hub' ) && defined( 'AQOP_TELEGRAM_BOT_TOKEN' ) ) {
			$telegram_message = sprintf(
				"<b>🆕 New Lead Submitted</b>\n\n<b>Name:</b> %s\n<b>Email:</b> %s\n<b>Phone:</b> %s\n\n<b>Message:</b>\n%s",
				$lead_data['name'],
				$lead_data['email'],
				$lead_data['phone'],
				$message ? $message : '(No message)'
			);
			
			// Get Telegram chat ID from options
			$telegram_chat = get_option( 'aqop_telegram_lead_notifications_chat', '' );
			if ( ! empty( $telegram_chat ) ) {
				AQOP_Integrations_Hub::send_telegram( $telegram_chat, $telegram_message );
			}
		}
	}

	/**
	 * Enqueue form assets.
	 *
	 * @since 1.0.7
	 */
	public function enqueue_assets() {
		// Only enqueue if shortcode is present
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'aqop_lead_form' ) ) {
			return;
		}

		// Enqueue dashicons for success/error icons
		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'aqop-public-form',
			AQOP_LEADS_PLUGIN_URL . 'public/css/public-form.css',
			array(),
			AQOP_LEADS_VERSION
		);

		wp_enqueue_script(
			'aqop-public-form',
			AQOP_LEADS_PLUGIN_URL . 'public/js/public-form.js',
			array( 'jquery' ),
			AQOP_LEADS_VERSION,
			true
		);

		wp_localize_script(
			'aqop-public-form',
			'aqopPublicForm',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'strings' => array(
					'submitting' => __( 'Submitting...', 'aqop-leads' ),
					'submit'     => __( 'Submit', 'aqop-leads' ),
					'error'      => __( 'An error occurred. Please try again.', 'aqop-leads' ),
					'required'   => __( 'This field is required.', 'aqop-leads' ),
				),
			)
		);
	}
}

// Initialize public form
new AQOP_Public_Form();

