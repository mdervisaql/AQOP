# AQOP Deployment Guide

## Server Information

| Item | Value |
|------|-------|
| **Server IP** | `69.62.113.67` |
| **SSH** | `ssh root@69.62.113.67` |
| **Panel** | aaPanel `https://69.62.113.67:30848/ca5cb8ba` |
| **Panel User** | `axzszi0j` |
| **Web Server** | nginx + OpenLiteSpeed |
| **CDN** | Cloudflare |

## URLs

| Service | URL |
|---------|-----|
| **Frontend (React)** | `https://leads.aqleeat.com/` |
| **Backend (WordPress)** | `https://operation.aqleeat.co/wp-admin` |

## Docker Containers

| Container | Image | Port |
|-----------|-------|------|
| `aqop-wordpress` | `wordpress:6.4-php8.1-apache` | `8080 → 80` |
| `aqop-mysql` | `mysql:8.0` | `3306` |
| `n8n` | `n8nio/n8n:latest` | `5678` |

## Database

| Item | Value |
|------|-------|
| **DB Name** | `aqop_db` |
| **DB User** | `aqop_user` |
| **DB Pass** | `AqopDB2025!` |
| **Docker Compose** | `/root/aqop-docker/docker-compose.yml` |

---

## Server Paths

### Backend (WordPress Plugins)
```
/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/
├── aqop-core/
├── aqop-leads/
├── aqop-jwt-auth/
└── aqop-feedback/
```

### Frontend (React App) — IMPORTANT
```
/www/app-frontend/          ← nginx يخدم الملفات من هنا
/www/wwwroot/leads.aqleeat.com/   ← هذا المسار لا يُستخدم!
```

> **تنبيه:** nginx مُعد على `root /www/app-frontend;` في:
> `/www/server/panel/vhost/nginx/leads.aqleeat.com.conf`

---

## Deployment Steps

### Step 1: Upload Backend Plugins (from Mac terminal)

```bash
# aqop-leads
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-leads/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-leads/

# aqop-core
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-core/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-core/

# aqop-jwt-auth
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-jwt-auth/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-jwt-auth/
```

### Step 2: Build Frontend (from Mac terminal)

```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/aqop-frontend
npm run build
```

### Step 3: Upload Frontend (from Mac terminal)

```bash
rsync -avz --delete dist/ root@69.62.113.67:/www/app-frontend/
```

> **مهم جداً:** الرفع إلى `/www/app-frontend/` وليس `/www/wwwroot/leads.aqleeat.com/`

### Step 4: Reload nginx (from SSH on server)

```bash
ssh root@69.62.113.67
nginx -s reload
```

### Step 5: Verify Deployment

```bash
# from SSH - verify new files are served
curl -s https://leads.aqleeat.com/ | head -15
```

---

## Cache Busting (if needed)

nginx يخزّن ملفات JS/CSS لمدة 12 ساعة. Vite يستخدم content-hash في أسماء الملفات، لذا عادةً لا تحتاج cache busting.

إذا لم تظهر التحديثات:

```bash
# from SSH: force cache bust on index.html
sed -i 's/\.js"/\.js?v=NEW_VERSION"/' /www/app-frontend/index.html
nginx -s reload
```

أو امسح كاش Cloudflare من: `https://dash.cloudflare.com`

---

## Database Operations (from SSH)

```bash
# Check database columns
docker exec -it aqop-mysql mysql -u aqop_user -pAqopDB2025! -e "DESCRIBE aqop_db.wp_aq_leads;"

# Run a query
docker exec -it aqop-mysql mysql -u aqop_user -pAqopDB2025! -e "SELECT COUNT(*) FROM aqop_db.wp_aq_leads;"

# Deactivate/Activate plugin (for DB upgrades)
# Go to: https://operation.aqleeat.co/wp-admin/plugins.php
# Deactivate then Activate "AQOP Leads"
```

---

## Quick Deploy Script (one command)

Save this as `deploy.sh` and run it:

```bash
#!/bin/bash
echo "🚀 Building frontend..."
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/aqop-frontend
npm run build

echo "📦 Uploading plugins..."
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-leads/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-leads/
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-core/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-core/
rsync -avz /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/wp-content/plugins/aqop-jwt-auth/ root@69.62.113.67:/var/lib/docker/volumes/aqop-docker_wordpress_data/_data/wp-content/plugins/aqop-jwt-auth/

echo "🌐 Uploading frontend..."
rsync -avz --delete dist/ root@69.62.113.67:/www/app-frontend/

echo "🔄 Reloading nginx..."
ssh root@69.62.113.67 "nginx -s reload"

echo "✅ Deployment complete!"
echo "🔗 Frontend: https://leads.aqleeat.com/"
echo "🔗 Backend:  https://operation.aqleeat.co/wp-admin"
```

---

## GitHub Repository

```
https://github.com/mdervisaql/AQOP
```

Push changes:
```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation
git add -A
git commit -m "description of changes"
git push https://mdervisaql:TOKEN@github.com/mdervisaql/AQOP.git main
```

---

## Important Notes

1. **Frontend path**: Always deploy to `/www/app-frontend/` NOT `/www/wwwroot/leads.aqleeat.com/`
2. **DB upgrades**: After adding new columns, deactivate/activate the plugin from wp-admin
3. **Cloudflare cache**: May need purging after major changes
4. **nginx JS cache**: 12 hours - Vite hashes handle this automatically
5. **React routes**: Use paths like `/add-lead` NOT `/leads/add` to avoid conflicts with `/leads/:id`
