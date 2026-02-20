# 🚀 GitHub Deployment & Maintenance Guide

**دليل النشر والصيانة على GitHub - AQOP Operation Platform**

**Version:** 1.0.0  
**Date:** November 17, 2025  
**Author:** Muhammed Derviş  
**Status:** Complete & Production Ready

---

## 📋 Table of Contents

1. [Initial GitHub Setup](#initial-setup)
2. [Step-by-Step Deployment](#deployment)
3. [Documentation Update Workflow](#documentation)
4. [Cursor Prompts for GitHub Operations](#cursor-prompts)
5. [Automated Documentation Updates](#automation)
6. [Git Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)
8. [Quick Reference](#quick-reference)

---

## 🎯 Initial GitHub Setup {#initial-setup}

### Prerequisites

```bash
# Verify Git installation
git --version
# Should show: git version 2.x.x

# Configure Git (first time only)
git config --global user.name "Muhammed Farrag"
git config --global user.email "your-email@example.com"

# Verify configuration
git config --list | grep user
```

### GitHub Personal Access Token

**Create Token (First Time):**

1. Go to: https://github.com/settings/tokens
2. Click: **"Generate new token (classic)"**
3. Settings:
   - **Note:** `AQOP-Deployment`
   - **Expiration:** `90 days` or `No expiration`
   - **Scopes:** ✅ `repo` (full control of private repositories)
4. Click: **"Generate token"**
5. **⚠️ Copy token immediately** (shown only once!)
6. **🔒 Store securely** (never share publicly!)

**Security Rules:**
```
❌ NEVER share token in chat/email
❌ NEVER commit token to repository
❌ NEVER write token in plain text files
✅ Store in password manager
✅ Use only when needed
✅ Revoke immediately if exposed
```

---

## 📝 Step-by-Step Deployment {#deployment}

### Phase 1: Prepare Local Repository

#### Step 1: Navigate to Project

```bash
# Open Mac Terminal (Cmd+Space → "Terminal")
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Verify location
pwd
# Should show: /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation
```

#### Step 2: Initialize Git (if needed)

```bash
# Check if Git is initialized
git status

# If "not a git repository" error:
git init
git branch -M main
```

#### Step 3: Add GitHub Remote

```bash
# Check existing remote
git remote -v

# If no remote exists:
git remote add origin https://github.com/mfarrag2050/OperationSystem.git

# If remote exists but wrong:
git remote set-url origin https://github.com/mfarrag2050/OperationSystem.git

# Verify
git remote -v
# Should show:
# origin  https://github.com/mfarrag2050/OperationSystem.git (fetch)
# origin  https://github.com/mfarrag2050/OperationSystem.git (push)
```

---

### Phase 2: Create Essential Files

#### Step 4: Create .gitignore

```bash
cat > .gitignore << 'EOF'
# WordPress Core
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-db/
wp-content/advanced-cache.php
wp-content/wp-cache-config.php
wp-content/upgrade/

# Logs
*.log
debug.log
error_log

# macOS
.DS_Store
._*
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo
*.sublime-*

# Environment
.env
.env.local
.env.production
.env.staging

# Dependencies
node_modules/
vendor/
bower_components/

# Compiled
*.min.js.map
*.min.css.map
dist/
build/

# Temporary
tmp/
temp/
*.tmp
*~
*.bak

# Archives (optional - remove if you want to include zips)
*.zip
*.tar
*.tar.gz
*.rar

# Security
*.pem
*.key
*.cert
id_rsa*
EOF

# Verify creation
ls -la | grep .gitignore
```

#### Step 5: Create README.md

```bash
cat > README.md << 'EOF'
# 🏢 AQOP Operation Platform

> نظام إدارة العملاء المحتملين المتكامل - بديل احترافي لبرامج الـ SaaS باهظة الثمن

[![Version](https://img.shields.io/badge/version-1.0.10-blue.svg)](https://github.com/mfarrag2050/OperationSystem)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

---

## 🎯 نظرة عامة

**منصة العمليات AQOP** هي نظام شامل ومتكامل مبني على WordPress لإدارة العملاء المحتملين والتواصل والتحليلات.

### ✨ المميزات الرئيسية

- ✅ **لوحة تحكم تحليلية** مع مؤشرات أداء ورسوم بيانية تفاعلية
- ✅ **إدارة كاملة للعملاء المحتملين** (إنشاء، عرض، تعديل، حذف)
- ✅ **فلاتر متقدمة** (6 أنواع + بحث متعدد الحقول)
- ✅ **عمليات جماعية** (حذف، تغيير الحالة، تصدير)
- ✅ **واجهة برمجية REST API** (8 نقاط وصول)
- ✅ **نماذج عامة** عبر shortcode
- ✅ **استيراد/تصدير CSV** لنقل البيانات
- ✅ **تكامل مع Airtable** لمزامنة البيانات
- ✅ **إشعارات Telegram** الفورية
- ✅ **سجل الأحداث** الكامل
- ✅ **صلاحيات حسب الدور** (مدير، مشرف، وكيل)

### 💰 التأثير على الأعمال

- 💰 **توفير +$4,000 سنوياً** (بديل لـ Airtable)
- 👥 **مستخدمين غير محدودين** (مقابل الباقات المدفوعة)
- ⚡ **تم البناء في 4 ساعات** باستخدام الذكاء الاصطناعي
- 📊 **+15,000 سطر برمجي** جاهز للإنتاج
- 🔒 **أمان على مستوى المؤسسات**

---

## 📦 التثبيت السريع

### 1. استنساخ المستودع
```bash
git clone https://github.com/mfarrag2050/OperationSystem.git
cd OperationSystem
```

### 2. نسخ الإضافات إلى WordPress
```bash
cp -r wp-content/plugins/aqop-core /path/to/wordpress/wp-content/plugins/
cp -r wp-content/plugins/aqop-leads /path/to/wordpress/wp-content/plugins/
```

### 3. تفعيل الإضافات (بالترتيب)
- انتقل إلى: لوحة تحكم WordPress → إضافات
- فعّل: "Operation Platform Core"
- فعّل: "Operation Platform - Leads Module"

### 4. الإعدادات
- اذهب إلى: مركز العمليات → الإعدادات
- أضف بيانات Airtable (اختياري)
- أضف توكن Telegram (اختياري)
- احفظ التغييرات

---

## 📚 التوثيق

- **[الوثائق الكاملة](docs/PROJECT_SYSTEM_DOCUMENTATION.md)** - مرجع تقني شامل
- **[منهجية التطوير](docs/DEVELOPMENT_METHODOLOGY.md)** - سير العمل بالذكاء الاصطناعي
- **[دليل النشر على GitHub](docs/GITHUB_DEPLOYMENT.md)** - دليل الرفع والصيانة

---

## 🚀 روابط سريعة

- **لوحة التحكم:** `wp-admin/admin.php?page=aqop-leads-dashboard`
- **جميع العملاء:** `wp-admin/admin.php?page=aqop-leads`
- **الإعدادات:** `wp-admin/admin.php?page=aqop-settings`
- **API Docs:** `wp-admin/admin.php?page=aqop-leads-api`

---

## 📊 إحصائيات المشروع

- **وقت التطوير:** 4 ساعات
- **أسطر البرمجة:** 15,000+
- **الملفات المُنشأة:** 35+
- **المميزات:** 20+
- **التوفير السنوي:** $4,000+

---

## 🔄 التحديثات

### آخر التحديثات (v1.0.10)
- ✅ لوحة تحكم تحليلية مع رسوم بيانية
- ✅ واجهة إدارة الإعدادات
- ✅ تكامل Chart.js
- ✅ نظام التوثيق الشامل

راجع [CHANGELOG.md](CHANGELOG.md) للتفاصيل الكاملة.

---

## 📄 الترخيص

GPL-2.0 License - انظر ملف [LICENSE](LICENSE) للتفاصيل

---

## 👨‍💻 المطور

**محمد درويش (Muhammed Derviş)**  
مطور متخصص في بناء الأنظمة المتكاملة

---

**آخر تحديث:** 20 فبراير 2026  
**الإصدار:** 1.0.11  
**الحالة:** يعمل بكفاءة في الإنتاج 🚀
EOF

# Verify
ls -la | grep README
```

#### Step 6: Create CHANGELOG.md

```bash
cat > CHANGELOG.md << 'EOF'
# سجل التغييرات - AQOP Operation Platform

جميع التغييرات البارزة سيتم توثيقها في هذا الملف.

الصيغة مبنية على [Keep a Changelog](https://keepachangelog.com/)  
والإصدارات تتبع [Semantic Versioning](https://semver.org/)

---

## [1.0.10] - 2025-11-17

### أُضيف

#### لوحة التحكم التحليلية
- 4 بطاقات مؤشرات أداء (Total, This Month, Converted, Conversion Rate)
- 3 رسوم بيانية تفاعلية (Timeline, Status Distribution, Top Sources)
- تكامل Chart.js 4.4.0
- خلاصة الأنشطة (آخر 10 أحداث)
- 6 اختصارات سريعة

#### إدارة الإعدادات
- واجهة 4 تبويبات (Sources, Statuses, Integrations, Notifications)
- إدارة مصادر العملاء
- تكوين Airtable
- تكوين Telegram
- إعدادات البريد الإلكتروني

### تم التغيير
- Dashboard الآن الصفحة الافتراضية في Control Center
- إعادة تسمية "Leads" إلى "All Leads"

---

## [1.0.8] - 2025-11-17

### أُضيف

#### نظام الاستيراد/التصدير
- استيراد CSV مع كشف التكرار
- تصدير CSV
- قوالب الاستيراد
- دعم UTF-8 للعربية

#### النماذج العامة
- Shortcode نموذج العملاء
- إرسال AJAX
- حد المعدل (5/ساعة)

#### الإشعارات
- إشعارات البريد الإلكتروني
- تكامل Telegram
- إعدادات قابلة للتخصيص

---

## [1.0.6] - 2025-11-17

### أُضيف

#### REST API
- 8 نقاط وصول RESTful
- المصادقة (WordPress cookies/app passwords)
- الترقيم والفلترة
- صفحة توثيق API

---

## [1.0.5] - 2025-11-17

### أُضيف

#### الفلترة المتقدمة
- 6 أنواع فلاتر
- بحث متعدد الحقول
- أعمدة قابلة للترتيب
- الترقيم

#### العمليات الجماعية
- حذف جماعي
- تغيير حالة جماعي
- تصدير جماعي

---

## [1.0.0] - 2025-11-15

### الإصدار الأول

#### المميزات الأساسية
- عمليات CRUD كاملة
- صفحة تفاصيل العميل
- نظام الملاحظات
- إدارة الحالات
- مستويات الأولوية

#### قاعدة البيانات
- جدول العملاء الرئيسي
- جدول الملاحظات
- تعريفات الحالات
- مصادر العملاء
- سجل الأحداث

#### التكاملات
- مزامنة Airtable
- نظام تسجيل الأحداث
- تكامل Control Center

#### الأمان
- حماية Nonce
- فحص الصلاحيات
- تنظيف المدخلات
- منع SQL injection
- منع XSS

---

## إحصائيات المشروع

- **وقت التطوير:** 4 ساعات
- **أسطر البرمجة:** 15,000+
- **الملفات:** 35+
- **المميزات:** 20+
- **التوفير السنوي:** $4,000+

---

[1.0.10]: https://github.com/mfarrag2050/OperationSystem/compare/v1.0.8...v1.0.10
[1.0.8]: https://github.com/mfarrag2050/OperationSystem/compare/v1.0.6...v1.0.8
[1.0.6]: https://github.com/mfarrag2050/OperationSystem/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/mfarrag2050/OperationSystem/compare/v1.0.0...v1.0.5
[1.0.0]: https://github.com/mfarrag2050/OperationSystem/releases/tag/v1.0.0
EOF

# Verify
ls -la | grep CHANGELOG
```

---

### Phase 3: Organize Documentation

#### Step 7: Create docs Directory and Copy Documentation

```bash
# Create docs folder
mkdir -p docs

# Copy documentation files
cp ../mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md docs/
cp ../mnt/project/DEVELOPMENT_METHODOLOGY.md docs/
cp ../mnt/project/DEPLOYMENT_GUIDE.md docs/

# Verify
ls -la docs/

# Should show:
# PROJECT_SYSTEM_DOCUMENTATION.md
# DEVELOPMENT_METHODOLOGY.md
# DEPLOYMENT_GUIDE.md
```

---

### Phase 4: Commit and Push

#### Step 8: Review Files Before Commit

```bash
# Check what will be committed
git status

# Review file list carefully
# Ensure NO sensitive data:
# ❌ wp-config.php
# ❌ .env files
# ❌ API keys/passwords
# ❌ database dumps
```

#### Step 9: Add Files to Git

```bash
# Add all files
git add .

# Or add selectively:
# git add README.md CHANGELOG.md .gitignore
# git add docs/
# git add wp-content/plugins/

# Verify staged files
git status
```

#### Step 10: Create Commit

```bash
git commit -m "docs: Add comprehensive documentation and project files

Added Files:
- README.md: Complete project overview (Arabic + English)
- CHANGELOG.md: Version history (v1.0.0 to v1.0.10)
- .gitignore: WordPress and development exclusions
- docs/PROJECT_SYSTEM_DOCUMENTATION.md: System reference
- docs/DEVELOPMENT_METHODOLOGY.md: AI workflow guide
- docs/DEPLOYMENT_GUIDE.md: Deployment instructions
- docs/GITHUB_DEPLOYMENT.md: GitHub operations guide

Documentation Coverage:
✅ System architecture (20+ features)
✅ User personas and journeys
✅ API reference (8 endpoints)
✅ Database schema (8+ tables)
✅ Security model (6 layers)
✅ Development workflow (Claude + Cursor)
✅ Token management strategies
✅ Deployment procedures

Project Status: Production Ready 🚀"
```

#### Step 11: Push to GitHub

```bash
# First push with upstream tracking
git push -u origin main

# If GitHub has different files (conflict):
# Option A: Rebase (recommended)
git pull origin main --rebase
git push -u origin main

# Option B: Force push (use if local is source of truth)
git push -u origin main --force

# You will be prompted:
# Username: mfarrag2050
# Password: [Paste your Personal Access Token]
```

#### Step 12: Create Version Tag

```bash
# Create annotated tag
git tag -a v1.0.10 -m "Release v1.0.10 - Complete Lead Management System

Features:
- Analytics Dashboard with KPIs and Charts
- Settings Management Interface
- Complete Documentation Suite
- REST API (8 endpoints)
- CSV Import/Export
- Airtable + Telegram Integration

Status: Production Ready"

# Push tag
git push origin v1.0.10

# Verify tags
git tag -l
```

---

## 📚 Documentation Update Workflow {#documentation}

### Automated Documentation Sync

**Workflow:** Every time you update documentation, sync it to GitHub automatically.

#### Create Update Script

```bash
# Create documentation update script
cat > update-docs.sh << 'EOF'
#!/bin/bash

# AQOP Documentation Update Script
# Author: Muhammed Derviş
# Date: November 17, 2025

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  AQOP Documentation Update            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Navigate to project
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Update docs from source
echo -e "${GREEN}[1/5]${NC} Copying documentation files..."
cp ../mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md docs/ 2>/dev/null || echo "Skipped PROJECT_SYSTEM_DOCUMENTATION.md"
cp ../mnt/project/DEVELOPMENT_METHODOLOGY.md docs/ 2>/dev/null || echo "Skipped DEVELOPMENT_METHODOLOGY.md"
cp ../mnt/project/DEPLOYMENT_GUIDE.md docs/ 2>/dev/null || echo "Skipped DEPLOYMENT_GUIDE.md"
cp ../mnt/project/GITHUB_DEPLOYMENT.md docs/ 2>/dev/null || echo "Skipped GITHUB_DEPLOYMENT.md"

# Check for changes
echo -e "${GREEN}[2/5]${NC} Checking for changes..."
if git diff --quiet docs/; then
    echo "No changes detected in documentation."
    exit 0
fi

# Show changes
echo -e "${GREEN}[3/5]${NC} Changes detected:"
git status --short docs/

# Add changes
echo -e "${GREEN}[4/5]${NC} Staging changes..."
git add docs/

# Commit
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
git commit -m "docs: Update documentation - ${TIMESTAMP}

Automated documentation sync from /mnt/project/"

# Push
echo -e "${GREEN}[5/5]${NC} Pushing to GitHub..."
git push origin main

echo ""
echo -e "${GREEN}✅ Documentation updated successfully!${NC}"
echo "View at: https://github.com/mfarrag2050/OperationSystem"
EOF

# Make executable
chmod +x update-docs.sh

# Test run
./update-docs.sh
```

#### Usage

```bash
# Whenever you update documentation:
./update-docs.sh

# Or create alias for quick access:
echo 'alias update-docs="cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation && ./update-docs.sh"' >> ~/.zshrc
source ~/.zshrc

# Now you can run from anywhere:
update-docs
```

---

## 🤖 Cursor Prompts for GitHub Operations {#cursor-prompts}

### Prompt Template for Documentation Updates

When you want Cursor to update documentation and push to GitHub:

```markdown
# Task: Update Project Documentation and Push to GitHub

**Objective:** Update documentation files and sync to GitHub repository.

---

## Part 1: Update Documentation Files

Update the following documentation files in `/mnt/project/`:

1. **PROJECT_SYSTEM_DOCUMENTATION.md**
   - Add new feature: [FEATURE_NAME]
   - Update section: [SECTION_NAME]
   - Current version: [OLD_VERSION]
   - New version: [NEW_VERSION]

2. **DEVELOPMENT_METHODOLOGY.md** (if applicable)
   - Update workflow for: [WORKFLOW_CHANGE]

3. **CHANGELOG.md** (in project root)
   - Add entry for version [VERSION]
   - Date: [DATE]
   - Changes: [LIST_OF_CHANGES]

---

## Part 2: Sync to Project docs/ Folder

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Copy updated docs
cp ../mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md docs/
cp ../mnt/project/DEVELOPMENT_METHODOLOGY.md docs/
cp ../mnt/project/GITHUB_DEPLOYMENT.md docs/

# Update CHANGELOG.md in root
cp ../mnt/project/CHANGELOG.md ./
```

---

## Part 3: Git Commit and Push

```bash
# Check changes
git status

# Add files
git add docs/ CHANGELOG.md

# Commit with descriptive message
git commit -m "docs: Update documentation for [FEATURE_NAME]

Changes:
- Added [FEATURE_NAME] documentation
- Updated [SECTION_NAME] section
- Version bumped to [VERSION]
- Updated changelog

Files modified:
- docs/PROJECT_SYSTEM_DOCUMENTATION.md
- docs/DEVELOPMENT_METHODOLOGY.md
- CHANGELOG.md"

# Push to GitHub
git push origin main
```

---

## Part 4: Create Release Tag (if new version)

```bash
# Only if this is a new version release
git tag -a v[VERSION] -m "Release v[VERSION] - [RELEASE_TITLE]

[RELEASE_NOTES]"

git push origin v[VERSION]
```

---

## Verification Checklist:

- [ ] Documentation files updated in /mnt/project/
- [ ] Files copied to docs/ folder
- [ ] CHANGELOG.md updated
- [ ] Git commit created with descriptive message
- [ ] Changes pushed to GitHub
- [ ] Tag created (if new version)
- [ ] GitHub repository verified

---

**IMPORTANT REMINDERS:**

1. ✅ Always update version numbers consistently
2. ✅ Keep CHANGELOG.md in sync
3. ✅ Use semantic versioning (MAJOR.MINOR.PATCH)
4. ✅ Write clear commit messages
5. ✅ Verify changes on GitHub after push
```

---

### Prompt Template for New Feature Deployment

```markdown
# Task: Deploy New Feature to GitHub

**Feature:** [FEATURE_NAME]  
**Version:** [OLD_VERSION] → [NEW_VERSION]  
**Date:** [DATE]

---

## Part 1: Code Implementation

[Implement the feature code here - already done by previous prompts]

---

## Part 2: Update Documentation

### 2.1 Update PROJECT_SYSTEM_DOCUMENTATION.md

Add to "Complete Feature List" section:

```markdown
| [FEATURE_NAME] | [LOCATION] | [ENTRY_POINT] | [PERSONAS] | ✅ Working |
```

Add to "Change Log" section:

```markdown
### Version [NEW_VERSION] ([DATE])
**Added:**
- [FEATURE_NAME]: [DESCRIPTION]

**Files:**
- [LIST_OF_NEW_FILES]
- [LIST_OF_MODIFIED_FILES]
```

### 2.2 Update CHANGELOG.md

```markdown
## [[NEW_VERSION]] - [DATE]

### Added
- **[FEATURE_NAME]**: [DESCRIPTION]
  - [DETAIL_1]
  - [DETAIL_2]

### Changed
- [WHAT_CHANGED]

### Files
- `[FILE_1]` (NEW)
- `[FILE_2]` (MODIFIED)
```

### 2.3 Update README.md (if major feature)

Update version badge and add to features list.

---

## Part 3: Git Operations

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Stage all changes
git add .

# Commit
git commit -m "feat: Add [FEATURE_NAME] - v[NEW_VERSION]

Implemented:
- [FEATURE_COMPONENT_1]
- [FEATURE_COMPONENT_2]

Documentation:
- Updated PROJECT_SYSTEM_DOCUMENTATION.md
- Updated CHANGELOG.md
- Updated README.md

Status: Production Ready"

# Push
git push origin main

# Create tag
git tag -a v[NEW_VERSION] -m "Release v[NEW_VERSION] - [FEATURE_NAME]

[DETAILED_RELEASE_NOTES]"

git push origin v[NEW_VERSION]
```

---

## Part 4: Verification

- [ ] Feature working locally
- [ ] Documentation updated
- [ ] Committed to Git
- [ ] Pushed to GitHub
- [ ] Tag created
- [ ] GitHub repository shows new version
- [ ] README displays correctly
- [ ] CHANGELOG updated

---

**Post-Deployment:**

1. Visit: https://github.com/mfarrag2050/OperationSystem
2. Verify all files updated
3. Check release tag in "Releases" section
4. Create GitHub Release (optional):
   - Go to Releases → Create Release
   - Choose tag v[NEW_VERSION]
   - Title: "AQOP v[NEW_VERSION] - [FEATURE_NAME]"
   - Description: Copy from CHANGELOG.md
```

---

## 🔄 Automated Documentation Updates {#automation}

### Method 1: Manual Script (Recommended for Now)

**File:** `update-docs.sh` (created in previous section)

**Usage:**
```bash
# After updating any documentation in /mnt/project/
./update-docs.sh
```

**What it does:**
1. ✅ Copies all documentation from `/mnt/project/` to `docs/`
2. ✅ Detects changes
3. ✅ Commits changes
4. ✅ Pushes to GitHub

---

### Method 2: Git Hook (Future Enhancement)

**Create post-commit hook:**

```bash
# Create hook file
cat > .git/hooks/post-commit << 'EOF'
#!/bin/bash

# Auto-sync documentation after commit
DOCS_SOURCE="../mnt/project"
DOCS_DEST="docs"

# Check if docs were modified
if git diff-tree --no-commit-id --name-only -r HEAD | grep -q "^${DOCS_SOURCE}"; then
    echo "Documentation changes detected, syncing..."
    cp ${DOCS_SOURCE}/*.md ${DOCS_DEST}/
    git add ${DOCS_DEST}/
    git commit --amend --no-edit
fi
EOF

# Make executable
chmod +x .git/hooks/post-commit
```

---

### Method 3: Cursor AI Automation Prompt

**Save this as a template for Cursor:**

```markdown
# AUTOMATED TASK: Documentation Sync

**Trigger:** After any documentation update

**Actions:**

1. Copy all `.md` files from `/mnt/project/` to `docs/`
2. Check for changes with `git diff`
3. If changes detected:
   - Stage: `git add docs/`
   - Commit: `git commit -m "docs: Auto-sync documentation"`
   - Push: `git push origin main`
4. Report completion

**Run this automatically whenever:**
- PROJECT_SYSTEM_DOCUMENTATION.md is updated
- DEVELOPMENT_METHODOLOGY.md is updated
- GITHUB_DEPLOYMENT.md is updated
- Any other .md file in /mnt/project/ is modified
```

---

## 📖 Git Best Practices {#best-practices}

### Commit Message Format

```
<type>: <short description>

<detailed description>

<metadata>
```

**Types:**
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code formatting (no logic change)
- `refactor:` Code restructuring
- `perf:` Performance improvement
- `test:` Adding/updating tests
- `chore:` Build process, dependencies

**Examples:**

```bash
# Good
git commit -m "feat: Add WhatsApp integration

- Added WhatsApp field to lead form
- Integrated with WhatsApp Business API
- Added notification templates

Files:
- includes/class-leads-manager.php (modified)
- public/class-public-form.php (modified)
- api/class-whatsapp-integration.php (new)"

# Bad
git commit -m "updates"
git commit -m "fixed stuff"
git commit -m "changes"
```

---

### Branching Strategy

**For Solo Development (Current):**
```
main (production)
└── Direct commits with good messages
```

**For Team Development (Future):**
```
main (production)
├── develop (integration)
│   ├── feature/dashboard-v2
│   ├── feature/whatsapp
│   └── feature/analytics
└── hotfix/critical-bug
```

**Branch Creation:**
```bash
# Create feature branch
git checkout -b feature/new-feature

# Work on feature
git add .
git commit -m "feat: Add new feature"

# Push branch
git push origin feature/new-feature

# Merge to main (after review)
git checkout main
git merge feature/new-feature
git push origin main

# Delete branch
git branch -d feature/new-feature
git push origin --delete feature/new-feature
```

---

### Version Numbering (Semantic Versioning)

```
MAJOR.MINOR.PATCH
  │     │     └── Bug fixes, minor updates
  │     └──────── New features, backwards compatible
  └────────────── Breaking changes

Examples:
1.0.0  → Initial release
1.0.1  → Bug fix
1.1.0  → New feature (compatible)
2.0.0  → Breaking change (major rewrite)
```

**When to Bump:**
```bash
# Patch (1.0.0 → 1.0.1)
- Bug fixes
- Documentation updates
- Security patches

# Minor (1.0.0 → 1.1.0)
- New features
- New API endpoints
- Backwards compatible changes

# Major (1.0.0 → 2.0.0)
- Breaking API changes
- Complete rewrites
- Database schema changes requiring migration
```

---

### .gitignore Best Practices

```bash
# ✅ DO include in .gitignore:
- wp-config.php (database credentials)
- .env files (environment variables)
- node_modules/ (dependencies)
- vendor/ (composer packages)
- *.log (log files)
- .DS_Store (macOS system files)
- uploads/ (user-uploaded content)

# ❌ DON'T include in .gitignore:
- Plugin code (aqop-core, aqop-leads)
- Documentation (docs/)
- README.md, CHANGELOG.md
- Source code files
```

---

## 🔧 Troubleshooting {#troubleshooting}

### Common Issues and Solutions

#### Issue 1: "Repository not found"

```bash
# Problem: Remote URL is wrong
git remote -v

# Solution: Update URL
git remote set-url origin https://github.com/mfarrag2050/OperationSystem.git

# Verify
git remote -v
```

---

#### Issue 2: "Permission denied"

```bash
# Problem: Authentication failed

# Solution A: Check credentials
# Make sure using Personal Access Token, not password

# Solution B: Check SSH key (if using SSH)
ssh -T git@github.com

# If failed, add SSH key:
# 1. Generate: ssh-keygen -t ed25519 -C "your-email@example.com"
# 2. Copy: cat ~/.ssh/id_ed25519.pub
# 3. Add to GitHub: Settings → SSH Keys → Add
```

---

#### Issue 3: "Failed to push - rejected"

```bash
# Problem: Remote has changes you don't have locally

# Solution A: Pull and merge
git pull origin main
# Resolve conflicts if any
git push origin main

# Solution B: Pull and rebase (cleaner history)
git pull origin main --rebase
git push origin main

# Solution C: Force push (DANGEROUS - only if you're sure)
git push origin main --force
```

---

#### Issue 4: "Large files rejected"

```bash
# Problem: GitHub rejects files > 100MB

# Solution: Check large files
git ls-files | xargs du -h | sort -h | tail -20

# Remove from Git
git rm --cached large-file.zip

# Add to .gitignore
echo "*.zip" >> .gitignore

# Commit
git commit -m "Remove large file"
git push origin main
```

---

#### Issue 5: "Committed sensitive data"

```bash
# Problem: Accidentally committed passwords/keys

# IMMEDIATE ACTION:
# 1. Remove from file
vim wp-config.php  # or any file with sensitive data
# Delete sensitive line

# 2. Commit removal
git add wp-config.php
git commit -m "Remove sensitive data"

# 3. Remove from history (CAREFUL!)
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch wp-config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 4. Force push
git push origin main --force

# 5. ROTATE CREDENTIALS
# Change all exposed passwords/keys immediately!
```

---

#### Issue 6: "Merge conflicts"

```bash
# Problem: Conflicts during pull/merge

# Step 1: Identify conflicts
git status

# Step 2: Open conflicted files
# Look for markers:
# <<<<<<< HEAD
# Your changes
# =======
# Their changes
# >>>>>>> branch-name

# Step 3: Resolve manually
# Choose what to keep, remove markers

# Step 4: Mark as resolved
git add conflicted-file.php

# Step 5: Complete merge
git commit -m "Resolve merge conflicts"
git push origin main
```

---

## 📋 Quick Reference {#quick-reference}

### Daily Commands

```bash
# Check status
git status

# View recent commits
git log --oneline -10

# Add files
git add .

# Commit
git commit -m "type: description"

# Push
git push origin main

# Pull latest
git pull origin main
```

---

### Documentation Update Workflow

```bash
# 1. Update docs in /mnt/project/
vim /Users/mfarrag/Documents/Operation/aql-leads/mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md

# 2. Run update script
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation
./update-docs.sh

# Done! Changes automatically pushed to GitHub
```

---

### New Version Release

```bash
# 1. Update version in plugin files
vim wp-content/plugins/aqop-core/aqop-core.php
# Change: Version: 1.0.11

vim wp-content/plugins/aqop-leads/aqop-leads.php
# Change: Version: 1.0.11

# 2. Update CHANGELOG.md
vim CHANGELOG.md
# Add new version section

# 3. Commit
git add .
git commit -m "chore: Bump version to 1.0.11"

# 4. Tag
git tag -a v1.0.11 -m "Release v1.0.11"

# 5. Push
git push origin main --tags
```

---

### Repository URLs

```
GitHub Repository:
https://github.com/mfarrag2050/OperationSystem

Clone URL (HTTPS):
https://github.com/mfarrag2050/OperationSystem.git

Clone URL (SSH):
git@github.com:mfarrag2050/OperationSystem.git

GitHub Pages (if enabled):
https://mfarrag2050.github.io/OperationSystem
```

---

### Useful Git Aliases

```bash
# Add to ~/.zshrc or ~/.bashrc

# Status
alias gs='git status'

# Log (pretty)
alias gl='git log --oneline --graph --decorate --all -10'

# Add all
alias ga='git add .'

# Commit
alias gc='git commit -m'

# Push
alias gp='git push origin main'

# Pull
alias gpl='git pull origin main'

# Update docs
alias udocs='cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation && ./update-docs.sh'

# Reload aliases
source ~/.zshrc
```

---

## 🎯 Summary Checklist

### Initial Setup ✅
- [ ] Git installed and configured
- [ ] GitHub account ready
- [ ] Personal Access Token created and saved
- [ ] Repository created on GitHub
- [ ] Remote added to local repository

### Essential Files ✅
- [ ] .gitignore created
- [ ] README.md created
- [ ] CHANGELOG.md created
- [ ] docs/ folder with 4 documentation files

### First Push ✅
- [ ] Files staged with `git add .`
- [ ] Initial commit created
- [ ] Pushed to GitHub with `git push -u origin main`
- [ ] Version tag created and pushed
- [ ] Repository verified on GitHub

### Documentation Workflow ✅
- [ ] Documentation source in `/mnt/project/`
- [ ] Documentation copied to `docs/`
- [ ] Update script created (`update-docs.sh`)
- [ ] Automation working

### Ongoing Maintenance ✅
- [ ] Commit messages follow format
- [ ] Version numbers use semantic versioning
- [ ] CHANGELOG.md kept up to date
- [ ] Documentation synced after changes
- [ ] Tags created for releases

---

## 📞 Support & Resources

**GitHub Documentation:**
- Git Basics: https://git-scm.com/book/en/v2
- GitHub Guides: https://guides.github.com
- Semantic Versioning: https://semver.org

**Project Resources:**
- Repository: https://github.com/mfarrag2050/OperationSystem
- Issues: https://github.com/mfarrag2050/OperationSystem/issues
- Releases: https://github.com/mfarrag2050/OperationSystem/releases

**Internal Documentation:**
- [Complete System Docs](docs/PROJECT_SYSTEM_DOCUMENTATION.md)
- [Development Methodology](docs/DEVELOPMENT_METHODOLOGY.md)
- [API Reference](docs/PROJECT_SYSTEM_DOCUMENTATION.md#api-documentation)

---

**END OF GUIDE**

*Last Updated: November 17, 2025*  
*Version: 1.0.0*  
*Status: Complete*

---

**File:** `GITHUB_DEPLOYMENT.md`  
**Location:** `docs/`  
**Maintained by:** Muhammed Derviş  
**Auto-sync:** Yes (via update-docs.sh)
