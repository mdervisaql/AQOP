# Notification System - Current State Audit

## 1. Overview
The AQOP Platform has a foundational notification system in place (`AQOP_Notification_System`), designed to be rule-based and modular. However, it appears to be currently underutilized, with some features (like the Public Lead Form) implementing their own hardcoded notification logic.

## 2. Current Notification Channels

### Telegram
- **Integration:** Handled by `AQOP_Integrations_Hub::send_telegram`.
- **Configuration:** Requires `AQOP_TELEGRAM_BOT_TOKEN` in `wp-config.php`.
- **Usage:**
    - **Public Lead Form:** Sends a message to a specific chat ID (configured via `aqop_telegram_lead_notifications_chat` option) when a new lead is submitted.
    - **Core System:** Supported as a channel in `AQOP_Notification_System` but no active rules were found in the codebase.

### Email
- **Integration:** Uses standard WordPress `wp_mail()`.
- **Usage:**
    - **Public Lead Form:** Sends an email to the site admin (`admin_email`) upon lead submission.
    - **Core System:** Supported as a channel in `AQOP_Notification_System`.

### In-App / Browser
- **Current Status:** Not implemented. No WebSocket, Server-Sent Events (SSE), or polling mechanisms found in the frontend.

## 3. Architecture & Code Locations

### Core Notification Engine
- **File:** `wp-content/plugins/aqop-core/includes/notifications/class-notification-system.php`
- **Class:** `AQOP_Notification_System`
- **Functionality:**
    - **`send($params)`:** Main entry point. Accepts `module`, `event`, and `data`.
    - **Rule Engine:** Fetches rules from `wp_aq_notification_rules` to determine recipients and channels.
    - **Templating:** Simple variable replacement (`{{variable}}`).
    - **Dispatch:** Routes to `send_telegram` or `send_email`.
    - **Logging:** Logs to `wp_aq_notifications` table.

### Integrations Hub
- **File:** `wp-content/plugins/aqop-core/includes/integrations/class-integrations-hub.php`
- **Class:** `AQOP_Integrations_Hub`
- **Functionality:**
    - Handles low-level API interactions for Telegram, Airtable, and Dropbox.
    - Implements async processing via WP Cron (`wp_schedule_single_event`).
    - Includes retry logic with exponential backoff.

### Public Lead Form (Legacy Implementation)
- **File:** `wp-content/plugins/aqop-leads/public/class-public-form.php`
- **Method:** `send_notification_email`
- **Issue:** Bypasses `AQOP_Notification_System`. Directly calls `wp_mail` and `AQOP_Integrations_Hub::send_telegram`.

### Leads Manager
- **File:** `wp-content/plugins/aqop-leads/includes/class-leads-manager.php`
- **Events:** Fires actions like `aqop_lead_created`, `aqop_lead_updated`, `aqop_lead_assigned`.
- **Status:** These events are ready to be hooked into the notification system but currently appear unconnected in the codebase.

## 4. Database Schema

### `wp_aq_notification_rules` (Assumed)
- Stores rules mapping events (e.g., `lead_created`) to channels and recipients.
- Columns (inferred): `module_code`, `event_type`, `channels` (JSON), `recipient_config` (JSON), `message_template_body`, `message_template_subject`, `priority`, `is_active`.

### `wp_aq_notifications`
- Stores the log/queue of notifications.
- Columns: `id`, `module_code`, `event_type`, `channel`, `recipient_type`, `recipient_id`, `message_body`, `status` ('pending', 'sent', 'failed'), `created_at`, `sent_at`.

## 5. Identified Gaps

1.  **Fragmentation:** The Public Lead Form uses a hardcoded implementation instead of the central `AQOP_Notification_System`.
2.  **Missing Event Listeners:** While `AQOP_Leads_Manager` fires actions, there is no "Bootstrapper" or "Listener" class found that subscribes to these actions and calls `AQOP_Notification_System::send()`.
3.  **No In-App Notifications:** Users have no way to see notifications within the dashboard (bell icon, toast messages).
4.  **No User Preferences:** Users cannot opt-in/out of specific notifications or choose their preferred channels.
5.  **No Frontend Integration:** The React frontend is completely disconnected from the notification system.

## 6. Proposed Improvements

### Phase 1: Consolidation & Backend Fixes
1.  **Refactor Public Form:** Update `AQOP_Public_Form` to use `AQOP_Notification_System::send()` instead of direct calls.
2.  **Create Event Listeners:** Create a `AQOP_Leads_Notifications` class that hooks into `aqop_lead_created`, `aqop_lead_assigned`, etc., and triggers the notification system.
3.  **Seed Default Rules:** Ensure the database is populated with default rules (e.g., "Notify Admin on New Lead" via Email/Telegram).

### Phase 2: In-App Notifications (Frontend)
1.  **API Endpoint:** Create `GET /aqop/v1/notifications` to fetch unread notifications for the current user.
2.  **Frontend UI:** Implement a "Notification Bell" component in the top bar.
3.  **Polling/Real-time:** Implement short-polling (e.g., every 30s) or SSE to fetch new notifications.

### Phase 3: User Preferences
1.  **Settings UI:** Add a "Notifications" tab in the User Profile.
2.  **Backend Logic:** Update `AQOP_Notification_System` to respect user preferences before sending.
