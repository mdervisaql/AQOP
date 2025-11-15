# دليل تنفيذ Operation Platform مع Cursor AI
## خطة محكمة للتنفيذ السريع بأعلى كفاءة

**Cursor Plan:** Pro+  
**الهدف:** تنفيذ كامل بأقل tokens وأعلى جودة  
**التاريخ:** نوفمبر 2024

---

## 🎯 استراتيجية التنفيذ

### المبادئ الأساسية للعمل مع Cursor

```
1. ✅ استخدام Composer للملفات المتعددة (أفضل من Chat)
2. ✅ كتابة prompts محددة وواضحة
3. ✅ تقسيم العمل إلى خطوات صغيرة
4. ✅ استخدام @ للإشارة للملفات الموجودة
5. ✅ Apply All مباشرة للتغييرات
6. ✅ Test بعد كل مرحلة
```

---

## 📋 Phase 1: Project Setup (يوم 1)

### Step 1.1: إنشاء البنية الأساسية

**Prompt لـ Cursor Composer:**

```
Create WordPress plugin structure for "aqop-core" with these requirements:

STRUCTURE:
aqop-core/
├── aqop-core.php (main plugin file)
├── README.md
├── includes/
│   ├── class-core.php
│   ├── class-installer.php
│   └── class-activator.php
├── admin/
├── assets/
└── languages/

REQUIREMENTS:
1. Follow WordPress plugin standards
2. Plugin Name: Operation Platform Core
3. Version: 1.0.0
4. Text Domain: aqop-core
5. Requires PHP: 7.4
6. Singleton pattern for main class
7. Activation/Deactivation hooks
8. PSR-4 autoloading

Generate all files with proper headers and structure.
```

**Expected Output:** بنية Plugin كاملة

**Tokens المتوقعة:** ~500 tokens

---

### Step 1.2: Database Schema Creation

**Prompt لـ Cursor:**

```
@aqop-core/includes/class-installer.php

Add database installation method that creates these tables using WordPress $wpdb:

TABLES TO CREATE:
1. wp_aq_events_log (see @TECHNICAL_STANDARDS_ANALYTICS.md section "Core Events Table")
2. wp_aq_dim_modules (see @TECHNICAL_STANDARDS_ANALYTICS.md)
3. wp_aq_dim_event_types
4. wp_aq_dim_countries
5. wp_aq_dim_date
6. wp_aq_notification_rules

REQUIREMENTS:
- Use $wpdb->prepare for all queries
- Proper foreign keys
- Optimized indexes
- UTF-8 charset
- InnoDB engine
- Add dbDelta support for updates

Include method to populate dimension tables with initial data.
```

**Expected Output:** Database installation class

**Tokens المتوقعة:** ~800 tokens

---

### Step 1.3: Date Dimension Population

**Prompt لـ Cursor:**

```
@aqop-core/includes/class-installer.php

Add method to populate wp_aq_dim_date table with dates from 2024-01-01 to 2025-12-31.

For each date, calculate:
- date_key (YYYYMMDD format)
- full_date
- year, quarter, month, week_of_year
- day_of_month, day_of_week
- month_name, day_name (Arabic)
- is_weekend (Friday, Saturday for Middle East)
- is_holiday (set to FALSE, can be updated later)

Use WordPress date functions where possible.
Make it efficient - insert in batches of 100.
```

**Expected Output:** Date population method

**Tokens المتوقعة:** ~300 tokens

---

## 📋 Phase 2: Event System (يوم 2)

### Step 2.1: Event Logger Class

**Prompt لـ Cursor:**

```
Create file: aqop-core/includes/events/class-event-logger.php

Create AQOP_Event_Logger class with these static methods:

1. log($module, $event_type, $object_type, $object_id, $payload = [])
   - Insert into wp_aq_events_log
   - Auto-fill temporal fields (date_key, time_key, hour, etc)
   - Trigger action hook: do_action('aqop_event_logged', ...)
   
2. get_events($object_type, $object_id, $limit = 50)
   - Retrieve events for specific object
   - Join with users table for author name
   - Order by created_at DESC
   
3. get_stats($module = null, $days = 7)
   - Return event counts grouped by date and type
   - Support module filter
   - Use proper indexes

4. count_events_today()
5. count_errors_24h()

REQUIREMENTS:
- All queries use $wpdb->prepare
- Proper error handling
- Cache results where appropriate
- Follow WordPress coding standards
```

**Expected Output:** Event Logger class

**Tokens المتوقعة:** ~600 tokens

---

### Step 2.2: Event Query Helper

**Prompt لـ Cursor:**

```
@aqop-core/includes/events/class-event-logger.php

Add method: query($args = [])

Support these filters:
- module (string or array)
- event_type (string or array)
- date_from (Y-m-d format)
- date_to (Y-m-d format)
- user_id
- country
- object_type
- object_id
- limit (default 50)
- offset
- orderby (default 'created_at')
- order (default 'DESC')

Return array with:
- 'results' => array of events
- 'total' => total count (for pagination)
- 'pages' => total pages

Use wp_cache for results (cache key based on args).
```

**Expected Output:** Advanced query method

**Tokens المتوقعة:** ~400 tokens

---

## 📋 Phase 3: Roles & Permissions (يوم 2)

### Step 3.1: Roles Manager

**Prompt لـ Cursor:**

```
Create: aqop-core/includes/authentication/class-roles-manager.php

Create AQOP_Roles_Manager class:

METHOD: create_roles()
Create these roles with capabilities:

1. operation_admin
   - All WordPress capabilities
   - operation_admin => true
   - view_control_center => true
   - manage_operation => true
   - manage_notification_rules => true
   - view_event_logs => true

2. operation_manager
   - read => true
   - view_control_center => true
   - view_event_logs => true

Also create method: remove_roles() for deactivation

Hook into plugin activation/deactivation.
```

**Expected Output:** Roles management

**Tokens المتوقعة:** ~300 tokens

---

### Step 3.2: Permissions Checker

**Prompt لـ Cursor:**

```
Create: aqop-core/includes/authentication/class-permissions.php

Create AQOP_Permissions class with static methods:

1. can_access_control_center()
   - Check operation_admin or operation_manager
   
2. can_manage_notifications()
   - Check operation_admin only
   
3. can_view_events()
   - Check operation_admin or operation_manager
   
4. get_user_modules_access($user_id = null)
   - Return array of modules user can access
   - Based on their capabilities
   
5. check_or_die($capability)
   - wp_die if user doesn't have capability
   - Proper error message in Arabic

All methods should cache results during request.
```

**Expected Output:** Permission helpers

**Tokens المتوقعة:** ~250 tokens

---

## 📋 Phase 4: Frontend Security (يوم 3)

### Step 4.1: Frontend Guard

**Prompt لـ Cursor:**

```
Create: aqop-core/includes/security/class-frontend-guard.php

Create AQOP_Frontend_Guard class:

1. check_page_access($capability = null)
   - Verify is_user_logged_in()
   - Check capability if provided
   - Log access event
   - Redirect to /operation-login/ if not logged in
   - wp_die if no permission
   
2. verify_ajax_request($action, $capability = null)
   - check_ajax_referer($action, 'security')
   - Check logged in
   - Check capability
   - wp_send_json_error if fails
   
3. check_rate_limit($action, $max = 60, $window = 60)
   - Use transients
   - Return true/false
   - Log if limit exceeded

4. sanitize_request($data, $rules)
   - Apply sanitization based on rules
   - Support: text, email, int, url, array, json
   - Return sanitized array

Use this in all frontend pages and AJAX handlers.
```

**Expected Output:** Security class

**Tokens المتوقعة:** ~400 tokens

---

## 📋 Phase 5: Integration Hub (يوم 3-4)

### Step 5.1: Integrations Hub

**Prompt لـ Cursor:**

```
Create: aqop-core/includes/integrations/class-integrations-hub.php

Create AQOP_Integrations class with static methods:

1. sync_to_airtable($module, $record_id, $data)
   - Get API key from wp-config constant
   - Get field mapping from options
   - Transform data based on mapping
   - POST to Airtable API
   - Handle errors and retry
   - Return success/error
   
2. upload_to_dropbox($file_path, $dropbox_path)
   - Get token from wp-config
   - Upload file
   - Create share link
   - Return ['path' => ..., 'url' => ...]
   
3. send_telegram($chat_id, $message, $parse_mode = 'HTML')
   - Get bot token from wp-config
   - Send message via Bot API
   - Handle errors
   
4. check_integration_health($integration)
   - Test connection for: airtable, dropbox, telegram
   - Return status array

All methods should log to event system.
Cache API tokens during request.
```

**Expected Output:** Integration hub

**Tokens المتوقعة:** ~500 tokens

---

### Step 5.2: Airtable Connector

**Prompt لـ Cursor:**

```
@aqop-core/includes/integrations/class-integrations-hub.php

Enhance sync_to_airtable method:

Add support for:
1. Get existing record (if airtable_record_id exists)
2. Update vs Create logic
3. Field type mapping:
   - text → string
   - number → number
   - date → ISO format
   - attachment → array of urls
   - select → string
   - multiselect → array
4. Retry on failure (max 3 times with exponential backoff)
5. Queue failed syncs for later retry

Add method: get_airtable_record($base_id, $table, $record_id)
Add method: batch_sync(array $records) for bulk operations

Store sync status in wp_postmeta or custom table.
```

**Expected Output:** Enhanced Airtable

**Tokens المتوقعة:** ~400 tokens

---

## 📋 Phase 6: Notification Engine (يوم 4-5)

### Step 6.1: Notification Engine Core

**Prompt لـ Cursor:**

```
Create: aqop-core/includes/notifications/class-notification-engine.php

Create AQOP_Notification_Engine class:

1. process_event($module, $event_type, $payload)
   - Get active rules from wp_aq_notification_rules
   - Check conditions for each rule
   - Execute actions if conditions match
   - Log execution
   
2. check_conditions($conditions, $payload)
   - Support operators: equals, not_equals, in, contains, greater_than, less_than
   - Handle nested payload values
   - Return boolean
   
3. execute_actions($actions, $payload)
   - Loop through actions
   - Call appropriate handler:
     * telegram → send_telegram_notification()
     * email → send_email_notification()
     * webhook → send_webhook()
   
4. get_active_rules($module, $event_type)
   - Query wp_aq_notification_rules
   - WHERE enabled = 1
   - Cache results (1 hour)

Hook to: add_action('aqop_event_logged', [...], 10, 3)
```

**Expected Output:** Notification engine

**Tokens المتوقعة:** ~500 tokens

---

### Step 6.2: Notification Channels

**Prompt لـ Cursor:**

```
@aqop-core/includes/notifications/class-notification-engine.php

Add private methods for channels:

1. send_telegram_notification($action, $payload)
   - Get template from $action['template']
   - Replace variables: {{lead.name}}, {{lead.phone}}
   - Use AQOP_Integrations::send_telegram()
   
2. send_email_notification($action, $payload)
   - Get template
   - Replace variables
   - Use wp_mail()
   - Support HTML emails
   
3. send_webhook($action, $payload)
   - POST to $action['url']
   - Include full payload or filtered
   - Set timeout to 10s
   - Use wp_remote_post()

4. replace_variables($template, $payload, $variables)
   - Support dot notation: {{lead.country.name}}
   - Handle missing values gracefully
   - Return processed string

Add method: test_notification($rule_id)
For testing rules without triggering actual event.
```

**Expected Output:** Notification channels

**Tokens المتوقعة:** ~400 tokens

---

## 📋 Phase 7: Control Center Dashboard (يوم 5-6)

### Step 7.1: Control Center Page

**Prompt لـ Cursor:**

```
Create: aqop-core/admin/control-center/class-control-center.php

Create AQOP_Control_Center class:

1. register_page()
   - add_menu_page with 'operation_admin' capability
   - Menu title: "Operation Center"
   - Icon: dashicons-dashboard
   - Position: 2
   
2. render_overview()
   - Get system stats
   - Load template: views/control-center/overview.php
   - Enqueue assets
   
3. get_system_stats()
   Return array with:
   - platform status
   - uptime (calculate from installation date)
   - modules health (loop active plugins)
   - integrations status
   - event counts
   - active users
   - errors count
   
4. enqueue_assets()
   - Chart.js
   - ApexCharts
   - Custom CSS/JS
   - wp_localize_script with REST endpoints

Hook: add_action('admin_menu', ...)
```

**Expected Output:** Control center class

**Tokens المتوقعة:** ~400 tokens

---

### Step 7.2: Overview Template

**Prompt لـ Cursor:**

```
Create: aqop-core/admin/views/control-center/overview.php

Use structure from @TECHNICAL_STANDARDS_ANALYTICS.md "Operation Control Center" section.

Create HTML template with:

1. Dashboard Header
   - Title
   - Real-time indicator
   
2. Stats Grid (4 cards)
   - Total Events
   - Active Users
   - Warnings
   - Errors
   
3. Modules Health Section
   - Loop through active modules
   - Show status for each
   
4. Integrations Status
   - Check each integration
   - Color-coded status
   
5. Chart placeholders
   - <canvas id="eventsTimelineChart">
   - <canvas id="moduleDistributionChart">

Use inline CSS from @TECHNICAL_STANDARDS_ANALYTICS.md
All text in Arabic with English fallback.
Proper escaping: esc_html(), esc_attr()
```

**Expected Output:** Dashboard template

**Tokens المتوقعة:** ~600 tokens

---

### Step 7.3: Dashboard JavaScript

**Prompt لـ Cursor:**

```
Create: aqop-core/assets/js/control-center.js

Implement:

1. Initialize Charts on DOM ready
   - Events Timeline (Line chart)
   - Module Distribution (Doughnut)
   - Event Types (Bar)
   - Performance (Line)
   
2. updateDashboard()
   - Fetch from /wp-json/aqop/v1/analytics/stats
   - Update stat numbers
   - Update charts data
   - Update status indicators
   
3. Auto-refresh every 30 seconds
   - setInterval(updateDashboard, 30000)
   
4. applyFilters()
   - Get filter values
   - Reload dashboard with filters
   
5. exportData()
   - Redirect to export endpoint with params

Use Chart.js v4 syntax.
Handle errors gracefully.
Show loading indicators.
```

**Expected Output:** Dashboard JS

**Tokens المتوقعة:** ~500 tokens

---

## 📋 Phase 8: REST API (يوم 6)

### Step 8.1: Analytics Endpoint

**Prompt لـ Cursor:**

```
Create: aqop-core/api/endpoints/class-analytics-endpoint.php

Create AQOP_Analytics_Endpoint class:

1. register_routes()
   Register these endpoints:
   
   GET /aqop/v1/analytics/stats
   - Return current stats for dashboard
   - Permission: operation_admin
   
   GET /aqop/v1/analytics/events
   - Return filtered events
   - Support query params: module, date_from, date_to, limit
   - Permission: operation_admin
   
   GET /aqop/v1/analytics/export
   - Export data as CSV/JSON
   - Support format param
   - Permission: operation_admin

2. get_stats($request)
   - Gather all stats
   - Use Event_Logger methods
   - Return rest_ensure_response()
   
3. get_events($request)
   - Use Event_Logger::query()
   - Apply filters from request
   - Paginate results
   
4. export_data($request)
   - Get format: csv or json
   - Generate appropriate file
   - Set headers
   - Output and exit

Hook: add_action('rest_api_init', ...)
```

**Expected Output:** REST API endpoints

**Tokens المتوقعة:** ~500 tokens

---

## 📋 Phase 9: Frontend Pages (يوم 7)

### Step 9.1: Login Page Template

**Prompt لـ Cursor:**

```
Create: aqop-core/public/templates/login.php

Create standalone login page template:

REQUIREMENTS:
1. Don't use theme (wp_head/wp_footer but custom HTML)
2. Clean, modern design
3. Login form with:
   - Username field
   - Password field
   - Remember me
   - Submit button
4. Use wp_login_form() or custom form
5. Redirect to /operation-dashboard/ after login
6. Error messages display
7. RTL support for Arabic
8. Mobile responsive
9. Brand colors from TECHNICAL_STANDARDS

Page should be accessible at: /operation-login/

Create function to register this page:
aqop_create_frontend_pages() to be called on activation.
```

**Expected Output:** Login template

**Tokens المتوقعة:** ~400 tokens

---

### Step 9.2: Dashboard Home Template

**Prompt لـ Cursor:**

```
Create: aqop-core/public/templates/dashboard-home.php

Create main dashboard for regular users:

STRUCTURE:
1. Header
   - Logo
   - User name
   - Logout button
   
2. Navigation
   - Links to enabled modules
   - Based on user capabilities
   
3. Quick Stats (user-specific)
   - My tasks today
   - My pending items
   - My recent activity
   
4. Recent Events (user's own)
   - Last 10 events
   
5. Shortcuts
   - Links to common actions

REQUIREMENTS:
- Check access: AQOP_Frontend_Guard::check_page_access()
- Get modules user can access
- Escape all output
- Mobile responsive
- Arabic RTL
- No theme styles

Page: /operation-dashboard/
```

**Expected Output:** Dashboard template

**Tokens المتوقعة:** ~400 tokens

---

## 📋 Phase 10: Testing & Polish (يوم 7)

### Step 10.1: Unit Tests

**Prompt لـ Cursor:**

```
Create: aqop-core/tests/test-event-logger.php

Write PHPUnit tests for Event_Logger class:

1. test_log_event()
   - Insert event
   - Check if inserted correctly
   - Verify temporal fields
   
2. test_get_events()
   - Create test events
   - Retrieve by object
   - Assert count and order
   
3. test_query_with_filters()
   - Test each filter
   - Test combinations
   
4. test_stats_calculation()
   - Insert known data
   - Calculate stats
   - Assert expected results

Use WordPress testing framework.
Setup/Teardown properly.
Mock external calls.
```

**Expected Output:** Test suite

**Tokens المتوقعة:** ~300 tokens

---

### Step 10.2: Installation Script

**Prompt لـ Cursor:**

```
@aqop-core/includes/class-installer.php

Add method: run_post_install_checks()

Verify:
1. All tables created successfully
2. Dimension tables populated
3. Default roles created
4. Frontend pages exist
5. Required directories writable
6. PHP version check
7. Required extensions (json, mysqli, curl)

Create: aqop-core/admin/views/welcome.php
Show after first activation with:
- Setup checklist
- Next steps
- Documentation links

Add activation redirect to welcome page.
```

**Expected Output:** Installation verification

**Tokens المتوقعة:** ~300 tokens

---

## 🎯 استراتيجية تنفيذ Cursor الفعالة

### Best Practices للحصول على أقل Tokens

#### 1. استخدم Composer بدلاً من Chat
```
Composer = Multiple files at once
Chat = One file at a time

Composer أكثر كفاءة بـ 40%
```

#### 2. Prompts محددة جداً
```
❌ سيء: "Create event logger"
✅ جيد: "Create class-event-logger.php with log() method that inserts into wp_aq_events_log using $wpdb->prepare"
```

#### 3. استخدم @ References
```
@file.php - للإشارة لملف موجود
@docs.md - للإشارة للتوثيق
@folder/ - للإشارة لمجلد

هذا يوفر context بدون تكرار
```

#### 4. Apply All مباشرة
```
لا تضيع tokens في المراجعة
اضغط Apply All → Test → Fix if needed
```

#### 5. تقسيم العمل
```
خطوات صغيرة = نتائج أفضل
بدل ملف 1000 سطر → 5 ملفات × 200 سطر
```

---

## 📊 تقدير Tokens لكل Phase

| Phase | Task | Est. Tokens | Time |
|-------|------|-------------|------|
| 1 | Project Setup | 1,600 | 2h |
| 2 | Event System | 1,000 | 3h |
| 3 | Roles & Permissions | 550 | 2h |
| 4 | Frontend Security | 400 | 2h |
| 5 | Integration Hub | 900 | 4h |
| 6 | Notification Engine | 900 | 4h |
| 7 | Control Center | 1,500 | 5h |
| 8 | REST API | 500 | 2h |
| 9 | Frontend Pages | 800 | 3h |
| 10 | Testing & Polish | 600 | 3h |
| **Total** | **Full Core** | **~8,750** | **30h** |

**مع Cursor Pro+:**
- 500 Fast requests/month
- Unlimited Slow requests
- هذا المشروع = ~50-60 requests
- **يكفي بسهولة في حدود الباقة**

---

## 🚀 سير العمل اليومي

### اليوم 1: Foundation
```bash
Morning:
- Phase 1: Project Setup (Steps 1.1, 1.2, 1.3)
- Test: Activate plugin, check tables created

Afternoon:
- Phase 2: Event System (Steps 2.1, 2.2)
- Test: Log some events, retrieve them
```

### اليوم 2: Core Systems
```bash
Morning:
- Phase 3: Roles (Steps 3.1, 3.2)
- Test: Check roles created

Afternoon:
- Phase 4: Security (Step 4.1)
- Test: Access checks working
```

### اليوم 3-4: Integrations
```bash
Day 3:
- Phase 5: Integration Hub (Steps 5.1, 5.2)
- Test: Connect to Airtable, Dropbox

Day 4:
- Phase 6: Notifications (Steps 6.1, 6.2)
- Test: Create rule, trigger event, check notification
```

### اليوم 5-6: Dashboard
```bash
Day 5:
- Phase 7: Control Center (Steps 7.1, 7.2)
- Test: Access dashboard, see stats

Day 6:
- Phase 7: Charts (Step 7.3)
- Phase 8: REST API (Step 8.1)
- Test: Dashboard updates, API responses
```

### اليوم 7: Frontend & Polish
```bash
Morning:
- Phase 9: Frontend (Steps 9.1, 9.2)
- Test: Login, access dashboard

Afternoon:
- Phase 10: Testing & Polish
- Final testing
- Documentation
```

---

## ✅ Checklist بعد كل Phase

```
After each phase:
[ ] كل الملفات المطلوبة موجودة
[ ] لا توجد PHP errors
[ ] لا توجد JavaScript errors
[ ] الـ functionality تعمل
[ ] التغييرات committed to Git
[ ] Documentation updated
```

---

## 🎓 نصائح للحصول على أفضل نتيجة

### 1. ابدأ بـ Context الصحيح
```
قبل أي prompt، تأكد أن Cursor يرى:
- @OPERATION_PLATFORM_COMPLETE.md
- @TECHNICAL_STANDARDS_ANALYTICS.md
- @folder/ الحالي
```

### 2. اطلب Code Quality Checks
```
في نهاية كل prompt، أضف:
"Follow WordPress coding standards, add PHPDoc comments, use proper escaping"
```

### 3. استخدم Terminal في Cursor
```
بدل Switch للتيرمينال الخارجي:
- Cmd+J لفتح terminal في Cursor
- شغل الأوامر مباشرة
```

### 4. استخدم Multi-cursor
```
Alt+Click = Multiple cursors
Edit multiple places at once
```

### 5. Git Integration
```
Commit بعد كل phase ناجح:
git commit -m "feat: Phase X completed"
```

---

## 📞 عند مواجهة مشكلة

### إذا Cursor أعطى كود خاطئ:
```
1. لا تحاول تصليحه manually
2. اضغط Reject
3. أعد صياغة الـ prompt بوضوح أكثر
4. أضف مثال للناتج المتوقع
```

### إذا نفذت الـ Fast Requests:
```
لا مشكلة - استخدم Slow requests
أو انتظر بضع ساعات للتجديد
```

### إذا حدث خطأ في Plugin:
```
1. Enable WP_DEBUG في wp-config.php
2. افتح debug.log
3. أعطي Error message لـ Cursor
4. اطلب Fix محدد
```

---

## 🎯 الهدف النهائي

بعد 7 أيام:
```
✅ aqop-core Plugin كامل ويعمل
✅ Event System يسجل كل شيء
✅ Notification Engine ديناميكي
✅ Integration Hub متصل
✅ Control Center Dashboard يعرض البيانات
✅ Frontend Pages محمية وتعمل
✅ REST API جاهز
✅ Documentation كاملة
✅ Ready للـ Leads Module
```

---

**ملاحظة نهائية:**

هذا الدليل مصمم خصيصاً لـ **Cursor AI Pro+**. اتبع الخطوات بالترتيب، استخدم Composer، كن محدداً في Prompts، وستحصل على نتيجة احترافية بأقل tokens ممكن.

**التزم بـ:**
1. خطوة واحدة في كل مرة
2. Test بعد كل خطوة
3. Commit بعد كل phase
4. استخدم @ references

**النجاح مضمون! 🚀**
