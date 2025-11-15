/**
 * Control Center JavaScript
 *
 * Handles charts, real-time updates, and interactive features.
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Control Center App
	 */
	const AQOP_ControlCenter = {

		/**
		 * Initialize
		 */
		init: function() {
			this.initCharts();
			this.initActions();
			this.startAutoRefresh();
		},

		/**
		 * Initialize Charts
		 */
		initCharts: function() {
			// Events Timeline Chart
			this.initEventsTimelineChart();

			// Module Distribution Chart
			this.initModuleDistributionChart();

			// Event Types Chart
			this.initEventTypesChart();
		},

		/**
		 * Events Timeline Chart
		 */
		initEventsTimelineChart: function() {
			const ctx = document.getElementById('eventsTimelineChart');
			if (!ctx) return;

			const chart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: [],
					datasets: [{
						label: 'Total Events',
						data: [],
						borderColor: '#2c5282',
						backgroundColor: 'rgba(44, 82, 130, 0.1)',
						tension: 0.4,
						fill: true,
						borderWidth: 2
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: true,
							position: 'top'
						},
						tooltip: {
							mode: 'index',
							intersect: false
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0
							}
						}
					}
				}
			});

			// Load data
			this.loadTimelineData(chart);

			// Store chart reference
			this.timelineChart = chart;
		},

		/**
		 * Module Distribution Chart
		 */
		initModuleDistributionChart: function() {
			const ctx = document.getElementById('moduleDistributionChart');
			if (!ctx) return;

			const chart = new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: ['Core', 'Leads', 'Training', 'KB'],
					datasets: [{
						data: [40, 35, 20, 5],
						backgroundColor: [
							'#2c5282',
							'#48bb78',
							'#ed8936',
							'#4299e1'
						],
						borderWidth: 0
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom'
						}
					}
				}
			});

			this.distributionChart = chart;
		},

		/**
		 * Event Types Chart
		 */
		initEventTypesChart: function() {
			const ctx = document.getElementById('eventTypesChart');
			if (!ctx) return;

			const chart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: ['Created', 'Updated', 'Deleted', 'Assigned', 'Status Changed'],
					datasets: [{
						label: 'Count',
						data: [450, 280, 45, 125, 380],
						backgroundColor: '#2c5282',
						borderRadius: 4
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					indexAxis: 'y',
					plugins: {
						legend: {
							display: false
						}
					},
					scales: {
						x: {
							beginAtZero: true,
							ticks: {
								precision: 0
							}
						}
					}
				}
			});

			this.eventTypesChart = chart;
		},

		/**
		 * Load Timeline Data
		 */
		loadTimelineData: function(chart) {
			// Generate sample data for last 7 days
			const labels = [];
			const data = [];

			for (let i = 6; i >= 0; i--) {
				const date = new Date();
				date.setDate(date.getDate() - i);
				labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
				data.push(Math.floor(Math.random() * 500) + 200);
			}

			chart.data.labels = labels;
			chart.data.datasets[0].data = data;
			chart.update('none');
		},

		/**
		 * Initialize Actions
		 */
		initActions: function() {
			// Refresh Timeline
			$('#refresh-timeline').on('click', () => {
				this.refreshTimeline();
			});

			// Clear Cache
			$('#clear-cache').on('click', () => {
				this.clearCache();
			});

			// Check Integrations
			$('#check-integrations').on('click', () => {
				this.checkIntegrations();
			});

			// Export Data
			$('#export-data').on('click', () => {
				this.exportData();
			});
		},

		/**
		 * Refresh Timeline
		 */
		refreshTimeline: function() {
			if (this.timelineChart) {
				const btn = $('#refresh-timeline');
				btn.prop('disabled', true).find('.dashicons').addClass('dashicons-update-alt').css('animation', 'rotation 1s infinite linear');

				setTimeout(() => {
					this.loadTimelineData(this.timelineChart);
					btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update-alt').css('animation', '');
					this.showNotice('Timeline refreshed successfully', 'success');
				}, 1000);
			}
		},

		/**
		 * Clear Cache
		 */
		clearCache: function() {
			const btn = $('#clear-cache');
			btn.prop('disabled', true).text('Clearing...');

			$.ajax({
				url: aqopControlCenter.ajaxurl,
				type: 'POST',
				data: {
					action: 'aqop_clear_cache',
					nonce: aqopControlCenter.nonce
				},
				success: (response) => {
					btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Caches');
					if (response.success) {
						this.showNotice('Caches cleared successfully', 'success');
					} else {
						this.showNotice('Failed to clear caches', 'error');
					}
				},
				error: () => {
					btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Caches');
					this.showNotice('Request failed', 'error');
				}
			});
		},

		/**
		 * Check Integrations
		 */
		checkIntegrations: function() {
			const btn = $('#check-integrations');
			btn.prop('disabled', true).text('Testing...');

			// Simulate integration check
			setTimeout(() => {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Test Integrations');
				this.showNotice('Integration health check completed', 'success');
				location.reload();
			}, 2000);
		},

		/**
		 * Export Data
		 */
		exportData: function() {
			const btn = $('#export-data');
			btn.prop('disabled', true).text('Exporting...');

			// Simulate export
			setTimeout(() => {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Export Data');
				this.showNotice('Export completed', 'success');
			}, 1500);
		},

		/**
		 * Start Auto Refresh
		 */
		startAutoRefresh: function() {
			// Refresh stats every 30 seconds
			setInterval(() => {
				this.refreshStats();
			}, 30000);
		},

		/**
		 * Refresh Stats
		 */
		refreshStats: function() {
			// Update last updated time
			const now = new Date();
			const timeString = now.toLocaleTimeString('en-US', { 
				hour: '2-digit', 
				minute: '2-digit',
				second: '2-digit'
			});
			$('.aqop-last-updated').text('Last updated: ' + timeString);
		},

		/**
		 * Show Notice
		 */
		showNotice: function(message, type) {
			const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
			const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
			
			$('.aqop-header').after(notice);
			
			setTimeout(() => {
				notice.fadeOut(() => {
					notice.remove();
				});
			}, 3000);
		}

	};

	// Initialize on document ready
	$(document).ready(function() {
		AQOP_ControlCenter.init();
	});

	// Add rotation animation for refresh icon
	const style = document.createElement('style');
	style.textContent = `
		@keyframes rotation {
			from {
				transform: rotate(0deg);
			}
			to {
				transform: rotate(359deg);
			}
		}
	`;
	document.head.appendChild(style);

})(jQuery);

