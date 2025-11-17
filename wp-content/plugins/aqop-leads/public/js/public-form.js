/**
 * Public Lead Form JavaScript
 *
 * @package AQOP_Leads
 * @since   1.0.7
 */

(function($) {
	'use strict';

	// === PUBLIC FORM (Phase 3.2) ===

	var PublicForm = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.handleFormSubmission();
			this.handleWhatsAppAutofill();
			this.handlePhoneFormat();
		},
		
		/**
		 * Handle form submission
		 */
		handleFormSubmission: function() {
			$('.aqop-lead-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $container = $form.closest('.aqop-lead-form-container');
				var $button = $form.find('.aqop-submit-button');
				var $success = $container.find('.aqop-form-success');
				var $error = $container.find('.aqop-form-error');
				
				// Hide previous messages
				$success.hide();
				$error.hide();
				
				// Disable button and show loading
				$button.prop('disabled', true).addClass('loading');
				$form.addClass('submitting');
				
				// Get form data
				var formData = $form.serialize();
				formData += '&action=aqop_submit_lead_form';
				formData += '&source=' + encodeURIComponent($container.data('source'));
				formData += '&campaign=' + encodeURIComponent($container.data('campaign'));
				
				$.ajax({
					url: aqopPublicForm.ajaxurl,
					method: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							// Show success message
							$success.find('p').text(response.data.message);
							$success.fadeIn();
							
							// Reset form
							$form[0].reset();
							
							// Scroll to success message
							$('html, body').animate({
								scrollTop: $success.offset().top - 100
							}, 500);
							
							// Redirect if specified
							var redirect = $container.data('redirect');
							if (redirect) {
								setTimeout(function() {
									window.location.href = redirect;
								}, 2000);
							}
						} else {
							// Show error message
							var errorMsg = response.data && response.data.message 
								? response.data.message 
								: aqopPublicForm.strings.error;
							$error.find('p').text(errorMsg);
							$error.fadeIn();
							
							// Scroll to error
							$('html, body').animate({
								scrollTop: $error.offset().top - 100
							}, 500);
						}
						
						// Re-enable button
						$button.prop('disabled', false).removeClass('loading');
						$form.removeClass('submitting');
					},
					error: function(xhr, status, error) {
						console.error('Form submission error:', error);
						$error.find('p').text(aqopPublicForm.strings.error);
						$error.fadeIn();
						
						$button.prop('disabled', false).removeClass('loading');
						$form.removeClass('submitting');
					}
				});
			});
		},
		
		/**
		 * Auto-fill WhatsApp from phone
		 */
		handleWhatsAppAutofill: function() {
			$('#aqop-lead-phone').on('blur', function() {
				var phone = $(this).val();
				var $whatsapp = $('#aqop-lead-whatsapp');
				
				// If WhatsApp field exists and is empty, auto-fill from phone
				if ($whatsapp.length && !$whatsapp.val() && phone) {
					$whatsapp.val(phone);
					$whatsapp.addClass('auto-filled');
					
					// Add visual feedback
					$whatsapp.css('border-color', '#48bb78');
					setTimeout(function() {
						$whatsapp.css('border-color', '');
					}, 1000);
				}
			});
			
			// Remove auto-fill class if user manually edits
			$('#aqop-lead-whatsapp').on('input', function() {
				$(this).removeClass('auto-filled');
			});
		},
		
		/**
		 * Format phone input
		 */
		handlePhoneFormat: function() {
			$('#aqop-lead-phone, #aqop-lead-whatsapp').on('input', function() {
				var value = $(this).val();
				
				// Allow only numbers, +, -, (, ), and spaces
				value = value.replace(/[^0-9+\-\(\)\s]/g, '');
				
				$(this).val(value);
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.aqop-lead-form').length) {
			PublicForm.init();
		}
	});

	// === END PUBLIC FORM ===

})(jQuery);

