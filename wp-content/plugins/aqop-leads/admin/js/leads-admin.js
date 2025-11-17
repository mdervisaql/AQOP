/**
 * Leads Admin JavaScript
 *
 * @package AQOP_Leads
 * @since   1.0.0
 */

(function($) {
	'use strict';

	const AQOP_Leads = {

		init: function() {
			this.handlePerPageChange();
			this.handleSearchClear();
			this.handleBulkActions();
			this.handleSettingsTabs();
		},
		
		// === PAGINATION (Phase 2.2) ===
		
		/**
		 * Handle per-page selector change
		 */
		handlePerPageChange: function() {
			$('#per-page-select').on('change', function() {
				var perPage = $(this).val();
				var currentUrl = new URL(window.location.href);
				
				// Update per_page parameter
				currentUrl.searchParams.set('per_page', perPage);
				
				// Reset to page 1 when changing per_page
				currentUrl.searchParams.set('paged', '1');
				
				// Redirect
				window.location.href = currentUrl.toString();
			});
		},
		
		/**
		 * Clear search button (X in search input)
		 */
		handleSearchClear: function() {
			// Modern browsers show a clear button in search inputs
			$('#leads-search-input').on('search', function() {
				// If cleared, submit form to show all results
				if ($(this).val() === '') {
					$(this).closest('form').submit();
				}
			});
		},
		
		// === END PAGINATION ===

		// === BULK ACTIONS (Phase 2.3) ===
		
		/**
		 * Handle bulk actions
		 */
		handleBulkActions: function() {
			var self = this;
			
			// Select all checkbox
			$('#cb-select-all-1').on('change', function() {
				$('.lead-checkbox').prop('checked', $(this).prop('checked'));
				self.updateBulkActionButton();
			});
			
			// Individual checkboxes
			$(document).on('change', '.lead-checkbox', function() {
				var allChecked = $('.lead-checkbox').length === $('.lead-checkbox:checked').length;
				$('#cb-select-all-1').prop('checked', allChecked);
				self.updateBulkActionButton();
			});
			
			// Apply bulk action
			$('#doaction').on('click', function(e) {
				e.preventDefault();
				
				var action = $('#bulk-action-selector-top').val();
				var selectedIds = $('.lead-checkbox:checked').map(function() {
					return $(this).val();
				}).get();
				
				if (action === '-1') {
					alert(aqopLeads.strings.selectBulkAction || 'Please select an action.');
					return;
				}
				
				if (selectedIds.length === 0) {
					alert(aqopLeads.strings.selectLeads || 'Please select at least one lead.');
					return;
				}
				
				// Confirm delete
				if (action === 'delete') {
					var confirmMsg = (aqopLeads.strings.confirmBulkDelete || 'Are you sure you want to delete the selected leads?').replace('%d', selectedIds.length);
					if (!confirm(confirmMsg)) {
						return;
					}
				}
				
				// Handle export separately (client-side download)
				if (action === 'export') {
					self.handleBulkExport(selectedIds);
					return;
				}
				
				// Execute bulk action
				self.executeBulkAction(action, selectedIds);
			});
		},
		
		/**
		 * Update bulk action button state
		 */
		updateBulkActionButton: function() {
			var selectedCount = $('.lead-checkbox:checked').length;
			var $button = $('#doaction');
			
			if (selectedCount > 0) {
				$button.prop('disabled', false);
				$button.text((aqopLeads.strings.apply || 'Apply') + ' (' + selectedCount + ')');
			} else {
				$button.prop('disabled', true);
				$button.text(aqopLeads.strings.apply || 'Apply');
			}
		},
		
		/**
		 * Execute bulk action via AJAX
		 */
		executeBulkAction: function(action, leadIds) {
			var $button = $('#doaction');
			var originalText = $button.text();
			
			$button.prop('disabled', true).html(
				'<span class="dashicons dashicons-update spin"></span> ' + 
				(aqopLeads.strings.processing || 'Processing...')
			);
			
			$.ajax({
				url: aqopLeads.ajaxurl,
				method: 'POST',
				data: {
					action: 'aqop_bulk_action',
					bulk_action: action,
					lead_ids: leadIds,
					nonce: aqopLeads.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message || aqopLeads.strings.error);
						$button.prop('disabled', false).html(originalText);
					}
				},
				error: function() {
					alert(aqopLeads.strings.error);
					$button.prop('disabled', false).html(originalText);
				}
			});
		},
		
		/**
		 * Handle bulk export
		 */
		handleBulkExport: function(leadIds) {
			var $button = $('#doaction');
			var originalText = $button.text();
			
			$button.prop('disabled', true).html(
				'<span class="dashicons dashicons-download spin"></span> ' +
				(aqopLeads.strings.exporting || 'Exporting...')
			);
			
			$.ajax({
				url: aqopLeads.ajaxurl,
				method: 'POST',
				data: {
					action: 'aqop_bulk_action',
					bulk_action: 'export',
					lead_ids: leadIds,
					nonce: aqopLeads.nonce
				},
				success: function(response) {
					if (response.success) {
						// Download CSV
						var blob = new Blob([response.data.csv_data], { type: 'text/csv;charset=utf-8;' });
						var url = window.URL.createObjectURL(blob);
						var a = document.createElement('a');
						a.href = url;
						a.download = response.data.filename;
						document.body.appendChild(a);
						a.click();
						window.URL.revokeObjectURL(url);
						document.body.removeChild(a);
						
						// Reset button
						$button.prop('disabled', false).html(originalText);
						
						// Uncheck all
						$('.lead-checkbox, #cb-select-all-1').prop('checked', false);
						AQOP_Leads.updateBulkActionButton();
					} else {
						alert(response.data.message || aqopLeads.strings.error);
						$button.prop('disabled', false).html(originalText);
					}
				},
				error: function() {
					alert(aqopLeads.strings.error);
					$button.prop('disabled', false).html(originalText);
				}
			});
		},
		
		// === END BULK ACTIONS ===

		// === SETTINGS PAGE (Phase 4.1) ===
		
		/**
		 * Handle settings page tab switching
		 */
		handleSettingsTabs: function() {
			if (!$('.aqop-settings-tabs').length) {
				return;
			}
			
			// Tab switching
			$('.nav-tab').on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				
				// Update nav tabs
				$('.nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				
				// Update tab content
				$('.aqop-settings-tab').removeClass('active');
				$(target).addClass('active');
				
				// Update URL hash without jumping
				if (history.pushState) {
					history.pushState(null, null, target);
				}
			});
			
			// Handle direct hash navigation
			if (window.location.hash) {
				var hash = window.location.hash;
				$('.nav-tab[href="' + hash + '"]').trigger('click');
			}
		}
		
		// === END SETTINGS PAGE ===

	};

	$(document).ready(function() {
		AQOP_Leads.init();
	});

})(jQuery);

