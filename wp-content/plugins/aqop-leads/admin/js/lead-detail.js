/**
 * Lead Detail Page JavaScript
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Lead Detail Handler
	 */
	var LeadDetail = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.handleNoteForm();
			this.handleAirtableSync();
			this.handleDeleteModal();
			this.handleNoteActions();
		},
		
		/**
		 * Handle note form submission
		 */
		handleNoteForm: function() {
			$('.aqop-note-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $button = $form.find('button[type="submit"]');
				var $textarea = $form.find('textarea[name="note_text"]');
				var originalButtonHtml = $button.html();
				
				// Validate
				if (!$textarea.val().trim()) {
					alert('Please enter a note.');
					return;
				}
				
				// Disable button and show loading
				$button.prop('disabled', true).html(
					'<span class="dashicons dashicons-update spin"></span> ' + 
					(aqopLeads.strings.adding || 'Adding...')
				);
				
				$.ajax({
					url: aqopLeads.ajaxurl,
					method: 'POST',
					data: $form.serialize(),
					success: function(response) {
						if (response.success) {
							// Show success message
							LeadDetail.showNotice('success', response.data.message || 'Note added successfully');
							
							// Reload page after short delay
							setTimeout(function() {
								location.reload();
			}, 500);
						} else {
							LeadDetail.showNotice('error', response.data.message || aqopLeads.strings.noteFailed);
							$button.prop('disabled', false).html(originalButtonHtml);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error:', error);
						LeadDetail.showNotice('error', aqopLeads.strings.error);
						$button.prop('disabled', false).html(originalButtonHtml);
					}
				});
			});
		},
		
		/**
		 * Handle Airtable sync
		 */
		handleAirtableSync: function() {
			$('.aqop-sync-airtable').on('click', function() {
				var $button = $(this);
				var leadId = $button.data('lead-id');
				var originalButtonHtml = $button.html();
				
				$button.prop('disabled', true).html(
					'<span class="dashicons dashicons-update spin"></span> ' + 
					(aqopLeads.strings.syncing || 'Syncing...')
				);
				
				$.ajax({
					url: aqopLeads.ajaxurl,
					method: 'POST',
					data: {
						action: 'aqop_sync_lead_airtable',
						lead_id: leadId,
						nonce: LeadDetail.createSyncNonce()
					},
					success: function(response) {
						if (response.success) {
							LeadDetail.showNotice('success', aqopLeads.strings.syncSuccess);
							
							// Reload page after short delay
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else {
							LeadDetail.showNotice('error', response.data.message || aqopLeads.strings.syncFailed);
							$button.prop('disabled', false).html(originalButtonHtml);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error:', error);
						LeadDetail.showNotice('error', aqopLeads.strings.error);
						$button.prop('disabled', false).html(originalButtonHtml);
					}
				});
			});
		},
		
		/**
		 * Create nonce for sync
		 */
		createSyncNonce: function() {
			// Generate nonce on-the-fly using wp-localize data
			if (aqopLeads && aqopLeads.nonce) {
				return aqopLeads.nonce;
			}
			return '';
		},
		
		// === DELETE MODAL (Phase 1.3) ===
		
		/**
		 * Handle delete confirmation modal
		 */
		handleDeleteModal: function() {
			var $modal = $('#delete-lead-modal');
			
			// Open modal
			$('.aqop-delete-trigger').on('click', function(e) {
				e.preventDefault();
				$modal.addClass('active').fadeIn(200);
				$('body').css('overflow', 'hidden'); // Prevent background scroll
			});
			
			// Close modal
			$('.aqop-modal-close, .aqop-modal-cancel').on('click', function() {
				LeadDetail.closeModal($modal);
			});
			
			// Close on outside click
			$modal.on('click', function(e) {
				if ($(e.target).is('.aqop-modal')) {
					LeadDetail.closeModal($modal);
				}
			});
			
			// Close on ESC key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $modal.hasClass('active')) {
					LeadDetail.closeModal($modal);
				}
			});
		},
		
		/**
		 * Close modal
		 */
		closeModal: function($modal) {
			$modal.removeClass('active').fadeOut(200);
			$('body').css('overflow', ''); // Restore scroll
		},
		
		// === END DELETE MODAL ===
		
		// === NOTES ENHANCEMENT (Phase 1.4) ===
		
		/**
		 * Handle note edit/delete actions
		 */
		handleNoteActions: function() {
			var self = this;
			
			// Edit note
			$(document).on('click', '.aqop-note-edit-btn', function() {
				var $note = $(this).closest('.aqop-note-item');
				$note.addClass('editing');
				$note.find('.aqop-note-edit-textarea').focus();
			});
			
			// Cancel edit
			$(document).on('click', '.aqop-note-cancel-btn', function() {
				var $note = $(this).closest('.aqop-note-item');
				$note.removeClass('editing');
				
				// Restore original text
				var originalText = $note.find('.aqop-note-text').data('original-text');
				$note.find('.aqop-note-edit-textarea').val(originalText);
			});
			
			// Save note
			$(document).on('click', '.aqop-note-save-btn', function() {
				var $note = $(this).closest('.aqop-note-item');
				var noteId = $note.data('note-id');
				var newText = $note.find('.aqop-note-edit-textarea').val().trim();
				
				if (!newText) {
					alert(aqopLeads.strings.noteEmpty || 'Note text cannot be empty.');
					return;
				}
				
				self.saveNoteEdit(noteId, newText, $note);
			});
			
			// Delete note
			$(document).on('click', '.aqop-note-delete-btn', function() {
				var $note = $(this).closest('.aqop-note-item');
				var noteId = $note.data('note-id');
				
				if (confirm(aqopLeads.strings.confirmDeleteNote || 'Are you sure you want to delete this note?')) {
					self.deleteNote(noteId, $note);
				}
			});
		},
		
		/**
		 * Save note edit via AJAX
		 */
		saveNoteEdit: function(noteId, newText, $note) {
			var originalText = $note.find('.aqop-note-text').data('original-text');
			
			// Optimistic UI update
			$note.find('.aqop-note-text').html(this.escapeHtml(newText).replace(/\n/g, '<br>'));
			$note.removeClass('editing');
			
			$.ajax({
				url: aqopLeads.ajaxurl,
				method: 'POST',
				data: {
					action: 'aqop_edit_note',
					note_id: noteId,
					note_text: newText,
					nonce: aqopLeads.nonce
				},
				success: function(response) {
					if (response.success) {
						// Update original text data
						$note.find('.aqop-note-text').attr('data-original-text', newText);
						$note.find('.aqop-note-edit-textarea').val(newText);
						LeadDetail.showNotice('success', response.data.message || 'Note updated successfully');
					} else {
						// Revert on error
						$note.find('.aqop-note-text').html(LeadDetail.escapeHtml(originalText).replace(/\n/g, '<br>'));
						LeadDetail.showNotice('error', response.data.message || 'Failed to update note');
					}
				},
				error: function() {
					// Revert on error
					$note.find('.aqop-note-text').html(LeadDetail.escapeHtml(originalText).replace(/\n/g, '<br>'));
					LeadDetail.showNotice('error', aqopLeads.strings.error);
				}
			});
		},
		
		/**
		 * Delete note via AJAX
		 */
		deleteNote: function(noteId, $note) {
			$note.addClass('deleting');
			
			$.ajax({
				url: aqopLeads.ajaxurl,
				method: 'POST',
				data: {
					action: 'aqop_delete_note',
					note_id: noteId,
					nonce: aqopLeads.nonce
				},
				success: function(response) {
					if (response.success) {
						$note.addClass('removed');
						setTimeout(function() {
							$note.remove();
							
							// Show "no notes" message if all notes deleted
							if ($('.aqop-note-item').length === 0) {
								$('.aqop-notes-timeline').html(
									'<p class="description">' + 
									(aqopLeads.strings.noNotes || 'No notes yet. Add the first note above.') + 
									'</p>'
								);
							}
						}, 300);
						LeadDetail.showNotice('success', response.data.message || 'Note deleted successfully');
					} else {
						$note.removeClass('deleting');
						LeadDetail.showNotice('error', response.data.message || 'Failed to delete note');
					}
				},
				error: function() {
					$note.removeClass('deleting');
					LeadDetail.showNotice('error', aqopLeads.strings.error);
				}
			});
		},
		
		/**
		 * Escape HTML helper
		 */
		escapeHtml: function(text) {
			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		},
		
		// === END NOTES ENHANCEMENT ===
		
		/**
		 * Show admin notice
		 */
		showNotice: function(type, message) {
			var noticeClass = 'notice-' + type;
			var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
			
			$('.aqop-lead-header').before($notice);
			
			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
			
			// Handle dismiss button
			$notice.on('click', '.notice-dismiss', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});
			
			// Scroll to notice
			$('html, body').animate({
				scrollTop: 0
			}, 300);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.aqop-lead-detail').length) {
			LeadDetail.init();
		}
	});

})(jQuery);

