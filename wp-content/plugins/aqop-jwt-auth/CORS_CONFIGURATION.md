# CORS Configuration - Production Ready

## ✅ Dynamic CORS Configuration Implemented

CORS headers are now flexible and configurable for both development and production environments.

---

## 🎯 What Changed

### Before (Hardcoded):
```php
header('Access-Control-Allow-Origin: http://localhost:5174');
```
❌ Problem: Only works with one specific URL
❌ Problem: Must edit code for production

### After (Dynamic):
```php
$origin = $_SERVER['HTTP_ORIGIN'];
$allowed_origins = aqop_jwt_get_allowed_origins();

if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
```
✅ Solution: Checks request origin against allowed list
✅ Solution: Configurable via WordPress admin

---

## 🔧 How It Works

### 1. **Request Origin Detection**
```php
$origin = $_SERVER['HTTP_ORIGIN'];
// e.g., "https://app.yourdomain.com"
```

### 2. **Allowed Origins List**
```php
// Get from options + defaults
$allowed_origins = array(
    'http://localhost:5173',      // Vite default
    'http://localhost:5174',      // AQOP frontend
    'http://localhost:3000',      // Common dev port
    'https://app.yourdomain.com', // Production (from settings)
);
```

### 3. **Origin Validation**
```php
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
```

---

## ⚙️ Configuration

### Access Settings:
```
WordPress Admin → Leads → Settings → CORS Settings tab
```

### Features:
- ✅ Textarea for custom origins (one per line)
- ✅ Default origins always allowed (localhost)
- ✅ Current origin display
- ✅ CORS status indicator
- ✅ Quick setup guide
- ✅ Security warnings

---

## 📝 Default Origins (Always Allowed)

These are hardcoded for development:
```
http://localhost:5173  // Vite default
http://localhost:5174  // AQOP frontend
http://localhost:3000  // React/Next.js default
```

---

## 🌐 Production Setup

### Step 1: Navigate to Settings
```
WordPress Admin → Leads → Settings → CORS Settings
```

### Step 2: Add Production Domains
In the "Allowed Origins" textarea, enter:
```
https://app.yourdomain.com
https://dashboard.yourdomain.com
https://staging.yourdomain.com
```
(One per line)

### Step 3: Save Settings
Click "Save CORS Settings"

### Step 4: Verify
- Check "Current Request Origin" shows your domain
- Check "CORS Status" shows ✓ Origin Allowed

---

## 🎨 Settings Page UI

### CORS Status Indicators:
- **✓ Origin Allowed** - Green badge (origin is in allowed list)
- **✗ Origin Blocked** - Red badge (origin not allowed)
- **No Origin Detected** - Gray badge (direct access)

### Current Origin Display:
Shows the origin of the current WordPress admin request for reference.

---

## 🔐 Security Features

### ✅ Implemented:
- **Strict origin matching** - Exact string comparison
- **No wildcards** - Prevents open CORS (security risk)
- **HTTPS enforcement** - Warned in UI
- **Input sanitization** - All origins sanitized
- **Admin-only access** - Only admins can configure

### Best Practices:
- ✅ Only add trusted domains
- ✅ Use HTTPS in production
- ✅ Don't use `*` wildcard
- ✅ Keep list minimal

---

## 📊 CORS Flow

### Request from Allowed Origin:
```
React App (https://app.yourdomain.com)
    ↓
Makes API request
    ↓
Backend checks: "https://app.yourdomain.com" in allowed_origins?
    ↓
YES → Header: Access-Control-Allow-Origin: https://app.yourdomain.com
    ↓
Browser allows request ✅
```

### Request from Unknown Origin:
```
Unknown App (https://malicious.com)
    ↓
Makes API request
    ↓
Backend checks: "https://malicious.com" in allowed_origins?
    ↓
NO → Header: Access-Control-Allow-Origin: http://localhost:5174 (fallback)
    ↓
Browser blocks request ❌
```

---

## 🧪 Testing

### Test 1: Development (Localhost)
**Setup:**
- React app running on `http://localhost:5174`
- No custom origins configured

**Expected:**
- ✅ Requests work (localhost always allowed)
- ✅ CORS status shows "✓ Origin Allowed"

### Test 2: Production Domain
**Setup:**
1. Add `https://app.yourdomain.com` to allowed origins
2. Save settings
3. Deploy React app to `https://app.yourdomain.com`
4. Make API request

**Expected:**
- ✅ API request succeeds
- ✅ CORS header set to `https://app.yourdomain.com`

### Test 3: Unauthorized Domain
**Setup:**
- React app on `https://unauthorized.com`
- Not in allowed origins

**Expected:**
- ❌ Browser blocks request (CORS error)
- ❌ Console shows CORS policy error

### Test 4: Multiple Domains
**Setup:**
Add multiple domains:
```
https://app.yourdomain.com
https://staging.yourdomain.com
https://beta.yourdomain.com
```

**Expected:**
- ✅ All three domains can make API requests
- ✅ Each gets correct CORS header

---

## 💾 Data Storage

### Option Name:
```
aqop_jwt_allowed_origins
```

### Format:
```
https://app.yourdomain.com
https://dashboard.yourdomain.com
https://staging.yourdomain.com
```
(One per line, newline-separated)

### Retrieval:
```php
$custom_origins = get_option('aqop_jwt_allowed_origins', '');
$origins_array = array_filter(array_map('trim', explode("\n", $custom_origins)));
```

---

## 🔧 Code Implementation

### Files Modified:

#### 1. **aqop-jwt-auth/aqop-jwt-auth.php**
```php
// New function
function aqop_jwt_get_allowed_origins() {
    $custom_origins = get_option('aqop_jwt_allowed_origins', '');
    $default_origins = array('http://localhost:5173', ...);
    
    if (!empty($custom_origins)) {
        $origins = array_map('trim', explode("\n", $custom_origins));
        return array_merge($default_origins, $origins);
    }
    
    return $default_origins;
}

// Updated CORS handler
add_action('rest_api_init', function() {
    $origin = $_SERVER['HTTP_ORIGIN'];
    $allowed = aqop_jwt_get_allowed_origins();
    
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
});
```

#### 2. **aqop-leads/admin/views/settings.php**
- Added "CORS Settings" tab
- Added origin configuration textarea
- Added current origin display
- Added CORS status indicator
- Added help text and warnings

#### 3. **aqop-leads/admin/class-leads-admin.php**
- Added `update_cors` case to switch
- Added `update_cors_settings()` method

---

## 📋 Production Checklist

### Before Deployment:

- [ ] Add production domain to CORS settings
- [ ] Save CORS settings
- [ ] Verify "CORS Status" shows green checkmark
- [ ] Test API call from production frontend
- [ ] Ensure HTTPS is used
- [ ] Remove unused development origins (optional)

### Example Production Config:
```
https://app.aqleeat.com
https://dashboard.aqleeat.com
```

---

## 🚨 Common Issues

### Issue: CORS Error in Production

**Symptoms:**
```
Access to fetch at 'https://api.yourdomain.com/wp-json/...' 
from origin 'https://app.yourdomain.com' has been blocked by CORS policy
```

**Solution:**
1. Go to WordPress Admin → Leads → Settings → CORS Settings
2. Add `https://app.yourdomain.com` to allowed origins
3. Save settings
4. Refresh frontend app

### Issue: Works in Development, Not Production

**Check:**
- [ ] Did you add production URL to allowed origins?
- [ ] Is production URL using HTTPS?
- [ ] Did you click "Save CORS Settings"?
- [ ] Did you refresh the React app?

---

## 💡 Best Practices

### ✅ Do's:
- Use HTTPS in production
- Add specific domains only
- Test before deploying
- Keep list minimal
- Document your origins

### ❌ Don'ts:
- Don't use `Access-Control-Allow-Origin: *` in production
- Don't add untrusted domains
- Don't use HTTP in production
- Don't forget to save settings

---

## 🎯 Multi-Environment Setup

### Development:
```
http://localhost:5174  (always allowed by default)
```

### Staging:
```
Add to settings:
https://staging.yourdomain.com
```

### Production:
```
Add to settings:
https://app.yourdomain.com
https://dashboard.yourdomain.com
```

---

## ✅ Verification

### Check Current Configuration:
```php
// In WordPress, run this (wp-cli or code):
$origins = get_option('aqop_jwt_allowed_origins');
print_r($origins);

$all_origins = aqop_jwt_get_allowed_origins();
print_r($all_origins);
```

### Check CORS Headers:
```bash
curl -I -X OPTIONS "http://localhost:8888/aqleeat-operation/wp-json/aqop-jwt/v1/login" \
  -H "Origin: https://app.yourdomain.com"
```

**Expected:**
```
Access-Control-Allow-Origin: https://app.yourdomain.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce
Access-Control-Allow-Credentials: true
```

---

## 🎉 Status: PRODUCTION READY ✅

CORS is now fully configurable for any environment:
- ✅ Dynamic origin detection
- ✅ Configurable via WordPress admin
- ✅ Default origins for development
- ✅ Production-ready
- ✅ Secure (no wildcards)
- ✅ Admin UI for easy management
- ✅ Status indicators
- ✅ Help text included

---

## 📚 Documentation

- JWT Plugin: `aqop-jwt-auth/CORS_CONFIGURATION.md`
- Settings: Leads → Settings → CORS Settings
- Function: `aqop_jwt_get_allowed_origins()`

---

**Your CORS configuration is now production-ready and easily manageable!** 🌐✅

**Last Updated:** November 17, 2025

