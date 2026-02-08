<?php
/**
 * Lead Scoring Settings Template
 *
 * @package AQOP_Leads
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="scoring" class="aqop-settings-tab">
    <div class="aqop-card">
        <h2><?php esc_html_e('Lead Scoring Rules', 'aqop-leads'); ?></h2>
        <p><?php esc_html_e('Define rules to automatically score leads based on their attributes and interactions.', 'aqop-leads'); ?>
        </p>
    </div>

    <!-- Scoring Rules Table -->
    <div class="aqop-card">
        <h3><?php esc_html_e('Active Rules', 'aqop-leads'); ?></h3>
        <div class="tablenav top">
            <div class="alignleft actions">
                <button type="button" class="button button-primary" id="add-scoring-rule">
                    <span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add New Rule', 'aqop-leads'); ?>
                </button>
                <button type="button" class="button button-secondary" id="bulk-recalculate-scores">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Recalculate All Scores', 'aqop-leads'); ?>
                </button>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" id="scoring-rules-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Rule Name', 'aqop-leads'); ?></th>
                    <th><?php esc_html_e('Condition', 'aqop-leads'); ?></th>
                    <th><?php esc_html_e('Points', 'aqop-leads'); ?></th>
                    <th><?php esc_html_e('Priority', 'aqop-leads'); ?></th>
                    <th><?php esc_html_e('Status', 'aqop-leads'); ?></th>
                    <th><?php esc_html_e('Actions', 'aqop-leads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated via JS -->
            </tbody>
        </table>
    </div>

    <!-- Rating Thresholds (Visual Guide for now, or editable if we implement options) -->
    <div class="aqop-card">
        <h3><?php esc_html_e('Rating Thresholds', 'aqop-leads'); ?></h3>
        <p><?php esc_html_e('Leads are rated based on their total score:', 'aqop-leads'); ?></p>
        <ul>
            <li><span class="aqop-badge aqop-badge-hot">Hot</span>: 80+ points</li>
            <li><span class="aqop-badge aqop-badge-warm">Warm</span>: 60-79 points</li>
            <li><span class="aqop-badge aqop-badge-qualified">Qualified</span>: 40-59 points</li>
            <li><span class="aqop-badge aqop-badge-cold">Cold</span>: 20-39 points</li>
            <li><span class="aqop-badge aqop-badge-neutral">Not Interested</span>: < 20 points</li>
        </ul>
    </div>
</div>

<!-- Add/Edit Rule Modal -->
<div id="scoring-rule-modal" class="aqop-modal" style="display:none;">
    <div class="aqop-modal-content">
        <span class="aqop-modal-close">&times;</span>
        <h2 id="modal-title"><?php esc_html_e('Add Scoring Rule', 'aqop-leads'); ?></h2>
        <form id="scoring-rule-form">
            <input type="hidden" name="id" id="rule_id">

            <table class="form-table">
                <tr>
                    <th><label for="rule_name"><?php esc_html_e('Rule Name', 'aqop-leads'); ?></label></th>
                    <td><input type="text" name="rule_name" id="rule_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="condition_field"><?php esc_html_e('Field', 'aqop-leads'); ?></label></th>
                    <td>
                        <select name="condition_field" id="condition_field">
                            <option value="source"><?php esc_html_e('Source', 'aqop-leads'); ?></option>
                            <option value="country"><?php esc_html_e('Country', 'aqop-leads'); ?></option>
                            <option value="status"><?php esc_html_e('Status', 'aqop-leads'); ?></option>
                            <option value="interactions_count"><?php esc_html_e('Interactions Count', 'aqop-leads'); ?>
                            </option>
                            <option value="response_time"><?php esc_html_e('Response Time (min)', 'aqop-leads'); ?>
                            </option>
                            <option value="no_response_time">
                                <?php esc_html_e('No Response Time (min)', 'aqop-leads'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="condition_operator"><?php esc_html_e('Operator', 'aqop-leads'); ?></label></th>
                    <td>
                        <select name="condition_operator" id="condition_operator">
                            <option value="equals"><?php esc_html_e('Equals', 'aqop-leads'); ?></option>
                            <option value="not_equals"><?php esc_html_e('Not Equals', 'aqop-leads'); ?></option>
                            <option value="contains"><?php esc_html_e('Contains', 'aqop-leads'); ?></option>
                            <option value="greater_than"><?php esc_html_e('Greater Than', 'aqop-leads'); ?></option>
                            <option value="less_than"><?php esc_html_e('Less Than', 'aqop-leads'); ?></option>
                            <option value="in_list"><?php esc_html_e('In List (comma separated)', 'aqop-leads'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="condition_value"><?php esc_html_e('Value', 'aqop-leads'); ?></label></th>
                    <td><input type="text" name="condition_value" id="condition_value" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="rule_type"><?php esc_html_e('Action', 'aqop-leads'); ?></label></th>
                    <td>
                        <select name="rule_type" id="rule_type">
                            <option value="add"><?php esc_html_e('Add Points', 'aqop-leads'); ?></option>
                            <option value="subtract"><?php esc_html_e('Subtract Points', 'aqop-leads'); ?></option>
                            <option value="set"><?php esc_html_e('Set Score To', 'aqop-leads'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="score_points"><?php esc_html_e('Points', 'aqop-leads'); ?></label></th>
                    <td><input type="number" name="score_points" id="score_points" class="small-text" required></td>
                </tr>
                <tr>
                    <th><label for="priority"><?php esc_html_e('Priority', 'aqop-leads'); ?></label></th>
                    <td><input type="number" name="priority" id="priority" class="small-text" value="10"></td>
                </tr>
                <tr>
                    <th><label for="is_active"><?php esc_html_e('Active', 'aqop-leads'); ?></label></th>
                    <td><input type="checkbox" name="is_active" id="is_active" value="1" checked></td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit"
                    class="button button-primary"><?php esc_html_e('Save Rule', 'aqop-leads'); ?></button>
            </p>
        </form>
    </div>
</div>

<style>
    .aqop-modal {
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .aqop-modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
    }

    .aqop-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .aqop-modal-close:hover,
    .aqop-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>

<script>
    jQuery(document).ready(function ($) {
        // Load Rules
        function loadRules() {
            $.ajax({
                url: '/wp-json/aqop/v1/scoring-rules',
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function (response) {
                    var tbody = $('#scoring-rules-table tbody');
                    tbody.empty();
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function (rule) {
                            var row = '<tr>' +
                                '<td>' + rule.rule_name + '</td>' +
                                '<td>' + rule.condition_field + ' ' + rule.condition_operator + ' ' + rule.condition_value + '</td>' +
                                '<td>' + (rule.rule_type === 'subtract' ? '-' : '+') + rule.score_points + '</td>' +
                                '<td>' + rule.priority + '</td>' +
                                '<td>' + (rule.is_active == 1 ? 'Active' : 'Inactive') + '</td>' +
                                '<td>' +
                                '<button class="button button-small edit-rule" data-rule=\'' + JSON.stringify(rule) + '\'>Edit</button> ' +
                                '<button class="button button-small delete-rule" data-id="' + rule.id + '">Delete</button>' +
                                '</td>' +
                                '</tr>';
                            tbody.append(row);
                        });
                    } else {
                        tbody.append('<tr><td colspan="6">No rules found.</td></tr>');
                    }
                }
            });
        }

        loadRules();

        // Modal Handling
        var modal = $('#scoring-rule-modal');
        var span = $('.aqop-modal-close');

        $('#add-scoring-rule').on('click', function () {
            $('#scoring-rule-form')[0].reset();
            $('#rule_id').val('');
            $('#modal-title').text('Add Scoring Rule');
            modal.show();
        });

        span.on('click', function () {
            modal.hide();
        });

        $(window).on('click', function (event) {
            if ($(event.target).is(modal)) {
                modal.hide();
            }
        });

        // Edit Rule
        $(document).on('click', '.edit-rule', function () {
            var rule = $(this).data('rule');
            $('#rule_id').val(rule.id);
            $('#rule_name').val(rule.rule_name);
            $('#condition_field').val(rule.condition_field);
            $('#condition_operator').val(rule.condition_operator);
            $('#condition_value').val(rule.condition_value);
            $('#rule_type').val(rule.rule_type);
            $('#score_points').val(rule.score_points);
            $('#priority').val(rule.priority);
            $('#is_active').prop('checked', rule.is_active == 1);
            $('#modal-title').text('Edit Scoring Rule');
            modal.show();
        });

        // Save Rule
        $('#scoring-rule-form').on('submit', function (e) {
            e.preventDefault();
            var id = $('#rule_id').val();
            var data = $(this).serialize();
            var url = '/wp-json/aqop/v1/scoring-rules';
            var method = 'POST';

            if (id) {
                url += '/' + id;
                method = 'PUT'; // Or POST if method override needed, but WP REST supports PUT
            }

            $.ajax({
                url: url,
                method: method,
                data: data,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function (response) {
                    modal.hide();
                    loadRules();
                },
                error: function (err) {
                    alert('Error saving rule');
                }
            });
        });

        // Delete Rule
        $(document).on('click', '.delete-rule', function () {
            if (!confirm('Are you sure?')) return;
            var id = $(this).data('id');
            $.ajax({
                url: '/wp-json/aqop/v1/scoring-rules/' + id,
                method: 'DELETE',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function (response) {
                    loadRules();
                }
            });
        });

        // Bulk Recalculate
        $('#bulk-recalculate-scores').on('click', function () {
            if (!confirm('This will recalculate scores for ALL leads. Continue?')) return;
            var btn = $(this);
            btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: '/wp-json/aqop/v1/leads/bulk-recalculate-score',
                method: 'POST',
                data: { all: true },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function (response) {
                    alert(response.message);
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Recalculate All Scores');
                },
                error: function () {
                    alert('Error recalculating scores');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Recalculate All Scores');
                }
            });
        });
    });
</script>