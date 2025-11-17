#!/bin/bash

# AQOP Documentation Update Script
# Author: Muhammed Derviş
# Date: November 17, 2025

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  تحديث وثائق AQOP                     ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

# Navigate to project
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation

# Update docs from source
echo -e "${GREEN}[1/6]${NC} نسخ ملفات التوثيق..."
cp ../mnt/project/PROJECT_SYSTEM_DOCUMENTATION.md docs/ 2>/dev/null && echo "  ✅ PROJECT_SYSTEM_DOCUMENTATION.md" || echo "  ⏭️  تم تخطي PROJECT_SYSTEM_DOCUMENTATION.md"
cp ../mnt/project/DEVELOPMENT_METHODOLOGY.md docs/ 2>/dev/null && echo "  ✅ DEVELOPMENT_METHODOLOGY.md" || echo "  ⏭️  تم تخطي DEVELOPMENT_METHODOLOGY.md"
cp ../mnt/project/DEPLOYMENT_GUIDE.md docs/ 2>/dev/null && echo "  ✅ DEPLOYMENT_GUIDE.md" || echo "  ⏭️  تم تخطي DEPLOYMENT_GUIDE.md"
cp ../mnt/project/GITHUB_DEPLOYMENT.md docs/ 2>/dev/null && echo "  ✅ GITHUB_DEPLOYMENT.md" || echo "  ⏭️  تم تخطي GITHUB_DEPLOYMENT.md"

# Check for changes
echo ""
echo -e "${GREEN}[2/6]${NC} فحص التغييرات..."
if git diff --quiet docs/; then
    echo -e "${YELLOW}لا توجد تغييرات في التوثيق.${NC}"
    exit 0
fi

# Show changes
echo -e "${GREEN}[3/6]${NC} تم اكتشاف تغييرات:"
git status --short docs/

# Add changes
echo ""
echo -e "${GREEN}[4/6]${NC} إضافة التغييرات لـ Git..."
git add docs/

# Commit
echo -e "${GREEN}[5/6]${NC} إنشاء Commit..."
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
git commit -m "docs: تحديث التوثيق - ${TIMESTAMP}

تم التحديث التلقائي من /mnt/project/
ملفات محدثة: $(git diff --staged --name-only docs/ | wc -l)"

# Push
echo -e "${GREEN}[6/6]${NC} الرفع إلى GitHub..."
git push origin main

echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ تم تحديث التوثيق بنجاح!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo "🌐 عرض على GitHub:"
echo "   https://github.com/mfarrag2050/OperationSystem"
