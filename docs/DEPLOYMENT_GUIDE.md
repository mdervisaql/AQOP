# 🚀 GitHub Deployment Guide - AQOP Operation Platform

**Repository:** https://github.com/mfarrag2050/OperationSystem  
**Version:** 1.0.10  
**Date:** November 17, 2025  
**Status:** Production Ready

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Git Setup](#git-setup)
3. [Repository Structure](#structure)
4. [Step-by-Step Deployment](#deployment)
5. [Post-Deployment](#post-deployment)
6. [Updating Documentation](#updating-docs)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## ✅ Prerequisites {#prerequisites}

### Required Software

```bash
# 1. Git installed
git --version
# Should show: git version 2.x.x

# 2. GitHub account configured
git config --global user.name "Muhammed Farrag"
git config --global user.email "your-email@example.com"

# 3. SSH key setup (recommended) OR HTTPS token
ssh -T git@github.com
# Should show: Hi mfarrag2050! You've successfully authenticated
```

### Repository Access

```
✅ GitHub Repository: https://github.com/mfarrag2050/OperationSystem
✅ Access Level: Owner/Admin
✅ Branch Protection: Optional (recommended for production)
```

---

## 🔧 Initial Git Setup {#git-setup}

### Step 1: Navigate to Project Directory

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation
```

### Step 2: Check Current Git Status

```bash
# Check if git is initialized
git status

# If not initialized:
git init

# Check current remote
git remote -v

# If remote doesn't exist or is wrong:
git remote add origin https://github.com/mfarrag2050/OperationSystem.git

# If remote exists but is wrong:
git remote set-url origin https://github.com/mfarrag2050/OperationSystem.git
```

### Step 3: Verify GitHub Connection

```bash
# Test connection
git ls-remote origin

# Should list remote branches
```

---

## 📁 Repository Structure {#structure}

### Recommended GitHub Structure

```
OperationSystem/
├── README.md                              # Main project overview
├── LICENSE                                # MIT or GPL license
├── .gitignore                            # WordPress standard
├── CHANGELOG.md                          # Version history
│
├── docs/                                 # 📚 Documentation
│   ├── PROJECT_SYSTEM_DOCUMENTATION.md   # Complete system docs
│   ├── DEVELOPMENT_METHODOLOGY.md        # AI workflow & methodology
│   ├── DEPLOYMENT_GUIDE.md               # This file
│   ├── API_REFERENCE.md                  # API documentation
│   └── TROUBLESHOOTING.md                # Common issues
│
├── plugins/                              # 🔌 WordPress Plugins
│   ├── aqop-core/                       # Core platform plugin
│   │   ├── aqop-core.php
│   │   ├── README.md
│   │   ├── CHANGELOG.md
│   │   ├── includes/
│   │   ├── admin/
│   │   └── assets/
│   │
│   └── aqop-leads/                      # Leads module plugin
│       ├── aqop-leads.php
│       ├── README.md
│       ├── CHANGELOG.md
│       ├── includes/
│       ├── admin/
│       ├── public/
│       └── api/
│
├── database/                             # 🗄️ Database
│   ├── schema.sql                       # Complete DB schema
│   ├── seed-data.sql                    # Sample data (optional)
│   └── migrations/                      # Version migrations
│
├── tests/                               # 🧪 Testing (future)
│   ├── unit/
│   ├── integration/
│   └── phpunit.xml
│
└── .github/                             # ⚙️ GitHub Config
    ├── workflows/                       # CI/CD (future)
    │   └── deploy.yml
    └── ISSUE_TEMPLATE/
        ├── bug_report.md
        └── feature_request.md
```

---

## 🚀 Step-by-Step Deployment {#deployment}

### Phase 1: Prepare Local Repository

#### 1.1 Clean and Organize Files

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Remove unnecessary files
rm -rf .DS_Store
rm -rf */._*
rm -rf node_modules/
rm -rf vendor/

# List what will be committed
git status
```

#### 1.2 Create .gitignore

```bash
# Create .gitignore file
cat > .gitignore << 'EOF'
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-db/
wp-content/advanced-cache.php
wp-content/wp-cache-config.php
wp-content/upgrade/

# Plugin specific
*.log
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Environment
.env
.env.local

# Dependencies
node_modules/
vendor/

# Compiled files
*.min.js.map
*.min.css.map

# Temporary
tmp/
temp/
*.tmp
EOF

git add .gitignore
```

#### 1.3 Create README.md

```bash
cat > README.md << 'EOF'
# 🏢 AQOP Operation Platform

> Complete lead management system built with WordPress, replacing expensive SaaS solutions.

[![Version](https://img.shields.io/badge/version-1.0.10-blue.svg)](https://github.com/mfarrag2050/OperationSystem)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

---

## 🎯 Overview

The **AQOP Operation Platform** is a comprehensive, modular WordPress-based operations hub that centralizes lead intake, qualification, collaboration, and reporting for Arabic-speaking teams.

### Key Features

- ✅ **Analytics Dashboard** with KPIs and interactive charts
- ✅ **Complete Lead Management** (CRUD operations)
- ✅ **Advanced Filtering** (6 filter types + search)
- ✅ **Bulk Operations** (delete, status change, export)
- ✅ **REST API** (8 endpoints for integrations)
- ✅ **Public Forms** via shortcode `[aqop_lead_form]`
- ✅ **CSV Import/Export** for data migration
- ✅ **Airtable Integration** for data sync
- ✅ **Telegram Notifications** for instant alerts
- ✅ **Event Logging** for audit trail
- ✅ **Role-Based Access** (Admin, Manager, Supervisor, Agent)

### Business Impact

- 💰 **$4,000+/year** savings (replacing Airtable)
- 👥 **Unlimited users** (vs paid SaaS tiers)
- ⚡ **Built in 4 hours** using AI-assisted development
- 📊 **15,000+ lines** of production-ready code
- 🔒 **Enterprise-grade security** built-in

---

## 📦 Installation

### Quick Start

1. **Clone Repository**
   ```bash
   git clone https://github.com/mfarrag2050/OperationSystem.git
   cd OperationSystem
   ```

2. **Copy Plugins to WordPress**
   ```bash
   cp -r plugins/aqop-core /path/to/wordpress/wp-content/plugins/
   cp -r plugins/aqop-leads /path/to/wordpress/wp-content/plugins/
   ```

3. **Activate Plugins** (in order)
   - Navigate to WordPress Admin → Plugins
   - Activate: "Operation Platform Core"
   - Activate: "Operation Platform - Leads Module"

4. **Configure Settings**
   - Go to: مركز العمليات → Settings
   - Add Airtable credentials (optional)
   - Add Telegram bot token (optional)
   - Save changes

5. **Test Installation**
   - Visit: مركز العمليات → Dashboard
   - Create a test lead
   - Verify everything works

### System Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- 128MB+ PHP memory limit
- mod_rewrite enabled

---

## 📚 Documentation

- **[Complete System Documentation](docs/PROJECT_SYSTEM_DOCUMENTATION.md)** - Full technical reference
- **[Development Methodology](docs/DEVELOPMENT_METHODOLOGY.md)** - AI-assisted workflow
- **[API Reference](docs/API_REFERENCE.md)** - REST API endpoints
- **[Deployment Guide](docs/DEPLOYMENT_GUIDE.md)** - Production deployment
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues

---

## 🚀 Quick Links

- **Dashboard:** `wp-admin/admin.php?page=aqop-leads-dashboard`
- **All Leads:** `wp-admin/admin.php?page=aqop-leads`
- **Settings:** `wp-admin/admin.php?page=aqop-settings`
- **API Docs:** `wp-admin/admin.php?page=aqop-leads-api`

---

## 🤝 Contributing

This is a private project. For internal contributions:

1. Create feature branch
2. Make changes
3. Test thoroughly
4. Submit for review
5. Merge to main

---

## 📄 License

GPL-2.0 License - See [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Developer

**Muhammed Derviş (محمد)**  
Built with ❤️ using AI-assisted development (Claude + Cursor)

---

## 🙏 Acknowledgments

- **WordPress** - Platform foundation
- **Claude (Anthropic)** - Strategic planning & documentation
- **Cursor AI** - Code implementation
- **Chart.js** - Analytics visualizations

---

**Last Updated:** November 17, 2025  
**Version:** 1.0.10  
**Status:** Production Ready 🚀
EOF

git add README.md
```

#### 1.4 Create CHANGELOG.md

```bash
cat > CHANGELOG.md << 'EOF'
# Changelog

All notable changes to the AQOP Operation Platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.10] - 2025-11-17

### Added - Week 4 Complete (100%)

#### Analytics Dashboard
- 4 KPI cards (Total Leads, This Month, Converted, Conversion Rate)
- 3 interactive charts (Timeline, Status Distribution, Top Sources)
- Chart.js 4.4.0 integration
- Activity feed showing last 10 events
- 6 quick action shortcuts
- Month-over-month comparison indicators

#### Settings Management
- 4-tab settings interface (Sources, Statuses, Integrations, Notifications)
- Lead sources management (add/activate/deactivate)
- Airtable integration configuration
- Telegram bot configuration
- Email notification preferences
- Tab switching with hash navigation

### Changed
- Dashboard now default page in Control Center menu
- Renamed "Leads" menu to "All Leads"
- Menu structure reorganized for better UX

### Technical
- Conditional asset loading (Chart.js only on dashboard)
- Event logging for all settings changes
- Responsive design for all new components

---

## [1.0.8] - 2025-11-17

### Added - Week 3 (85%)

#### Import/Export System
- CSV import with duplicate detection
- CSV export with filters preserved
- Template download for consistent imports
- Batch processing for large files
- UTF-8 encoding support for Arabic text

#### Public Lead Form
- Shortcode `[aqop_lead_form]` implementation
- AJAX form submission
- Rate limiting (5 submissions/hour per IP)
- Custom fields support
- Redirect after submission
- Success/error messages

#### Notifications
- Email notifications for new leads
- Telegram integration for instant alerts
- Configurable notification settings

---

## [1.0.6] - 2025-11-17

### Added - Week 3 (85%)

#### REST API
- 8 RESTful endpoints
- Authentication via WordPress cookies/app passwords
- Pagination support
- Search and filtering in API
- Rate limiting
- API documentation page in admin

**Endpoints:**
- `GET /wp-json/aqop/v1/leads` - List leads
- `GET /wp-json/aqop/v1/leads/{id}` - Get single lead
- `POST /wp-json/aqop/v1/leads` - Create lead
- `PUT /wp-json/aqop/v1/leads/{id}` - Update lead
- `DELETE /wp-json/aqop/v1/leads/{id}` - Delete lead
- `GET /wp-json/aqop/v1/leads/statuses` - Get statuses
- `GET /wp-json/aqop/v1/leads/countries` - Get countries
- `GET /wp-json/aqop/v1/leads/sources` - Get sources

---

## [1.0.5] - 2025-11-17

### Added - Week 2 (75%)

#### Advanced Filtering
- 6 filter types (Status, Priority, Country, Source, Campaign, Date Range)
- Multi-field search (Name, Email, Phone, WhatsApp)
- Sortable columns (click to sort)
- Pagination with per-page control
- Filter persistence across page loads

#### Bulk Operations
- Bulk delete with confirmation
- Bulk status change
- Bulk export to CSV
- Select all functionality
- Visual feedback for operations

#### UI Enhancements
- Professional filter interface
- Inline statistics display
- Responsive design improvements
- Loading states for async operations

---

## [1.0.0] - 2025-11-15

### Added - Week 1 (65%)

#### Core Features
- Complete CRUD operations for leads
- Lead detail page with comprehensive view
- Notes system (add/edit/delete with AJAX)
- Contact information management
- Lead assignment to users
- Status management
- Priority levels (urgent/high/medium/low)

#### Database Schema
- `wp_aq_leads` - Main leads table
- `wp_aq_leads_notes` - Notes with timestamps
- `wp_aq_leads_status` - Status definitions
- `wp_aq_leads_sources` - Lead sources
- `wp_aq_leads_campaigns` - Campaign tracking
- `wp_aq_dim_countries` - Country reference
- `wp_aq_events_log` - Event logging

#### Integration
- Airtable sync (bidirectional)
- Event logging system
- Control Center integration
- Role-based permissions

#### Security
- Nonce protection on all forms
- Capability checks
- Input sanitization
- SQL injection prevention
- XSS prevention
- CSRF protection

---

## Project Statistics

- **Total Development Time:** 4 hours
- **Lines of Code:** 15,000+
- **Files Created:** 35+
- **Features Implemented:** 20+
- **Cost Savings:** $4,000+/year

---

[1.0.10]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.10
[1.0.8]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.8
[1.0.6]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.6
[1.0.5]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.5
[1.0.0]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.0
EOF

git add CHANGELOG.md
```

---

### Phase 2: Organize Documentation

#### 2.1 Create docs/ Directory

```bash
# Create documentation directory
mkdir -p docs

# Move/copy documentation files
cp /Users/mfarrag/Documents/Operation/aql-leads/mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md docs/
cp /Users/mfarrag/Documents/Operation/aql-leads/mnt/project/DEVELOPMENT_METHODOLOGY.md docs/

# This deployment guide will also go there
# (You'll create it after this guide is complete)
```

#### 2.2 Create Additional Documentation

Create `docs/API_REFERENCE.md`:

```bash
cat > docs/API_REFERENCE.md << 'EOF'
# 📡 AQOP API Reference

**Base URL:** `https://yoursite.com/wp-json/aqop/v1`  
**Version:** 1.0  
**Authentication:** WordPress Cookies, Application Passwords, or OAuth

---

## Authentication

### Cookie Authentication
For logged-in WordPress users, authentication is automatic.

### Application Passwords
1. Go to: Users → Profile → Application Passwords
2. Generate new password
3. Use in API calls:

```bash
curl -u username:app_password https://yoursite.com/wp-json/aqop/v1/leads
```

---

## Endpoints

### 1. List Leads

**GET** `/leads`

**Parameters:**
- `page` (int) - Page number (default: 1)
- `per_page` (int) - Items per page (default: 20, max: 200)
- `search` (string) - Search in name/email/phone
- `status` (string) - Filter by status code
- `priority` (string) - Filter by priority
- `country` (int) - Filter by country ID
- `source` (int) - Filter by source ID
- `orderby` (string) - Sort column
- `order` (string) - ASC or DESC

**Example:**
```bash
curl -u user:pass "https://site.com/wp-json/aqop/v1/leads?status=pending&per_page=10"
```

**Response:**
```json
{
  "leads": [
    {
      "id": 123,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "phone": "+971501234567",
      "status": "pending",
      "created_at": "2025-11-17 10:30:00"
    }
  ],
  "total": 150,
  "pages": 15,
  "page": 1
}
```

### 2. Get Single Lead

**GET** `/leads/{id}`

**Example:**
```bash
curl -u user:pass https://site.com/wp-json/aqop/v1/leads/123
```

**Response:**
```json
{
  "lead": {
    "id": 123,
    "name": "Ahmed Ali",
    "email": "ahmed@example.com",
    "phone": "+971501234567",
    "whatsapp": "+971501234567",
    "country_id": 1,
    "source_id": 2,
    "status": "pending",
    "priority": "high",
    "assigned_to": 5,
    "created_at": "2025-11-17 10:30:00",
    "updated_at": "2025-11-17 11:00:00"
  },
  "notes": [
    {
      "id": 45,
      "note": "Called customer, interested",
      "user_id": 5,
      "created_at": "2025-11-17 11:00:00"
    }
  ]
}
```

### 3. Create Lead

**POST** `/leads`

**Body:**
```json
{
  "name": "Sara Ahmed",
  "email": "sara@example.com",
  "phone": "+971509876543",
  "whatsapp": "+971509876543",
  "country_id": 1,
  "source_id": 3,
  "priority": "medium",
  "note": "Inbound from website form"
}
```

**Response:**
```json
{
  "message": "Lead created successfully",
  "lead": {
    "id": 124,
    "name": "Sara Ahmed",
    "email": "sara@example.com",
    "created_at": "2025-11-17 12:00:00"
  }
}
```

### 4. Update Lead

**PUT** `/leads/{id}`

**Body:**
```json
{
  "status": "contacted",
  "priority": "high",
  "assigned_to": 8
}
```

**Response:**
```json
{
  "message": "Lead updated successfully",
  "lead": {
    "id": 124,
    "status": "contacted",
    "updated_at": "2025-11-17 12:30:00"
  }
}
```

### 5. Delete Lead

**DELETE** `/leads/{id}`

**Response:**
```json
{
  "message": "Lead deleted successfully",
  "id": 124
}
```

### 6-8. Supporting Data

**GET** `/leads/statuses` - Get all status definitions
**GET** `/leads/countries` - Get all countries  
**GET** `/leads/sources` - Get all lead sources

---

## Error Responses

### 400 Bad Request
```json
{
  "code": "invalid_params",
  "message": "Invalid parameter: email",
  "data": {
    "status": 400
  }
}
```

### 401 Unauthorized
```json
{
  "code": "rest_forbidden",
  "message": "You are not authenticated",
  "data": {
    "status": 401
  }
}
```

### 403 Forbidden
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission",
  "data": {
    "status": 403
  }
}
```

### 404 Not Found
```json
{
  "code": "not_found",
  "message": "Lead not found",
  "data": {
    "status": 404
  }
}
```

---

## Rate Limiting

- API requests are rate-limited per user
- Recommended: Max 100 requests per hour
- Headers include: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

---

## Best Practices

1. ✅ Use pagination for large datasets
2. ✅ Cache responses when possible
3. ✅ Use Application Passwords (more secure than cookies)
4. ✅ Handle errors gracefully
5. ✅ Respect rate limits
6. ✅ Use HTTPS always

---

For more details, see [Complete System Documentation](PROJECT_SYSTEM_DOCUMENTATION.md).
EOF

git add docs/API_REFERENCE.md
```

---

### Phase 3: Commit All Changes

#### 3.1 Stage All Files

```bash
# Check status
git status

# Add all files
git add .

# Or add selectively:
git add README.md
git add CHANGELOG.md
git add .gitignore
git add docs/
git add wp-content/plugins/aqop-core/
git add wp-content/plugins/aqop-leads/
```

#### 3.2 Create Initial Commit

```bash
git commit -m "feat: Initial commit - AQOP Operation Platform v1.0.10

Complete lead management system with:

Core Features:
- Analytics Dashboard with KPIs and Charts
- Complete Lead CRUD operations
- Advanced Filtering (6 types + search)
- Bulk Operations (delete, status, export)
- REST API (8 endpoints)
- Public Lead Forms (shortcode)
- CSV Import/Export
- Settings Management Interface

Integrations:
- Airtable sync (bidirectional)
- Telegram notifications
- Email notifications
- Event logging system

Security:
- Nonce protection on all forms
- SQL injection prevention
- XSS prevention
- CSRF protection
- Role-based access control
- Rate limiting on public endpoints

Documentation:
- Complete system documentation
- Development methodology
- API reference
- Deployment guide
- Changelog

Project Statistics:
- Development Time: 4 hours
- Lines of Code: 15,000+
- Files: 35+
- Features: 20+
- Cost Savings: $4,000+/year

Plugins:
- aqop-core v1.0.10
- aqop-leads v1.0.10

Status: Production Ready 🚀"
```

---

### Phase 4: Push to GitHub

#### 4.1 Set Default Branch

```bash
# Ensure you're on main branch
git branch -M main
```

#### 4.2 Push to GitHub

```bash
# First push (with upstream tracking)
git push -u origin main

# Enter credentials if prompted
# For HTTPS: username + personal access token
# For SSH: passphrase (if set)
```

#### 4.3 Verify Upload

```bash
# Check remote status
git remote -v

# Check what was pushed
git log --oneline -5

# Visit GitHub to verify:
# https://github.com/mfarrag2050/OperationSystem
```

---

### Phase 5: Create GitHub Release (Optional but Recommended)

#### Via GitHub Web Interface:

1. **Navigate to Repository**
   ```
   https://github.com/mfarrag2050/OperationSystem
   ```

2. **Create New Release**
   - Click "Releases" → "Create a new release"
   - Tag version: `v1.0.10`
   - Release title: `AQOP v1.0.10 - Complete Lead Management System`
   - Description: Copy from CHANGELOG.md

3. **Attach Assets (Optional)**
   - `aqop-core-v1.0.10.zip`
   - `aqop-leads-v1.0.10.zip`

---

## 📝 Post-Deployment {#post-deployment}

### Update Documentation

#### Add GitHub Section to PROJECT_SYSTEM_DOCUMENTATION.md

Add this section after "Change Log":

```markdown
## 🐙 GitHub Repository

### Repository Information
- **URL:** https://github.com/mfarrag2050/OperationSystem
- **Visibility:** Private
- **Default Branch:** main
- **License:** GPL-2.0

### Repository Structure
```
OperationSystem/
├── README.md                    # Project overview
├── CHANGELOG.md                 # Version history
├── LICENSE                      # GPL-2.0 license
├── .gitignore                  # Git exclusions
├── docs/                       # Documentation
│   ├── PROJECT_SYSTEM_DOCUMENTATION.md
│   ├── DEVELOPMENT_METHODOLOGY.md
│   ├── API_REFERENCE.md
│   └── DEPLOYMENT_GUIDE.md
└── plugins/                    # WordPress plugins
    ├── aqop-core/
    └── aqop-leads/
```

### Cloning Repository

For team members or deployment:

```bash
# Clone repository
git clone https://github.com/mfarrag2050/OperationSystem.git

# Navigate to directory
cd OperationSystem

# Copy plugins to WordPress
cp -r plugins/aqop-core /path/to/wordpress/wp-content/plugins/
cp -r plugins/aqop-leads /path/to/wordpress/wp-content/plugins/
```

### Keeping Updated

```bash
# Pull latest changes
git pull origin main

# View recent changes
git log --oneline -10

# Check what changed in files
git diff HEAD~1 HEAD
```

### Contributing

1. Create feature branch
   ```bash
   git checkout -b feature/new-feature
   ```

2. Make changes and commit
   ```bash
   git add .
   git commit -m "feat: Add new feature"
   ```

3. Push to GitHub
   ```bash
   git push origin feature/new-feature
   ```

4. Create Pull Request on GitHub

5. After review, merge to main

### GitHub Best Practices

1. ✅ Meaningful commit messages
2. ✅ Regular commits (not one giant commit)
3. ✅ Keep documentation updated
4. ✅ Use .gitignore properly
5. ✅ Tag releases with semantic versioning
6. ✅ Write descriptive PR descriptions
7. ✅ Review code before merging

---

## Deployment Verification Checklist

After pushing to GitHub:

```
□ Repository accessible at GitHub URL
□ README.md displays correctly
□ All documentation files present in docs/
□ Plugins folders complete (aqop-core, aqop-leads)
□ .gitignore excludes sensitive files
□ CHANGELOG.md up to date
□ No wp-config.php or credentials committed
□ File structure organized
□ License file included
□ All commit messages clear
```

---

**Last Updated:** November 17, 2025  
**Next Update:** When new features added
```

### Update Both Documentation Files

```bash
# Navigate to docs directory
cd docs

# Open and edit PROJECT_SYSTEM_DOCUMENTATION.md
# Add the GitHub Repository section at the end (before Change Log)

# Open and edit DEVELOPMENT_METHODOLOGY.md
# Add Git workflow section if not already present

# Commit updates
cd ..
git add docs/
git commit -m "docs: Add GitHub repository information to documentation"
git push origin main
```

---

## 🎯 Best Practices {#best-practices}

### Commit Messages

```bash
# Format: <type>: <description>

# Good examples:
git commit -m "feat: Add WhatsApp integration"
git commit -m "fix: Resolve dashboard loading issue"
git commit -m "docs: Update API documentation"
git commit -m "style: Format code according to WordPress standards"
git commit -m "refactor: Optimize database queries"
git commit -m "perf: Add caching for dashboard stats"
git commit -m "test: Add unit tests for lead creation"
git commit -m "chore: Update dependencies"

# Bad examples:
git commit -m "update"
git commit -m "fixed stuff"
git commit -m "changes"
```

### Branch Strategy

```bash
# Main branch (production)
main

# Feature branches (new features)
feature/dashboard-improvements
feature/whatsapp-integration

# Fix branches (bug fixes)
fix/dashboard-loading
fix/email-notification

# Hotfix branches (urgent production fixes)
hotfix/security-patch
hotfix/critical-bug

# Create and switch to branch
git checkout -b feature/new-feature

# Push branch to GitHub
git push origin feature/new-feature

# Merge back to main
git checkout main
git merge feature/new-feature
git push origin main

# Delete branch after merge
git branch -d feature/new-feature
git push origin --delete feature/new-feature
```

### Release Management

```bash
# When ready for new release:

# 1. Update version in plugin files
# aqop-core/aqop-core.php: Version: 1.0.11
# aqop-leads/aqop-leads.php: Version: 1.0.11

# 2. Update CHANGELOG.md

# 3. Commit version bump
git add .
git commit -m "chore: Bump version to 1.0.11"

# 4. Create git tag
git tag -a v1.0.11 -m "Release version 1.0.11"

# 5. Push with tags
git push origin main --tags

# 6. Create GitHub Release
# Go to GitHub → Releases → Create Release
```

---

## 🔧 Troubleshooting {#troubleshooting}

### Issue: "Repository not found"

```bash
# Check remote URL
git remote -v

# Fix URL if wrong
git remote set-url origin https://github.com/mfarrag2050/OperationSystem.git

# Test connection
git ls-remote origin
```

### Issue: "Permission denied"

```bash
# For HTTPS: Use Personal Access Token instead of password
# Generate at: GitHub → Settings → Developer Settings → Personal Access Tokens

# For SSH: Check SSH key
ssh -T git@github.com

# Add SSH key if needed
cat ~/.ssh/id_rsa.pub
# Copy and add to GitHub → Settings → SSH Keys
```

### Issue: "Failed to push - rejected"

```bash
# Pull first (someone else pushed changes)
git pull origin main

# Resolve conflicts if any
# Then push
git push origin main

# Force push (DANGEROUS - use only if sure)
git push -f origin main
```

### Issue: "Large files rejected"

```bash
# GitHub max file size: 100MB
# Check large files
git ls-files | xargs du -h | sort -h | tail -20

# Remove large file from git history
git rm --cached large-file.zip
git commit -m "Remove large file"

# Add to .gitignore
echo "*.zip" >> .gitignore
git add .gitignore
git commit -m "Update .gitignore"
```

### Issue: "Committed sensitive data"

```bash
# IMPORTANT: Never commit passwords, API keys, etc.

# If committed accidentally:
# 1. Remove from file
# 2. Commit removal
git add wp-config.php
git commit -m "Remove sensitive data"

# 3. Remove from history (careful!)
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch wp-config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 4. Force push (if repository is private)
git push -f origin main

# 5. Rotate credentials immediately
# Change all exposed passwords/keys
```

---

## 📋 Quick Reference

### Common Commands

```bash
# Clone repository
git clone https://github.com/mfarrag2050/OperationSystem.git

# Check status
git status

# Add files
git add .
git add specific-file.php

# Commit
git commit -m "message"

# Push
git push origin main

# Pull
git pull origin main

# View history
git log --oneline -10

# Create branch
git checkout -b branch-name

# Switch branch
git checkout main

# Merge branch
git merge branch-name

# Tag version
git tag -a v1.0.10 -m "Version 1.0.10"

# Push tags
git push --tags

# View remotes
git remote -v

# View branches
git branch -a
```

---

## 🎉 Completion Checklist

```
Final Verification:

□ Repository created on GitHub
□ Local git initialized
□ Remote origin configured
□ All files committed
□ Pushed to GitHub successfully
□ README.md displays correctly
□ Documentation in docs/ folder
□ .gitignore excludes sensitive files
□ CHANGELOG.md complete
□ No sensitive data committed
□ Repository accessible to team
□ Documentation includes GitHub info
□ Release created (optional)
```

---

**Congratulations! Your AQOP Operation Platform is now on GitHub!** 🎊

**Repository URL:** https://github.com/mfarrag2050/OperationSystem

---

**File:** `DEPLOYMENT_GUIDE.md`  
**Location:** `docs/`  
**Version:** 1.0.0  
**Date:** November 17, 2025  
**Status:** Complete
