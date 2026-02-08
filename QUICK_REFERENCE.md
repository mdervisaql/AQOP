# 🚀 AQOP Project Quick Reference Guide

**Last Updated:** February 7, 2026 | **Version:** 1.0.10

---

## 📌 Quick Links & Access Points

### Backend Admin Pages
| الصفحة | الرابط | الوصول |
|------|--------|--------|
| **لوحة التحكم** | `wp-admin/admin.php?page=aqop-leads-dashboard` | Admin, Manager |
| **جميع العملاء** | `wp-admin/admin.php?page=aqop-leads` | Admin, Manager, Supervisor |
| **تفاصيل العميل** | `wp-admin/admin.php?page=aqop-leads-view&lead_id=ID` | المختص + صاحب السجل |
| **إضافة عميل** | `wp-admin/admin.php?page=aqop-leads-form` | Admin, Manager |
| **استيراد/تصدير** | `wp-admin/admin.php?page=aqop-import-export` | Admin |
| **الإعدادات** | `wp-admin/admin.php?page=aqop-settings` | Admin |
| **توثيق API** | `wp-admin/admin.php?page=aqop-leads-api` | Admin, Manager |
| **مركز التحكم** | `wp-admin/admin.php?page=aqop-control-center` | جميع الأدوار |

### API Endpoints
```
Base URL: /wp-json/aqop/v1/

Leads:
  GET    /leads                 - List all leads
  GET    /leads/{id}            - Get single lead
  POST   /leads                 - Create lead
  PUT    /leads/{id}            - Update lead
  DELETE /leads/{id}            - Delete lead

References:
  GET    /leads/statuses        - Lead statuses
  GET    /leads/countries       - Countries list
  GET    /leads/sources         - Lead sources
```

### Database Tables (Quick Reference)
```
Main Tables:
  wp_aq_leads                - Lead records
  wp_aq_leads_notes          - Lead notes/comments
  wp_aq_leads_status         - Available statuses
  wp_aq_leads_sources        - Lead sources
  wp_aq_leads_campaigns      - Marketing campaigns

Event/Logging:
  wp_aq_events_log           - All system events
  wp_aq_dim_*                - Dimension tables
```

---

## 🏗️ Project Structure Overview

```
aqleeat-operation/
│
├── wp-content/plugins/
│   │
│   ├── aqop-core/                (Foundation - 40+ PHP files)
│   │   ├── includes/             (Core classes)
│   │   ├── admin/                (Admin UI)
│   │   ├── api/                  (Core APIs)
│   │   └── assets/               (JS/CSS)
│   │
│   ├── aqop-leads/               (Lead Management - 60+ PHP files)
│   │   ├── includes/             (Lead classes & integrations)
│   │   ├── admin/                (Lead admin pages)
│   │   ├── api/                  (Lead APIs)
│   │   ├── public/               (Public form)
│   │   └── CHANGELOG.md          (Version history)
│   │
│   ├── aqop-jwt-auth/            (Authentication)
│   └── aqop-feedback/            (Feedback system)
│
├── aqop-frontend/                (React App - 215MB with node_modules)
│   ├── src/
│   │   ├── api/                  (API client)
│   │   ├── auth/                 (Auth system)
│   │   ├── pages/                (Page components)
│   │   ├── components/           (Reusable components)
│   │   └── hooks/                (Custom hooks)
│   ├── package.json
│   └── vite.config.js
│
└── docs/                         (Documentation)
    ├── PROJECT_SYSTEM_DOCUMENTATION.md
    ├── DEVELOPMENT_METHODOLOGY.md
    ├── DEPLOYMENT_GUIDE.md
    └── GITHUB_DEPLOYMENT.md
```

---

## 🔐 Security Model Summary

### Authentication Flow
```
User Login
  ↓
WordPress User Check
  ↓
JWT Generation (HS256)
  ↓
Return: Access Token (15 min) + Refresh Token (7 days)
  ↓
Token Stored in localStorage
  ↓
Bearer Token in all API requests
  ↓
Automatic Token Refresh on Expiry
```

### Authorization Layers
```
Layer 1: Route Guards (Frontend)
  ├─ Check authentication
  ├─ Check user role
  └─ Redirect if unauthorized

Layer 2: API Middleware (REST)
  ├─ Verify JWT signature
  ├─ Check permissions
  └─ Filter data by role

Layer 3: Database Query (PHP)
  ├─ Build role-specific WHERE clauses
  ├─ Verify data ownership
  └─ Sanitize inputs

Layer 4: WordPress Capabilities
  ├─ Check manage_options
  ├─ Check custom capabilities
  └─ Log all actions
```

### Role Hierarchy
```
100 | administrator / operation_admin
    | ├─ Full access to everything
    | └─ Can manage all users
    ↓
80  | operation_manager
    | ├─ Full lead management
    | └─ Can assign to supervisors/agents
    ↓
50  | aq_supervisor
    | ├─ Can see team leads
    | └─ Can manage agents
    ↓
10  | aq_agent
    | └─ Can only view assigned leads
```

---

## 📊 Key Features Summary

| Feature | Status | Location |
|---------|--------|----------|
| **Lead CRUD** | ✅ Complete | `admin/class-leads-admin.php` |
| **Analytics Dashboard** | ✅ Complete | `admin/views/dashboard.php` |
| **Advanced Filters** | ✅ 6 filters | `includes/class-leads-manager.php` |
| **Bulk Operations** | ✅ Complete | `admin/js/leads-admin.js` |
| **CSV Import/Export** | ✅ Complete | `admin/views/import-export.php` |
| **Public Form** | ✅ Complete | `public/class-public-form.php` |
| **REST API** | ✅ 8 endpoints | `api/class-leads-api.php` |
| **Airtable Sync** | ✅ Bi-directional | `includes/class-airtable-sync.php` |
| **Telegram Notifications** | ✅ Complete | Integration Hub |
| **Email Notifications** | ✅ Complete | `AQOP_Public_Form` |
| **Event Logging** | ✅ Complete | `AQOP_Event_Logger` |
| **Notes Management** | ✅ AJAX | `admin/js/lead-detail.js` |
| **Lead Assignment** | ✅ Complete | `class-leads-manager.php` |
| **Custom Fields** | ✅ JSON support | `lead-detail.php` |
| **Activity Feed** | ✅ Complete | `dashboard.php` |
| **Permission Control** | ✅ Role-based | Backend enforcement |

---

## 🛠️ Technology Stack at a Glance

### Backend
```
WordPress 6.0+ (Foundation)
├── PHP 8.1+ (Server Logic)
├── MySQL 8.0 (Data Storage)
└── WordPress REST API (JSON Interface)
```

### Frontend
```
React 19.2.0 (UI Framework)
├── React Router 6.28.0 (Routing)
├── Axios 1.13.2 (HTTP Client)
├── React Query 5.90.10 (State Management)
├── Tailwind CSS 3.4.17 (Styling)
├── Lucide React 0.554.0 (Icons)
├── Recharts 2.15.4 (Charts)
└── Vite 7.2.2 (Build Tool)
```

### Integrations
```
External Services:
├── Airtable (CRM sync)
├── Telegram (Notifications)
├── Facebook Ads (Lead import)
├── WhatsApp Business (Messaging)
└── Dropbox (File storage)
```

---

## 📈 Project Statistics

### Code Metrics
- **Total PHP Files:** 103
- **Total JavaScript Files:** 28
- **Total Documentation Files:** 50+
- **Lines of Code:** 15,000+
- **Development Time:** 4 hours
- **Current Version:** 1.0.10

### Database
- **Tables Created:** 20+
- **Relations Defined:** Complex star schema
- **Indexes:** Optimized for queries
- **Pre-loaded Data:** 5 statuses + 6 sources

### Features
- **Implemented:** 22+
- **In Development:** Frontend pages
- **Planned:** Mobile app, AI features

### Security
- **Authentication:** JWT (HS256)
- **Authorization:** 4-level role hierarchy
- **Encryption:** AES-256 capable
- **Audit Trail:** Complete event logging
- **Input Protection:** SQLi + XSS + CSRF

---

## 🚀 Getting Started

### 1. Installation
```bash
# Clone repository
git clone <repo-url>
cd aqleeat-operation

# Activate plugins in WordPress admin
# - aqop-core (first)
# - aqop-leads
# - aqop-jwt-auth

# Tables created automatically
```

### 2. Configuration
```
WordPress Admin → مركز العمليات → الإعدادات
├── Add Airtable API key (optional)
├── Add Telegram Bot token (optional)
└── Configure notification preferences
```

### 3. Frontend Setup
```bash
cd aqop-frontend
npm install
cp .env.example .env
# Edit .env with API URL
npm run dev
```

### 4. Create First Lead
```
Method 1: Use admin form
  → Admin Dashboard → Add New Lead

Method 2: Use public form
  → Add shortcode [aqop_lead_form] to page

Method 3: Use REST API
  → POST /wp-json/aqop/v1/leads with auth
```

---

## 📋 Common Tasks

### Add New Lead (Admin)
1. Go to `wp-admin/admin.php?page=aqop-leads-form`
2. Fill in lead details
3. Click Save
4. Lead auto-syncs to Airtable (if configured)

### Search Leads
1. Go to Leads list
2. Use search bar (Name, Email, Phone, WhatsApp)
3. Apply filters (Status, Priority, Country, Source, Campaign, Date)
4. Results update live

### Export Leads
1. Go to Import/Export page
2. Select filters
3. Click Export CSV
4. File downloads to computer

### Import Leads
1. Go to Import/Export page
2. Download template (optional)
3. Fill CSV file
4. Upload and confirm
5. Leads created in batch

### Send Telegram Notification
- Triggered automatically on:
  - New lead from public form
  - Status change
  - Assignment change
- Manual send via: Integration Hub API

### View Activity Log
1. Go to Dashboard
2. Check "Recent Activity" section
3. Click event for more details
4. Or go to Control Center for full log

---

## 🔧 Development Quick Reference

### File Locations

**Lead Management Classes:**
```
wp-content/plugins/aqop-leads/includes/
├── class-leads-core.php         (Bootstrap)
├── class-leads-manager.php      (CRUD + Operations)
├── class-airtable-sync.php      (Airtable)
├── class-notification-manager.php
├── class-lead-scoring.php       (Lead scoring)
└── class-lead-details-handler.php
```

**Admin Interface:**
```
wp-content/plugins/aqop-leads/admin/
├── class-leads-admin.php        (Main admin class)
├── class-notifications-admin.php
├── views/
│   ├── dashboard.php            (Analytics)
│   ├── lead-detail.php          (Detail view)
│   ├── lead-form.php            (Create/Edit form)
│   ├── settings.php             (Configuration)
│   └── import-export.php
└── js/
    ├── lead-detail.js           (Notes, etc.)
    └── leads-admin.js           (Bulk ops)
```

**API Layer:**
```
wp-content/plugins/aqop-leads/api/
├── class-leads-api.php          (Lead endpoints)
├── class-notifications-api.php
├── class-activity-api.php
├── class-communications-api.php
└── class-facebook-api.php
```

### Adding New Feature (Checklist)

- [ ] **Database:** Run installer for tables
- [ ] **Manager Class:** Add method to `AQOP_Leads_Manager`
- [ ] **Admin Page:** Create view in `admin/views/`
- [ ] **Admin Class:** Add handling in `AQOP_Leads_Admin`
- [ ] **API Endpoint:** Add in `api/class-leads-api.php`
- [ ] **Event Logging:** Add `AQOP_Event_Logger::log()` call
- [ ] **Permissions:** Check user capabilities
- [ ] **Frontend:** Add React component/page
- [ ] **Documentation:** Update README + docs

### Testing Endpoints

```bash
# Get all leads
curl -X GET 'http://localhost:8888/wp-json/aqop/v1/leads' \
  -H 'Authorization: Bearer YOUR_TOKEN'

# Create lead
curl -X POST 'http://localhost:8888/wp-json/aqop/v1/leads' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Test Lead",
    "email": "test@example.com",
    "phone": "+1234567890"
  }'

# Update lead
curl -X PUT 'http://localhost:8888/wp-json/aqop/v1/leads/1' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"status_id": 2}'

# Delete lead
curl -X DELETE 'http://localhost:8888/wp-json/aqop/v1/leads/1' \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

---

## 🐛 Troubleshooting

### Issue: API returns 401 Unauthorized
**Solution:**
1. Check token in localStorage
2. Verify token hasn't expired
3. Regenerate token by logging in again
4. Check CORS headers in WordPress config

### Issue: Airtable sync fails
**Solution:**
1. Verify Airtable API key is correct
2. Check Base ID matches
3. Check table name matches
4. Review sync logs in event log

### Issue: Frontend won't connect to API
**Solution:**
1. Verify API URL in `.env` file
2. Check WordPress is running
3. Verify CORS is configured
4. Check browser console for errors

### Issue: Permissions not working
**Solution:**
1. Verify user has correct role
2. Check role capabilities in Settings
3. Clear WordPress cache if using cache plugin
4. Verify database tables were created

### Issue: Rate limiting preventing submissions
**Solution:**
1. Wait 10 minutes for IP cooldown
2. Submit from different IP/network
3. Disable rate limiting in settings (dev only)
4. Check IP in database

---

## 📝 Key Concepts

### Lead States
```
Pending (معلق)
  ↓
Contacted (تم الاتصال)
  ↓
Qualified (مؤهل)
  ├→ Converted (محول) ✓ Sale!
  └→ Lost (خاسر) ✗ No sale
```

### Lead Priority Levels
- 🔴 Urgent (عاجل) - Requires immediate action
- 🟠 High (عالي) - Important, handle soon
- 🟡 Medium (متوسط) - Normal processing
- 🟢 Low (منخفض) - Can wait

### Lead Sources
1. Facebook Ads
2. Google Ads
3. Instagram Ads
4. Website Form
5. Referral
6. Direct Contact

### Standard Countries
- Kingdom of Saudi Arabia (السعودية)
- United Arab Emirates (الإمارات)
- Kuwait (الكويت)
- Qatar (قطر)
- Bahrain (البحرين)
- Oman (عمان)
- ... and more

---

## 📚 Important Files to Know

| File | Purpose | Modified |
|------|---------|----------|
| `aqop-core.php` | Core plugin loader | M |
| `aqop-leads.php` | Leads plugin loader | M |
| `class-leads-manager.php` | All lead operations | M |
| `class-leads-admin.php` | All admin pages | M |
| `class-leads-api.php` | All REST endpoints | M |
| `class-leads-core.php` | Module bootstrap | M |
| `class-airtable-sync.php` | Airtable integration | M |
| `.env` | Frontend configuration | N |
| `tailwind.config.js` | Frontend styling | N |
| `package.json` | Frontend dependencies | N |

Legend: M = Modified, N = New

---

## ✅ Checklist Before Production

- [ ] Database backed up
- [ ] All plugins activated in order
- [ ] API endpoints tested
- [ ] Airtable sync working
- [ ] Telegram notifications working
- [ ] Email notifications working
- [ ] Admin users created
- [ ] Security settings configured
- [ ] CORS headers set correctly
- [ ] SSL/TLS enabled
- [ ] Backups scheduled
- [ ] Monitoring enabled
- [ ] Error logging enabled
- [ ] Performance optimized

---

## 🎓 Resources

### Documentation Files
- 📄 `PROJECT_SYSTEM_DOCUMENTATION.md` - Complete technical reference
- 📄 `DEVELOPMENT_METHODOLOGY.md` - Development process
- 📄 `SECURITY_IMPLEMENTATION_COMPLETE.md` - Security details
- 📄 `DEPLOYMENT_GUIDE.md` - Production deployment
- 📄 `README.md` - Project overview

### External Resources
- 🌐 WordPress.org - WordPress documentation
- 🌐 Airtable API - Airtable integration docs
- 🌐 Telegram API - Telegram bot docs
- 🌐 React Docs - React documentation
- 🌐 WordPress REST API - REST API guide

---

## 📞 Support & Maintenance

### Regular Tasks
- ✅ Weekly: Check error logs
- ✅ Weekly: Verify Airtable sync
- ✅ Monthly: Review user activity
- ✅ Monthly: Update WordPress/plugins
- ✅ Quarterly: Review and optimize database

### Monitoring Points
- 🔍 Database size growth
- 🔍 API response times
- 🔍 Failed authentication attempts
- 🔍 Airtable sync failures
- 🔍 Email delivery issues

---

**Project Status:** ✅ Ready for Production (Backend 100%, Frontend 70%)  
**Last Review:** February 7, 2026  
**Next Review:** Recommended in 1 month
