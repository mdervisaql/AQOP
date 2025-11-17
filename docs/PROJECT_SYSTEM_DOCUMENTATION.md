# 📘 AQOP Operation Platform - Complete System Documentation

**Version:** 1.0.10  
**Last Updated:** November 17, 2025  
**Status:** Production Ready  
**Maintainer:** Operation Platform Engineering (Muhammed Derviş)  

---

## 📋 Table of Contents

1. [System Overview](#-system-overview)
2. [Architecture](#-architecture)
3. [Data Flows](#-data-flows)
4. [Complete Feature List](#-complete-feature-list)
5. [User Personas & Access](#-user-personas--access)
6. [User Journeys](#-user-journeys)
7. [Frontend Access Points](#-frontend-access-points)
8. [Backend Access Points](#-backend-access-points)
9. [API Documentation](#-api-documentation)
10. [Database Schema](#-database-schema)
11. [Integration Points](#-integration-points)
12. [Security Model](#-security-model)
13. [Performance Considerations](#-performance-considerations)
14. [File Structure](#-file-structure)
15. [Code Reference](#-code-reference)
16. [Development Guide](#-development-guide)
17. [Deployment Guide](#-deployment-guide)
18. [Troubleshooting](#-troubleshooting)
19. [Future Enhancements](#-future-enhancements)
20. [Change Log](#-change-log)

---

## 🎯 System Overview

**What is AQOP?**  
The Operation Platform (AQOP) is a modular WordPress-based operations hub that centralizes lead intake, qualification, collaboration, and reporting for Arabic-speaking teams. The core plugin (`aqop-core`) delivers shared infrastructure (roles, event logging, integrations), while the Leads Module (`aqop-leads`) contributes a complete CRM-lite experience built directly into the WordPress admin.

**Business Problem Solved**  
AQOP eliminates fragmented spreadsheets, third-party SaaS costs, and inconsistent follow-ups by providing:
- Single source of truth for lead data, status, and ownership
- Automated intake from web forms and APIs
- Immediate notifications (email, Telegram)
- Analytics dashboards that highlight conversion health
- Bulk operations, CSV workflows, and Airtable sync for BI

**Key Metrics**
- Total bundled features: 22 (see feature inventory)
- Lines of source code (Leads module only): ~8,500
- Supported roles: 4 backend personas + public visitors + API clients
- Estimated SaaS cost savings: ~$4,000/year (replacing tiered CRM licenses)

**Technology Stack**
- WordPress 6.x, PHP 8.1+, MySQL 5.7+
- WP REST API (JSON), AJAX (admin-ajax.php)
- Frontend libraries: jQuery, Chart.js 4.4.0, Dashicons
- Core utilities: `$wpdb`, `AQOP_Event_Logger`, `AQOP_Integrations_Hub`
- Deployment footprint: two plugins (`aqop-core`, `aqop-leads`)

---

## 🏗️ Architecture

### Plugin Structure
- **aqop-core**: foundational services (roles, security guard, event logger, integration hub, control center shell, dimension tables).
- **aqop-leads**: feature plugin layered on top of core, responsible for lead CRUD, admin UI, public form, API controller, dashboard, and settings UI.
- **Communication**: shared namespaces via PHP classes; both plugins load through WordPress hooks and leverage shared tables prefixed with `aq_`.

### File Organization & Hierarchy
```
aqop-core/
  ├─ includes/ (installer, roles manager, integrations hub, event logger)
  ├─ admin/control-center/ (UI shell)
  └─ public/ + assets/
aqop-leads/
  ├─ includes/ (core bootstrap, installer, manager, detail handler)
  ├─ admin/ (class-leads-admin.php + CSS/JS/views)
  ├─ public/ (shortcode class + assets)
  └─ api/ (REST controller)
```

### Class Structure & Dependencies
| Class | Responsibility | Depends On |
| --- | --- | --- |
| `AQOP_Leads_Core` | Bootstraps leads module, loads dependencies, registers REST routes | `AQOP_Event_Logger`, WP hooks |
| `AQOP_Leads_Manager` | Lead CRUD, notes, assignments, Airtable sync | `$wpdb`, `AQOP_Integrations_Hub`, `AQOP_Event_Logger` |
| `AQOP_Lead_Details_Handler` | Prepares enriched data for lead detail view | `$wpdb`, caching |
| `AQOP_Leads_Admin` | All admin pages, forms, AJAX, CSV tooling | WP Admin APIs, `AQOP_Leads_Manager` |
| `AQOP_Public_Form` | Shortcode, AJAX submission, notifications | `$wpdb`, `AQOP_Leads_Manager`, `AQOP_Frontend_Guard` |
| `AQOP_Leads_API` | REST endpoints for leads | WP REST API, `AQOP_Leads_Manager` |
| `AQOP_Event_Logger` (core) | Central log of module events | Custom tables `aq_events_log` |
| `AQOP_Integrations_Hub` (core) | Airtable/Dropbox/Telegram abstractions | cURL/HTTP, wp_remote_* |

### Hook System
- **Actions**: `admin_menu`, `admin_enqueue_scripts`, `wp_ajax_*`, `rest_api_init`, `init`, `aqop_lead_created/updated/deleted`, `aqop_tables_created`, `aqop_installation_complete`.
- **Filters**: standard WP filters (none custom in leads module) + caching via `wp_cache_*`.
- AJAX entry points include `aqop_add_note`, `aqop_edit_note`, `aqop_delete_note`, `aqop_sync_lead_airtable`, `aqop_bulk_action`, `aqop_submit_lead_form`.

### Database Design (High-level)
- **Core Fact Table**: `wp_aq_events_log` captures every significant action with module + event type foreign keys.
- **Dimensions**: `wp_aq_dim_modules`, `wp_aq_dim_event_types`, `wp_aq_dim_countries`, `wp_aq_dim_date`, `wp_aq_dim_time`.
- **Leads Fact Tables**:  
  - `wp_aq_leads` (primary record)  
  - `wp_aq_leads_notes` (1:N)  
  - Supporting dimensions: `wp_aq_leads_status`, `wp_aq_leads_sources`, `wp_aq_leads_campaigns`
- All tables use InnoDB with indexes on status, created date, email, etc., to support filters/search.

---

## 🔄 Data Flows

### Lead Creation (Public Form)
1. Visitor submits `[aqop_lead_form]`.
2. JavaScript validates and sends AJAX request (`aqop_submit_lead_form`) with nonce.
3. `AQOP_Public_Form::handle_form_submission()`:
   - Verifies nonce + rate limit (`AQOP_Frontend_Guard`).
   - Sanitizes fields, maps source/campaign, resolves default status.
   - Creates lead via `AQOP_Leads_Manager::create_lead()`.
   - Optional initial note (message field).
   - Sends admin email + Telegram message (if configured).
   - Logs event via `AQOP_Event_Logger`.

### Lead Update (Admin)
1. Admin loads `lead-detail.php`, edits fields or status.
2. Form submission hits `AQOP_Leads_Admin::handle_form_submission()`.
3. Validation → `AQOP_Leads_Manager::update_lead()` updates DB, logs event, syncs Airtable, updates caches.

### Airtable Sync (Bidirectional)
1. `AQOP_Leads_Manager` calls `AQOP_Integrations_Hub::sync_to_airtable()` after create/update/assign/status change.
2. Airtable push uses API key/base/table, stores `airtable_record_id`.
3. Incoming Airtable webhooks (optional future) processed via REST route (planned) to update WP.
4. Failures logged with severity `error` for monitoring.

### Telegram Notification
1. Triggered by public form submissions (and can be triggered elsewhere via `AQOP_Integrations_Hub::send_telegram`).
2. Uses bot token + chat ID stored in options.
3. Logs success/failure events for auditing.

### Email Notification
1. Public form sends structured email to `admin_email`.
2. Template includes quick link to lead detail page.

### CSV Import / Export
1. Import: Admin uploads CSV on Import/Export page.
   - File parsed server-side with validation, duplicates handled.
   - Rows inserted via `AQOP_Leads_Manager::create_lead()`.
2. Export: filter/search context reused, `generate_csv_export()` streams CSV or AJAX bulk export returns blob for download.

### REST API Handling
1. Clients call `/wp-json/aqop/v1/...`.
2. WP REST API handles authentication (cookies/app passwords).
3. `AQOP_Leads_API` methods sanitize arguments, map status codes, and call `AQOP_Leads_Manager`.
4. Responses include pagination headers + JSON payload.

### Event Logging
1. `AQOP_Event_Logger::log()` called from all critical operations.
2. Writes to `wp_aq_events_log` with module, event type, metadata JSON, and timestamp.
3. Activity feed (dashboard) queries a summarized view of these logs.

---

## ✨ Complete Feature List

| Feature | Location | Entry Point | Personas | Dependencies | Status |
| --- | --- | --- | --- | --- | --- |
| Analytics Dashboard (KPIs + charts) | `admin/views/dashboard.php` | `wp-admin/admin.php?page=aqop-leads-dashboard` | Admin, Manager | `$wpdb`, Chart.js | ✅ Working |
| Leads Table w/ filters, search, sorting, pagination | `admin/class-leads-admin.php` (`render_leads_table`) | `wp-admin/admin.php?page=aqop-leads` | Admin, Manager, Supervisor | `$wpdb`, `AQOP_Leads_Manager` | ✅ Working |
| Lead Detail View (cards, notes, sidebar, delete modal) | `admin/views/lead-detail.php` | `admin.php?page=aqop-leads-view&lead_id=` | Admin, Manager, Supervisor, Agent (own leads) | `AQOP_Lead_Details_Handler`, JS/CSS | ✅ Working |
| Lead Add/Edit Form | `admin/views/lead-form.php` | `admin.php?page=aqop-leads-form` | Admin, Manager | `AQOP_Leads_Manager`, `wp_nonce_field` | ✅ Working |
| Notes Timeline + AJAX add/edit/delete | `lead-detail.php`, `admin/js/lead-detail.js` | Buttons within Lead Detail | Admin, Manager, Supervisor, Note owner | `wp_ajax_aqop_*_note` | ✅ Working |
| Bulk Actions (delete, status change, export) | `leads-admin.js`, `ajax_bulk_action()` | Leads list toolbar | Admin, Manager | WP AJAX, CSV export helper | ✅ Working |
| CSV Import/Export UI | `admin/views/import-export.php` | `wp-admin/admin.php?page=aqop-import-export` | Admin | File uploads, `handle_import_export()` | ✅ Working |
| Settings (sources, integrations, notifications) | `admin/views/settings.php` | `wp-admin/admin.php?page=aqop-settings` | Admin | Options API, `AQOP_Integrations_Hub` | ✅ Working |
| REST API (8 endpoints) | `api/class-leads-api.php` | `/wp-json/aqop/v1/*` | Admin (auth’d), External apps | WP REST API | ✅ Working |
| Public Lead Form Shortcode + AJAX | `public/class-public-form.php` | `[aqop_lead_form]` | Website Visitor | `AQOP_Frontend_Guard`, JS assets | ✅ Working |
| Airtable Sync | `AQOP_Leads_Manager::sync_to_airtable()` | Automatic post-CRUD | Admin ops | `AQOP_Integrations_Hub` | ✅ Working |
| Telegram Notifications | `AQOP_Public_Form::send_notification_email()` | Public submissions | Admin, Manager | `AQOP_Integrations_Hub` | ✅ Working |
| Email Notifications | Same as above | Public submissions | Admin | `wp_mail` | ✅ Working |
| Event Logging + Activity Feed | `AQOP_Event_Logger`, dashboard activity pane | Dashboard | Admin, Manager, Supervisor | `wp_aq_events_log` | ✅ Working |
| Lead Assignment & Permissions | `AQOP_Leads_Manager::assign_lead()` | Lead detail bulk actions | Admin, Manager | WP roles/capabilities | ✅ Working |
| Custom Fields (JSON) | `lead-detail.php`, `lead-form.php` | Lead detail + form | Admin, Manager | `custom_fields` column | ✅ Working |
| Airtable ID caching | `AQOP_Leads_Manager` | Background sync | Admin | wp_cache, DB column | ✅ Working |
| API Docs Admin Page | `admin/views/api-docs.php` | `wp-admin/admin.php?page=aqop-leads-api` | Admin, Manager, External partners | Markdown-like reference | ✅ Working |
| Dashboard Quick Actions | `dashboard.php` | Buttons inside dashboard | Admin, Manager | Admin URLs | ✅ Working |
| Control Center Shell Integration | `aqop-core/admin/control-center` | `wp-admin/admin.php?page=aqop-control-center` | All back-office roles | WordPress admin menu | ✅ Working |

Status Legend: ✅ Working | ⚠️ Needs Testing | ❌ Incomplete

---

## 👥 User Personas & Access

### Administrator (WordPress `administrator` / `operation_admin`, capability `manage_options`)
- **Access:** All control center pages, settings, integrations, import/export, API docs, dashboard, every lead record.
- **Capabilities:** Full CRUD, bulk operations, source/status management, integration credentials, event log viewing.
- **Entry Points:** `wp-admin → مركز العمليات` (Control Center) main menu.
- **Dependencies:** Must retain `manage_options`.

### Manager (`operation_manager` + custom caps like `edit_leads`, `export_analytics`)
- **Access:** Dashboard, All Leads, Lead Detail/Form, Import/Export (read/export), API Docs (read-only).
- **Capabilities:** Create/edit/delete leads, assign agents, execute bulk operations, export CSV, view analytics. Cannot change integration credentials.
- **Entry Points:** `wp-admin → مركز العمليات`.

### Supervisor (`aq_supervisor` conceptual role mapped to WP role with `edit_leads` but not `delete_users`)
- **Access:** Dashboard, All Leads filtered to team (via UI filters), Lead Detail, Notes.
- **Capabilities:** View + edit assigned team leads, add notes, change status, run filters. Cannot delete leads or change settings.
- **Entry Points:** Control Center (same as above) but UI-level restrictions.

### Agent (`aq_agent` conceptual role with `edit_own_leads`)
- **Access:** All Leads list limited to “Assigned to me”, Lead Detail (own leads), Notes.
- **Capabilities:** Update contact info, log notes, change status on owned leads, cannot bulk action or export.
- **Entry Points:** Control Center → All Leads (pre-filtered).

### Website Visitor (Anonymous)
- **Access:** Public pages containing `[aqop_lead_form]`.
- **Capabilities:** Submit contact info, optional message, receives inline success feedback.
- **Entry Points:** `/contact`, landing pages, microsites.

### External Application (API Client)
- **Access:** `/wp-json/aqop/v1/*` endpoints (requires authentication via WP cookie/Application Password/OAuth proxy).
- **Capabilities:** Full CRUD (if user token has `manage_options`), fetch supporting data (statuses/countries/sources), integrate with Zapier or Data Studio.
- **Entry Points:** HTTPS REST endpoints.

---

## 🚶 User Journeys

### Administrator – Morning Health Check
1. Login → `wp-admin`.
2. Navigate to `مركز العمليات → Dashboard`.
3. Review KPIs (total leads, conversion rate).
4. Scan timeline chart for dips/spikes.
5. Inspect recent activity feed, open newest lead.
6. Assign lead to Agent, add onboarding note.
7. Return to All Leads, filter by “Pending”.
8. Select stale leads, bulk change status to “Lost”.
9. Export filtered dataset to CSV for BI team.
10. Go to Settings → Integrations, rotate Airtable API key, save.

### Manager – Campaign Follow-up
1. Dashboard → Quick Action “Pending Leads”.
2. Apply filters: Source = “Facebook Ads”, Date range = last 7 days.
3. Sort by priority, open highest-priority lead.
4. Edit lead details, set status to “Contacted”.
5. Add follow-up note, ping team via @ mention inside note (text).
6. Use bulk export for remaining filtered leads, share with SDR team.

### Supervisor – Team Oversight
1. All Leads page auto-filters `assigned_to = my_team`.
2. Use search to locate a specific phone number.
3. Check lead detail, ensure agent note quality.
4. Trigger Airtable sync via detail page button (if desynced).
5. Return to dashboard to verify conversion improvement week-over-week.

### Agent – Daily Workflow
1. Login; All Leads shows “Assigned to me”.
2. Sort by “Last Updated” ascending.
3. Open first lead, dial from contact card.
4. Log call outcome in notes (AJAX).
5. Change status to “Qualified”.
6. Repeat for remaining queue.

### Website Visitor – Lead Submission
1. Visits landing page with `[aqop_lead_form source="landing" campaign="winter"]`.
2. Completes required fields, optional WhatsApp/country.
3. Clicks submit, sees spinner + success message.
4. Browser optionally redirects to `/thank-you`.
5. Receives confirmation email (if configured externally).

### External Application – CRM Sync
1. Scheduled job hits `GET /wp-json/aqop/v1/leads?status=qualified&per_page=100`.
2. Parses JSON, updates third-party CRM.
3. For new records, posts to `POST /wp-json/aqop/v1/leads`.
4. Error handling uses HTTP 4xx/5xx codes + messages.

---

## 🌐 Frontend Access Points

### Public Lead Form Shortcode
- **Shortcode:** `[aqop_lead_form source="facebook" campaign="summer2025" redirect="/thanks" button_text="Get Started" show_whatsapp="no" show_country="yes"]`
- **Fields:** Name*, Email*, Phone*, WhatsApp (optional), Country (optional), Message (optional).
- **Validation:** Client-side (HTML5) + server-side (nonce, sanitization, rate limit).
- **Assets:** `public/css/public-form.css`, `public/js/public-form.js` (enqueued only when shortcode detected).
- **Success Handling:** Inline message + optional redirect parameter.

---

## 🧰 Backend Access Points

| Page | URL | Template | Description |
| --- | --- | --- | --- |
| Dashboard | `admin.php?page=aqop-leads-dashboard` | `admin/views/dashboard.php` | KPIs, charts, activity feed, quick actions. |
| All Leads | `admin.php?page=aqop-leads` | `render_leads_page()` | Table with filters/search/sort/pagination, bulk actions, inline stats. |
| Lead Detail | `admin.php?page=aqop-leads-view&lead_id={id}` | `admin/views/lead-detail.php` | Comprehensive dossier, notes timeline, Airtable/Telegram actions, delete modal. |
| Lead Form | `admin.php?page=aqop-leads-form&lead_id={id}` | `admin/views/lead-form.php` | Add/edit interface with two-column metabox layout. |
| Import/Export | `admin.php?page=aqop-import-export` | `admin/views/import-export.php` | CSV import wizard, template download, export options. |
| Settings | `admin.php?page=aqop-settings` | `admin/views/settings.php` | Tabs for Sources, Statuses, Integrations, Notifications. |
| API Docs | `admin.php?page=aqop-leads-api` | `admin/views/api-docs.php` | Built-in documentation for developers (endpoints, parameters, samples). |

All pages registered under the Control Center menu (`aqop-control-center`) courtesy of `AQOP_Leads_Admin::register_admin_pages()`.

---

## 📡 API Documentation

**Base URL:** `https://{domain}/wp-json/aqop/v1`  
**Authentication:**  
- WordPress cookies (logged-in admin)  
- Application Passwords (recommended for external systems)  
- OAuth proxy (future)

### Endpoints

| Method | Endpoint | Description | Permission |
| --- | --- | --- | --- |
| GET | `/leads` | List leads with pagination, search, filters | `manage_options` |
| GET | `/leads/{id}` | Retrieve single lead + notes | `manage_options` |
| POST | `/leads` | Create new lead | `manage_options` |
| PUT/PATCH | `/leads/{id}` | Update lead fields/status | `manage_options` |
| DELETE | `/leads/{id}` | Delete lead | `manage_options` |
| GET | `/leads/statuses` | Public list of statuses | Public |
| GET | `/leads/countries` | Public list of countries (active) | Public |
| GET | `/leads/sources` | Public list of sources (active) | Public |

### Sample Request
```bash
curl -X POST https://example.com/wp-json/aqop/v1/leads \
  -u admin:application-password \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Sara Khalid",
    "email": "sara@example.com",
    "phone": "+9715000000",
    "status": "pending",
    "priority": "high",
    "note": "Inbound from Expo booth"
  }'
```

### Sample Response
```json
{
  "message": "Lead created successfully.",
  "lead": {
    "id": 245,
    "name": "Sara Khalid",
    "email": "sara@example.com",
    "phone": "+9715000000",
    "status_id": 1,
    "priority": "high",
    "created_at": "2025-11-17 09:25:10"
  }
}
```

Error responses follow WP conventions (`code`, `message`, `data.status`).

---

## 🗄️ Database Schema

### Core Tables (from `aqop-core`)
- `wp_aq_events_log`: Fact table for all module events; indexed on date_key/module_id, stores payload JSON.
- `wp_aq_dim_modules`: Module metadata (`core`, `leads`, etc.).
- `wp_aq_dim_event_types`: Event taxonomy with severity + category.
- `wp_aq_dim_countries`: Country reference table with Arabic names.
- `wp_aq_dim_date` / `wp_aq_dim_time`: Calendar/time dimensions for analytics.
- `wp_aq_notification_rules`: Dynamic notification rule engine (future automation).

### Leads Module Tables
- `wp_aq_leads`  
  - **Columns:** id, name, email, phone, whatsapp, country_id, source_id, campaign_id, status_id, assigned_to, priority, created_at, updated_at, last_contact_at, airtable_record_id, notes, custom_fields.
  - **Indexes:** status_id, assigned_to, created_at, email, source_id, campaign_id.
- `wp_aq_leads_status`  
  - Status code/name (EN/AR), display color, order, `is_active`.
- `wp_aq_leads_sources`  
  - Source code, name, type, cost per lead, `is_active`.
- `wp_aq_leads_campaigns`  
  - Campaign metadata, schedule, budget, `is_active`.
- `wp_aq_leads_notes`  
  - Lead foreign key, user_id, note text, timestamps (ordered desc).

**Relationships**
- `aq_leads.status_id → aq_leads_status.id`
- `aq_leads.country_id → aq_dim_countries.id`
- `aq_leads.source_id → aq_leads_sources.id`
- `aq_leads.campaign_id → aq_leads_campaigns.id`
- `aq_leads.assigned_to → wp_users.ID`
- `aq_leads_notes.lead_id → aq_leads.id`

---

## 🔗 Integration Points

### Airtable
- **Configuration:**  
  `AQOP_AIRTABLE_API_KEY`, `AQOP_AIRTABLE_BASE_ID`, `AQOP_AIRTABLE_TABLE_NAME` constants OR settings page entries.  
- **Triggers:** On create/update/assign/status change (automatic). Manual re-sync from Lead Detail view (AJAX).  
- **Data Mapping:** Name, Email, Phone, WhatsApp, Country, Status, Source, Priority, Assigned To, Created/Updated timestamps, WP ID.  
- **Error Handling:** Retries with exponential backoff; failures logged (`airtable_sync_failed`) and surfaced in activity feed.  
- **Bidirectional Notes:** Pull via webhook planned; currently push-only.

### Dropbox
- **Used For:** Import/export archives, future document storage.  
- **Configuration:** `AQOP_DROPBOX_ACCESS_TOKEN`.  
- **Flow:** `AQOP_Integrations_Hub::upload_to_dropbox()` used by automation scripts (not exposed in UI yet). Creates optional share links.  
- **Access Control:** Token stored outside repo (wp-config constants). Logs successes/failures.

### Telegram
- **Configuration:** `AQOP_TELEGRAM_BOT_TOKEN` constant + option `aqop_telegram_lead_notifications_chat`.  
- **Triggers:** Public lead form submissions (default). Additional triggers can use `AQOP_Integrations_Hub::send_telegram()`.  
- **Formatting:** HTML (bold headings, emojis) to highlight new leads.  
- **Error Handling:** Logging via `telegram_message_failed`.

### REST / Webhooks
- **REST API:** Documented above.  
- **Webhooks:** Provided helper in integrations hub (`send_webhook`). Can deliver outbound notifications to Zapier/Make.  
- **Rate Limiting:** Recommended via reverse proxy; API also enforces sensible per_page ≤ 200.

---

## 🔐 Security Model

- **Capabilities & Roles:**  
  - Admin pages gated by `manage_options`.  
  - AJAX endpoints verify `current_user_can( 'manage_options' )` or note ownership.  
  - Agents restricted via UI to their assigned leads.
- **Nonce Protection:**  
  - Admin forms: `wp_nonce_field( 'aqop_save_lead', 'aqop_lead_nonce' )`, etc.  
  - AJAX: `check_ajax_referer( 'aqop_leads_nonce', 'nonce' )`.  
  - Public form: `wp_nonce_field( 'aqop_submit_lead', 'aqop_lead_nonce' )`.
- **Input Sanitization:** `sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`, `absint`, `wp_kses_post`.  
- **Output Escaping:** `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`.  
- **SQL Safety:** `$wpdb->prepare` everywhere; no string concatenation.  
- **Rate Limiting:** `AQOP_Frontend_Guard::check_rate_limit( 'lead_submission', 5, 3600 )`.  
- **CSRF / XSS:** Nonces + escaping; lead detail page removes inline scripts/styles in favor of enqueued assets.  
- **Audit Trail:** Every action logged with timestamp, user, payload for compliance.  
- **Secrets Storage:** Integration keys expected in `wp-config.php` or secured options table (WP salts).  

---

## ⚙️ Performance Considerations

- **Database Indexes:** Status, assigned_to, created_at, email columns indexed. Query builder only selects required columns and uses prepared statements.
- **Caching:** Lead detail formatting cached (`wp_cache_set`) for 5 minutes keyed by lead ID, reducing repeated lookups.
- **Asset Loading:** Conditional enqueues for Chart.js (only dashboard), lead detail CSS/JS (only view page), leads filters CSS (only list page), shortcode assets (only if shortcode present).
- **Pagination:** Query builder calculates `LIMIT/OFFSET`, `paginate_links()` used for UI. `per_page` capped at 200 to avoid memory issues.
- **Bulk Operations:** AJAX-based to avoid page timeouts; progress feedback via alerts, CSV downloads happen client-side to reduce server load.
- **AJAX vs Full Reload:** Notes, Airtable sync, and bulk actions run asynchronously to keep UI responsive.
- **Import Handling:** CSV imports processed server-side with chunking and server limits in mind; duplicates handled gracefully.

---

## 🗂️ File Structure

```
aqleeat-operation/wp-content/plugins/
├── aqop-core/
│   ├── aqop-core.php
│   ├── includes/
│   │   ├── class-aqop-core.php
│   │   ├── class-installer.php
│   │   ├── authentication/class-roles-manager.php
│   │   ├── security/class-frontend-guard.php
│   │   ├── events/class-event-logger.php
│   │   └── integrations/class-integrations-hub.php
│   ├── admin/control-center/
│   │   └── class-control-center.php
│   └── doc/*.md (phase summaries, guides)
└── aqop-leads/
    ├── aqop-leads.php
    ├── includes/
    │   ├── class-leads-core.php
    │   ├── class-leads-manager.php
    │   ├── class-leads-installer.php
    │   └── class-lead-details-handler.php
    ├── admin/
    │   ├── class-leads-admin.php
    │   ├── css/ (leads-admin.css, lead-detail.css, leads-filters.css)
    │   ├── js/ (leads-admin.js, lead-detail.js)
    │   └── views/ (dashboard.php, lead-detail.php, lead-form.php, settings.php, import-export.php, api-docs.php)
    ├── public/ (class-public-form.php + assets)
    └── api/class-leads-api.php
```

---

## 💻 Code Reference

### `AQOP_Leads_Manager`
- `create_lead( $data )`: Inserts sanitized data, logs event, triggers Airtable sync.
- `update_lead( $id, $data )`: Partial updates with diff logging and resync.
- `delete_lead( $id )`: Cascades notes, logs deletion.
- `assign_lead( $id, $user_id )`: Updates assignment + logs.
- `change_status( $id, $status_id )`: Updates status, logs, syncs.
- `add_note() / get_notes() / ajax_edit_note() / ajax_delete_note()`: Complete note lifecycle with permission checks.
- `query_leads( $args )`: Core query builder powering table, API, exports.

### `AQOP_Leads_Admin`
- **Menus:** Registers Dashboard, All Leads, Lead Form, Import/Export, Settings, API Docs.
- **Assets:** Conditional enqueues (Chart.js, CSS, JS).
- **Rendering:** `render_leads_page`, `render_lead_detail_page`, `render_lead_form_page`, `render_dashboard_page`, `render_settings_page`.
- **Handlers:**  
  - Form submissions (`handle_form_submission`, `handle_save_lead`, `handle_delete_lead`).  
  - Import/export pipeline (`handle_import_export`, `handle_export`, `download_csv_template`).  
  - Settings tabs (`handle_settings_save`, `add_lead_source`, `update_integration_settings`, `update_notification_settings`).  
  - AJAX endpoints for notes, Airtable sync, bulk actions.

### `AQOP_Public_Form`
- Registers shortcode, enqueues assets, handles AJAX submissions, sends notifications, enforces rate limits.

### `AQOP_Leads_API`
- Encapsulates REST routes, parameter schemas, permission callbacks, JSON responses.

### `AQOP_Integrations_Hub`
- Provides reusable methods for Airtable sync, Dropbox uploads, Telegram messages, webhooks, and health checks with logging.

---

## 🧑‍💻 Development Guide

1. **Environment Setup**
   - Clone repo into local WP install.
   - Activate `aqop-core`, then `aqop-leads`.
   - Run installer via plugin activation (creates tables).
   - Configure `wp-config.php` for debug logging (`WP_DEBUG`, `SAVEQUERIES` optional).
2. **Workflow**
   - Create feature branch (`git checkout -b feature/...`).
   - Make changes with PHPCS compliance (WordPress standards).
   - Regenerate assets if necessary (currently vanilla CSS/JS).
   - Update `PROJECT_SYSTEM_DOCUMENTATION.md` + `CHANGELOG.md`.
   - Write regression tests manually (smoke list below).
3. **Testing Checklist**
   - [ ] Create/update/delete lead via admin UI.
   - [ ] Add/edit/delete note (AJAX).
   - [ ] Bulk actions (delete/status/export).
   - [ ] CSV import (sample file) + export.
   - [ ] Public form submission (rate limit, validation).
   - [ ] REST API (GET/POST/DELETE) with auth.
   - [ ] Airtable sync (log success/failure).
   - [ ] Telegram notification (if token available).
   - [ ] Dashboard charts load without console errors.
   - [ ] Settings save (sources/integrations).
4. **Coding Standards**
   - Follow WordPress PHPCS ruleset.
   - Use strict escaping/sanitization.
   - Keep translation-ready strings (`esc_html__`, etc.).
   - Avoid inline scripts/styles in PHP templates.

---

## 🚀 Deployment Guide

### Pre-Deployment
1. Backup DB + `wp-content` (snapshot or hosting backup).
2. Verify staging environment is on latest commit and QA’d.
3. Ensure `wp-config.php` contains updated integration keys.
4. Confirm `PROJECT_SYSTEM_DOCUMENTATION.md` version/changelog updated.
5. Communicate maintenance window if downtime expected.

### Deployment Steps
1. Upload/rsync `aqop-core` and `aqop-leads` to production `wp-content/plugins`.
2. In WordPress admin, deactivate both plugins (if already active) to trigger clean activation hooks, then reactivate in order: `aqop-core` → `aqop-leads`.
3. Visit Control Center to ensure menus render without fatal errors.
4. Navigate to Settings, verify integration credentials and run health checks (Airtable, Telegram).
5. Run smoke tests (create lead, submit public form, check dashboard).

### Post-Deployment
1. Clear caches/CDN if site uses caching.
2. Monitor `debug.log` and system emails/Telegram for 24h.
3. Update documentation with deployment date + version.

---

## 🛠️ Troubleshooting

| Symptom | Probable Cause | Resolution |
| --- | --- | --- |
| Control Center menu missing | Core plugin inactive or fatal error during boot | Check `wp-content/plugins/aqop-core` activation, review `debug.log`, ensure PHP ≥ 7.4 |
| Leads page blank or 500 | DB tables missing | Re-run installer via plugin deactivate/activate, verify tables exist (`SHOW TABLES LIKE 'wp_aq_leads'`) |
| AJAX note actions fail (403) | Nonce expired or user lacks capability | Refresh page to regenerate nonce, confirm user role `manage_options` or note ownership |
| Public form rate limit error | Too many submissions from same IP | Wait 1 hour or adjust `AQOP_Frontend_Guard` thresholds |
| Airtable sync errors | Invalid API key/table, network failure | Update credentials in Settings, check server can reach `api.airtable.com`, inspect activity feed for error details |
| CSV import stalls | Large file size or invalid headers | Use provided template, split file into smaller batches (<2k rows), check PHP `upload_max_filesize` |
| REST API 403 | User lacks `manage_options` capability | Use admin account or grant necessary capability via custom role |
| Chart.js not loading | CDN blocked | Bundle Chart.js locally or allowlist CDN domain |

---

## 🔮 Future Enhancements

1. **Mobile Companion App (Phase 5)** – React Native app consuming REST API with push notifications.
2. **Advanced Analytics Builder (Phase 6)** – Customizable reports, scheduled email exports.
3. **AI Lead Scoring (Phase 7)** – Auto-prioritize leads based on historical conversion data.
4. **WhatsApp Business Integration (Phase 8)** – Two-way messaging, template replies, chat history.
5. **Zapier/Make Connectors** – Prebuilt recipes using REST endpoints.
6. **Slack Notifications** – Alternative to Telegram for bilingual teams.

---

## 📝 Change Log

| Version | Date | Highlights |
| --- | --- | --- |
| **1.0.10** | 2025-11-17 | Added analytics dashboard (KPIs, charts, activity feed), settings enhancements, Chart.js integration, dashboard-first menu routing. |
| **1.0.8** | 2025-11-17 | CSV import/export system, duplicate handling, template generator. |
| **1.0.6** | 2025-11-17 | REST API (8 endpoints), public lead form, Telegram & email notifications. |
| **1.0.5** | 2025-11-17 | Advanced filters, search, sorting, pagination, bulk operations. |
| **1.0.0** | 2025-11-15 | Initial release: lead CRUD, notes, event logging, Control Center integration. |

> **Documentation Maintenance Rule:** Update this file whenever new features ship, version bumps occur, or architecture changes. This document is the canonical reference for all future development conversations.

---

