# AQOP Core - Custom Roles Updated

## ✅ Issue Fixed

The roles manager was only creating 2 roles, but 4 roles are required for the AQOP Platform to function properly.

---

## 🔧 Changes Made

### Updated File:
`includes/authentication/class-roles-manager.php`

### Added 2 Missing Roles:

#### 3. **aq_supervisor** (AQ Supervisor)
**Capabilities:**
- `read` - Basic WordPress read access
- `view_control_center` - Access control center
- `view_event_logs` - View system events
- `manage_team_leads` - Manage team's leads
- `assign_leads` - Assign leads to agents
- `view_team_reports` - View team performance
- `edit_own_leads` - Edit own leads
- `view_own_leads` - View own leads
- `add_lead_notes` - Add notes to leads
- `export_analytics` - Export reports

#### 4. **aq_agent** (AQ Agent)
**Capabilities:**
- `read` - Basic WordPress read access
- `edit_own_leads` - Edit assigned leads
- `view_own_leads` - View assigned leads
- `add_lead_notes` - Add notes to leads
- `update_lead_status` - Change lead status

---

## 📋 Complete Roles List

### 1. operation_admin (Operation Admin)
- Full administrative access
- Inherits all WordPress admin capabilities
- Custom operation capabilities

### 2. operation_manager (Operation Manager)
- Limited management access
- Can view control center and logs
- Can export analytics
- Cannot make system changes

### 3. aq_supervisor (AQ Supervisor) ⭐ NEW
- Team supervision capabilities
- Manage team leads
- Assign leads to agents
- View team reports

### 4. aq_agent (AQ Agent) ⭐ NEW
- Agent-level access
- View and manage assigned leads only
- Add notes and update status
- Limited permissions

---

## 🔄 How Roles Are Created

### Activation Flow:
```
Plugin Activated
    ↓
AQOP_Activator::activate()
    ↓
Load class-roles-manager.php
    ↓
AQOP_Roles_Manager::create_roles()
    ↓
Creates 4 roles:
  ✅ operation_admin
  ✅ operation_manager
  ✅ aq_supervisor
  ✅ aq_agent
```

### Verification in Code:
**File:** `includes/class-activator.php` (Lines 52-53)
```php
require_once AQOP_PLUGIN_DIR . 'includes/authentication/class-roles-manager.php';
AQOP_Roles_Manager::create_roles();
```

---

## 🚀 Next Steps

### 1. Deactivate and Reactivate Plugin

**Via WordPress Admin:**
1. Go to Plugins → Installed Plugins
2. Find "Operation Platform Core"
3. Click "Deactivate"
4. Wait for confirmation
5. Click "Activate"
6. Roles will be created

**Via WP-CLI (if available):**
```bash
wp plugin deactivate aqop-core
wp plugin activate aqop-core
```

### 2. Verify Roles Created

**Via WordPress Admin:**
1. Go to Users → Add New
2. Check the "Role" dropdown
3. Should see:
   - Administrator
   - Operation Admin ✅
   - Operation Manager ✅
   - AQ Supervisor ✅
   - AQ Agent ✅

**Via WP-CLI:**
```bash
wp role list
```

**Via Database:**
```sql
SELECT option_value FROM wp_options WHERE option_name = 'wp_user_roles';
```

### 3. Create Test Users

**Create one user for each role to test:**

**Agent Test User:**
- Username: `test_agent`
- Email: `agent@example.com`
- Role: AQ Agent

**Supervisor Test User:**
- Username: `test_supervisor`
- Email: `supervisor@example.com`
- Role: AQ Supervisor

**Manager Test User:**
- Username: `test_manager`
- Email: `manager@example.com`
- Role: Operation Manager

### 4. Test React Frontend

1. **Login as Agent:**
   - Navigate to: `http://localhost:5174/login`
   - Login with agent credentials
   - Should see: "My Leads" page
   - Should only see assigned leads

2. **Login as Manager:**
   - Navigate to: `http://localhost:5174/login`
   - Login with manager credentials
   - Should see: "All Leads" + "Analytics"
   - Should see all leads system-wide

---

## 🔐 Role Permissions Matrix

| Capability | Admin | Manager | Supervisor | Agent |
|------------|-------|---------|------------|-------|
| Full WP Admin | ✅ | ❌ | ❌ | ❌ |
| View Control Center | ✅ | ✅ | ✅ | ❌ |
| Manage Operation | ✅ | ❌ | ❌ | ❌ |
| View Event Logs | ✅ | ✅ | ✅ | ❌ |
| Export Analytics | ✅ | ✅ | ✅ | ❌ |
| Manage Integrations | ✅ | ❌ | ❌ | ❌ |
| Manage Team Leads | ✅ | ✅ | ✅ | ❌ |
| Assign Leads | ✅ | ✅ | ✅ | ❌ |
| View Team Reports | ✅ | ✅ | ✅ | ❌ |
| Edit Own Leads | ✅ | ✅ | ✅ | ✅ |
| View Own Leads | ✅ | ✅ | ✅ | ✅ |
| Add Lead Notes | ✅ | ✅ | ✅ | ✅ |
| Update Lead Status | ✅ | ✅ | ✅ | ✅ |

---

## 🔍 Troubleshooting

### Issue: Roles Not Created After Reactivation

**Solution 1: Check if roles already exist**
```php
// In WordPress admin, run this in a plugin or theme:
$roles = wp_roles()->role_names;
print_r($roles);
```

**Solution 2: Manually trigger role creation**
```php
// Add this to functions.php temporarily:
add_action('init', function() {
    require_once WP_PLUGIN_DIR . '/aqop-core/includes/authentication/class-roles-manager.php';
    AQOP_Roles_Manager::create_roles();
});
```

**Solution 3: Delete and recreate**
```php
// Remove old roles first
AQOP_Roles_Manager::remove_roles();
// Then create new ones
AQOP_Roles_Manager::create_roles();
```

### Issue: User Can't Login to React Frontend

**Check:**
1. User has correct role (aq_agent, aq_supervisor, operation_manager, operation_admin, or administrator)
2. JWT plugin is activated
3. CORS is configured correctly
4. API endpoint is accessible

---

## ✅ Verification Checklist

After reactivating the plugin:

- [ ] 4 roles visible in Users → Add New → Role dropdown
- [ ] Can create user with "AQ Agent" role
- [ ] Can create user with "AQ Supervisor" role
- [ ] Can create user with "Operation Manager" role
- [ ] Can create user with "Operation Admin" role
- [ ] Agent can login to React frontend
- [ ] Manager can login to React frontend
- [ ] Agent sees only "My Leads"
- [ ] Manager sees "All Leads" + "Analytics"

---

## 📊 Before vs After

### Before Update:
```
✅ operation_admin
✅ operation_manager
❌ aq_supervisor (MISSING)
❌ aq_agent (MISSING)
```

### After Update:
```
✅ operation_admin
✅ operation_manager
✅ aq_supervisor (ADDED)
✅ aq_agent (ADDED)
```

---

## 🎉 Summary

**Status:** ✅ **FIXED**

**Changes:** 
- Added `aq_supervisor` role with team management capabilities
- Added `aq_agent` role with agent-level access
- Updated `remove_roles()` to remove all 4 roles
- Updated `get_operation_roles()` to return all 4 roles

**Action Required:**
1. Deactivate AQOP Core plugin
2. Reactivate AQOP Core plugin
3. Verify roles are created
4. Create test users
5. Test React frontend

**No Code Errors:** ✅ All linter checks passed

---

**The plugin is now ready to create all 4 required roles on activation!** 🚀

**Next:** Deactivate and reactivate the AQOP Core plugin in WordPress admin.

