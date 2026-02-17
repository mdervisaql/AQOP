/**
 * Operations Center Monitoring Admin JS
 */

(function ($) {
    'use strict';

    const AQOPMonitoring = {
        refreshInterval: null,
        refreshRate: 5000, // 5 seconds

        init: function () {
            this.bindEvents();
            this.startAutoRefresh();
            this.loadStats();
        },

        bindEvents: function () {
            $('#refresh-users').on('click', () => this.refreshActiveUsers());
            $('#refresh-activity').on('click', () => this.refreshRecentActivity());
        },

        startAutoRefresh: function () {
            // Auto-refresh every 5 seconds
            this.refreshInterval = setInterval(() => {
                this.refreshActiveUsers();
                this.refreshRecentActivity();
                this.loadStats();
            }, this.refreshRate);
        },

        stopAutoRefresh: function () {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },

        refreshActiveUsers: function () {
            const $button = $('#refresh-users');
            const $list = $('#active-users-list');

            $button.prop('disabled', true);
            $button.find('.dashicons').addClass('spin');

            $.ajax({
                url: aqopMonitoring.apiUrl + '/active-users',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aqopMonitoring.nonce);
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.renderActiveUsers(response.data);
                        $('#active-users-count').text(response.total);
                    }
                },
                error: (xhr) => {
                    console.error('Failed to fetch active users:', xhr);
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('spin');
                }
            });
        },

        renderActiveUsers: function (users) {
            const $list = $('#active-users-list');

            if (users.length === 0) {
                $list.html(`
					<div class="no-data">
						<span class="dashicons dashicons-info"></span>
						<p>No active users at the moment</p>
					</div>
				`);
                return;
            }

            let html = '<div class="users-table">';

            users.forEach(user => {
                const duration = this.formatDuration(user.session_duration);
                const module = user.current_module || 'N/A';
                const role = user.role || 'subscriber';

                html += `
					<div class="user-row">
						<div class="user-avatar">
							<img src="https://www.gravatar.com/avatar/${this.md5(user.user_email)}?s=40&d=mp" alt="${user.display_name}" width="40" height="40" style="border-radius: 50%;">
						</div>
						<div class="user-info">
							<div class="user-name">${this.escapeHtml(user.display_name)}</div>
							<div class="user-meta">
								<span class="user-role">${this.escapeHtml(role)}</span>
								${user.current_module ? `<span class="separator">•</span><span class="user-module">${this.escapeHtml(user.current_module)}</span>` : ''}
							</div>
						</div>
						<div class="user-activity">
							<div class="activity-time">${duration}</div>
							<div class="activity-label">Session</div>
						</div>
						<div class="user-status">
							<span class="status-indicator active"></span>
						</div>
					</div>
				`;
            });

            html += '</div>';
            $list.html(html);
        },

        refreshRecentActivity: function () {
            const $button = $('#refresh-activity');
            const $list = $('#recent-activity-list');

            $button.prop('disabled', true);
            $button.find('.dashicons').addClass('spin');

            $.ajax({
                url: aqopMonitoring.apiUrl + '/recent-activity',
                method: 'GET',
                data: { limit: 20 },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aqopMonitoring.nonce);
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.renderRecentActivity(response.data);
                    }
                },
                error: (xhr) => {
                    console.error('Failed to fetch recent activity:', xhr);
                },
                complete: () => {
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('spin');
                }
            });
        },

        renderRecentActivity: function (activities) {
            const $list = $('#recent-activity-list');

            if (activities.length === 0) {
                $list.html(`
					<div class="no-data">
						<span class="dashicons dashicons-info"></span>
						<p>No recent activity</p>
					</div>
				`);
                return;
            }

            let html = '<div class="activity-feed">';

            activities.forEach(activity => {
                const icon = this.getActionIcon(activity.action_type);
                const action = this.formatAction(activity.action_type);
                const timeAgo = this.timeAgo(activity.created_at);

                html += `
					<div class="activity-item">
						<div class="activity-icon">
							<span class="dashicons ${icon}"></span>
						</div>
						<div class="activity-content">
							<div class="activity-user">${this.escapeHtml(activity.user_name)}</div>
							<div class="activity-action">${action}</div>
							<div class="activity-module">${this.escapeHtml(activity.module_code)}</div>
						</div>
						<div class="activity-time">${timeAgo}</div>
					</div>
				`;
            });

            html += '</div>';
            $list.html(html);
        },

        loadStats: function () {
            $.ajax({
                url: aqopMonitoring.apiUrl + '/stats',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aqopMonitoring.nonce);
                },
                success: (response) => {
                    if (response.success && response.data) {
                        $('#activity-count').text(response.data.recent_activity_count);

                        const moduleCount = response.data.users_by_module ? response.data.users_by_module.length : 0;
                        $('#module-count').text(moduleCount);
                    }
                },
                error: (xhr) => {
                    console.error('Failed to fetch stats:', xhr);
                }
            });
        },

        formatDuration: function (seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else {
                return `${minutes}m`;
            }
        },

        getActionIcon: function (actionType) {
            const icons = {
                'page_view': 'dashicons-visibility',
                'api_call': 'dashicons-rest-api',
                'lead_created': 'dashicons-plus',
                'lead_updated': 'dashicons-edit',
                'feedback_created': 'dashicons-feedback'
            };

            return icons[actionType] || 'dashicons-admin-generic';
        },

        formatAction: function (actionType) {
            const actions = {
                'page_view': 'Viewed page',
                'api_call': 'API call',
                'lead_created': 'Created lead',
                'lead_updated': 'Updated lead',
                'feedback_created': 'Submitted feedback'
            };

            return actions[actionType] || actionType.replace(/_/g, ' ');
        },

        timeAgo: function (dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            return Math.floor(seconds / 86400) + ' days ago';
        },

        escapeHtml: function (text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        md5: function (string) {
            // Simple MD5 for Gravatar (you might want to use a proper library)
            // For now, just return a placeholder
            return string.split('').reduce((a, b) => {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0).toString(16);
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        if ($('.aqop-operations-center').length) {
            AQOPMonitoring.init();
        }
    });

    // Add spin animation for refresh buttons
    $('<style>')
        .text('.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);
