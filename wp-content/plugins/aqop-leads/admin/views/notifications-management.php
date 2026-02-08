<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Notifications Management', 'aqop-leads'); ?></h1>
    <hr class="wp-header-end">

    <div class="nav-tab-wrapper">
        <a href="#templates" class="nav-tab nav-tab-active"
            onclick="switchTab(event, 'templates')"><?php _e('Templates', 'aqop-leads'); ?></a>
        <a href="#settings" class="nav-tab"
            onclick="switchTab(event, 'settings')"><?php _e('System Settings', 'aqop-leads'); ?></a>
    </div>

    <div id="tab-templates" class="tab-content" style="margin-top: 20px;">
        <div class="tablenav top">
            <div class="alignleft actions">
                <button class="button button-primary"
                    onclick="openTemplateModal()"><?php _e('Add New Template', 'aqop-leads'); ?></button>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'aqop-leads'); ?></th>
                    <th><?php _e('Event Type', 'aqop-leads'); ?></th>
                    <th><?php _e('Channels', 'aqop-leads'); ?></th>
                    <th><?php _e('Target Roles', 'aqop-leads'); ?></th>
                    <th><?php _e('Priority', 'aqop-leads'); ?></th>
                    <th><?php _e('Status', 'aqop-leads'); ?></th>
                    <th><?php _e('Actions', 'aqop-leads'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($templates)): ?>
                    <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><strong><?php echo esc_html($template->name); ?></strong></td>
                            <td><code><?php echo esc_html($template->event_type); ?></code></td>
                            <td>
                                <?php
                                $channels = json_decode($template->notification_channels, true) ?: [];
                                if ($template->push_enabled)
                                    $channels[] = 'push';
                                echo implode(', ', array_map('ucfirst', $channels));
                                ?>
                            </td>
                            <td>
                                <?php
                                $roles = json_decode($template->target_roles, true) ?: [];
                                echo implode(', ', array_map('ucfirst', $roles));
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo esc_attr($template->priority); ?>">
                                    <?php echo ucfirst($template->priority); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($template->enabled): ?>
                                    <span class="dashicons dashicons-yes" style="color: green;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" style="color: red;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small"
                                    onclick='editTemplate(<?php echo json_encode($template); ?>)'>Edit</button>
                                <button class="button button-small button-link-delete"
                                    onclick="deleteTemplate(<?php echo $template->id; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7"><?php _e('No templates found.', 'aqop-leads'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="tab-settings" class="tab-content" style="display: none; margin-top: 20px;">
        <div class="card">
            <h2><?php _e('Browser Push Configuration', 'aqop-leads'); ?></h2>
            <p><?php _e('Configure VAPID keys for Web Push API.', 'aqop-leads'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('VAPID Public Key', 'aqop-leads'); ?></th>
                    <td>
                        <input type="text" class="regular-text" value="<?php echo esc_attr($vapid_public); ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Active Subscriptions', 'aqop-leads'); ?></th>
                    <td>
                        <strong><?php echo intval($push_count); ?></strong>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="button" class="button button-secondary" onclick="generateKeys()">
                    <?php echo $has_vapid ? __('Regenerate Keys', 'aqop-leads') : __('Generate Keys', 'aqop-leads'); ?>
                </button>
                <?php if ($has_vapid): ?>
                    <span class="description" style="color: red; margin-left: 10px;">
                        <?php _e('Warning: Regenerating keys will invalidate all existing subscriptions.', 'aqop-leads'); ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div id="template-modal"
    style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index: 99999;">
    <div
        style="background:#fff; width:600px; margin: 50px auto; padding: 20px; border-radius: 4px; max-height: 90vh; overflow-y: auto;">
        <h2 id="modal-title"><?php _e('Add New Template', 'aqop-leads'); ?></h2>
        <form id="template-form">
            <input type="hidden" name="id" id="template_id" value="0">
            <input type="hidden" name="action" value="aqop_save_notification_template">
            <?php wp_nonce_field('aqop_notifications_nonce', 'nonce'); ?>

            <table class="form-table">
                <tr>
                    <th><?php _e('Name', 'aqop-leads'); ?></th>
                    <td><input type="text" name="name" id="template_name" class="widefat" required></td>
                </tr>
                <tr>
                    <th><?php _e('Event Type', 'aqop-leads'); ?></th>
                    <td>
                        <select name="event_type" id="template_event_type" class="widefat" required>
                            <option value="lead_assigned">Lead Assigned</option>
                            <option value="lead_status_changed">Lead Status Changed</option>
                            <option value="lead_created">Lead Created</option>
                            <option value="lead_note_added">Lead Note Added</option>
                            <option value="new_user_registered">New User Registered</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Title Template', 'aqop-leads'); ?></th>
                    <td><input type="text" name="title_template" id="template_title" class="widefat" required></td>
                </tr>
                <tr>
                    <th><?php _e('Message Template', 'aqop-leads'); ?></th>
                    <td>
                        <textarea name="message_template" id="template_message" class="widefat" rows="4"
                            required></textarea>
                        <p class="description">Variables: {lead_name}, {lead_status}, {user_name}, {site_name}</p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Channels', 'aqop-leads'); ?></th>
                    <td>
                        <label><input type="checkbox" name="channels[]" value="in_app" checked> In-App</label><br>
                        <label><input type="checkbox" name="channels[]" value="telegram"> Telegram</label><br>
                        <label><input type="checkbox" name="channels[]" value="email"> Email</label><br>
                        <label><input type="checkbox" name="push_enabled" value="1"> Browser Push</label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Target Roles', 'aqop-leads'); ?></th>
                    <td>
                        <select name="target_roles[]" id="template_roles" class="widefat" multiple
                            style="height: 100px;">
                            <?php wp_dropdown_roles(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Priority', 'aqop-leads'); ?></th>
                    <td>
                        <select name="priority" id="template_priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'aqop-leads'); ?></th>
                    <td>
                        <label><input type="checkbox" name="enabled" id="template_enabled" value="1" checked>
                            Enabled</label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save Template', 'aqop-leads'); ?></button>
                <button type="button" class="button button-secondary"
                    onclick="closeTemplateModal()"><?php _e('Cancel', 'aqop-leads'); ?></button>
            </p>
        </form>
    </div>
</div>

<script>
    function switchTab(e, tabId) {
        e.preventDefault();
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
        e.target.classList.add('nav-tab-active');
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.getElementById('tab-' + tabId).style.display = 'block';
    }

    function openTemplateModal() {
        document.getElementById('template-form').reset();
        document.getElementById('template_id').value = 0;
        document.getElementById('modal-title').innerText = '<?php _e('Add New Template', 'aqop-leads'); ?>';
        document.getElementById('template-modal').style.display = 'block';
    }

    function closeTemplateModal() {
        document.getElementById('template-modal').style.display = 'none';
    }

    function editTemplate(template) {
        openTemplateModal();
        document.getElementById('template_id').value = template.id;
        document.getElementById('modal-title').innerText = '<?php _e('Edit Template', 'aqop-leads'); ?>';
        document.getElementById('template_name').value = template.name;
        document.getElementById('template_event_type').value = template.event_type;
        document.getElementById('template_title').value = template.title_template;
        document.getElementById('template_message').value = template.message_template;
        document.getElementById('template_priority').value = template.priority;
        document.getElementById('template_enabled').checked = template.enabled == 1;

        // Handle channels
        const channels = JSON.parse(template.notification_channels || '[]');
        document.querySelectorAll('input[name="channels[]"]').forEach(cb => {
            cb.checked = channels.includes(cb.value);
        });
        document.querySelector('input[name="push_enabled"]').checked = template.push_enabled == 1;

        // Handle roles
        const roles = JSON.parse(template.target_roles || '[]');
        const roleSelect = document.getElementById('template_roles');
        Array.from(roleSelect.options).forEach(opt => {
            opt.selected = roles.includes(opt.value);
        });
    }

    function deleteTemplate(id) {
        if (!confirm('<?php _e('Are you sure you want to delete this template?', 'aqop-leads'); ?>')) return;

        jQuery.post(ajaxurl, {
            action: 'aqop_delete_notification_template',
            id: id,
            nonce: '<?php echo wp_create_nonce('aqop_notifications_nonce'); ?>'
        }, function (res) {
            if (res.success) location.reload();
            else alert('Error deleting template');
        });
    }

    function generateKeys() {
        if (!confirm('<?php _e('Are you sure? This will invalidate all existing subscriptions.', 'aqop-leads'); ?>')) return;

        jQuery.post(ajaxurl, {
            action: 'aqop_generate_vapid_keys',
            nonce: '<?php echo wp_create_nonce('aqop_notifications_nonce'); ?>'
        }, function (res) {
            if (res.success) location.reload();
            else alert('Error generating keys');
        });
    }

    jQuery('#template-form').on('submit', function (e) {
        e.preventDefault();
        jQuery.post(ajaxurl, jQuery(this).serialize(), function (res) {
            if (res.success) location.reload();
            else alert('Error saving template');
        });
    });
</script>