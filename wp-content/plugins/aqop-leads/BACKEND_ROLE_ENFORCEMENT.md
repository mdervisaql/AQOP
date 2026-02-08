# Backend Role Enforcement - Implementation Complete

## ✅ Role-Based Permissions Added

Backend now properly enforces role-based access control matching the frontend expectations.

---

## 🔒 What Was Implemented

### **1. REST API Permission Callbacks**
**File:** `aqop-leads/api/class-leads-api.php`

#### Updated Permission Methods:
- `check_permission()` - General (all roles)
- `check_read_permission()` - Read single lead (with ownership check)
- `check_create_permission()` - Create leads (Manager+ only)
- `check_edit_permission()` - Edit leads (Owner or Manager+)
- `check_delete_permission()` - Delete leads (Manager+ only)

#### New Helper Methods:
- `is_agent()` - Check if user is agent (lowest role)
- `is_supervisor_or_above()` - Check supervisor or higher
- `is_manager_or_above()` - Check manager or higher

---

### **2. Auto-Filtering by Role**
**File:** `aqop-leads/api/class-leads-api.php`

#### In `get_leads()` method:
```php
// Auto-filter for agents: only show assigned leads
if ( $this->is_agent() ) {
    $args['assigned_to'] = get_current_user_id();
}
```

**Benefits:**
- ✅ Agents automatically see only their assigned leads
- ✅ No frontend filtering needed
- ✅ Secure at data layer
- ✅ Can't be bypassed

---

### **3. WordPress Admin Page Permissions**
**File:** `aqop-leads/admin/class-leads-admin.php`

#### Updated Menu Capabilities:
```php
// Dashboard - All AQOP roles
add_submenu_page(..., 'read', ...);

// All Leads - All AQOP roles  
add_submenu_page(..., 'read', ...);

// Settings - Admin only
add_submenu_page(..., 'manage_options', ...);
```

#### New Access Check Method:
```php
private function user_has_aqop_access() {
    $aqop_roles = array('administrator', 'operation_admin', 
                       'operation_manager', 'aq_supervisor', 'aq_agent');
    return !empty(array_intersect($aqop_roles, $user->roles));
}
```

---

## 📊 Permission Matrix

### REST API Endpoints

| Endpoint | Agent | Supervisor | Manager | Admin |
|----------|-------|------------|---------|-------|
| **GET /leads** | ✅ (assigned only) | ✅ (all) | ✅ (all) | ✅ (all) |
| **GET /leads/{id}** | ✅ (if assigned) | ✅ (all) | ✅ (all) | ✅ (all) |
| **POST /leads** | ❌ | ❌ | ✅ | ✅ |
| **PUT /leads/{id}** | ✅ (if assigned) | ✅ (all) | ✅ (all) | ✅ (all) |
| **DELETE /leads/{id}** | ❌ | ❌ | ✅ | ✅ |
| **GET /leads/stats** | ✅ (own) | ✅ (team) | ✅ (all) | ✅ (all) |
| **POST /leads/{id}/notes** | ✅ (if assigned) | ✅ (all) | ✅ (all) | ✅ (all) |

### WordPress Admin Pages

| Page | Agent | Supervisor | Manager | Admin |
|------|-------|------------|---------|-------|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **All Leads** | ✅ | ✅ | ✅ | ✅ |
| **Settings** | ❌ | ❌ | ❌ | ✅ |
| **Import/Export** | ✅ | ✅ | ✅ | ✅ |
| **API Docs** | ✅ | ✅ | ✅ | ✅ |

---

## 🔧 Implementation Details

### 1. Role Detection

#### Agent Check (Lowest Role):
```php
private function is_agent() {
    $user = wp_get_current_user();
    return in_array('aq_agent', $user->roles, true) 
        && ! in_array('aq_supervisor', $user->roles, true)
        && ! in_array('operation_manager', $user->roles, true)
        && ! in_array('operation_admin', $user->roles, true)
        && ! in_array('administrator', $user->roles, true);
}
```

#### Manager Check (High Role):
```php
private function is_manager_or_above() {
    $user = wp_get_current_user();
    $manager_roles = array('administrator', 'operation_admin', 'operation_manager');
    return !empty(array_intersect($manager_roles, $user->roles));
}
```

---

### 2. Query Filtering

#### Before (No Filtering):
```php
$result = AQOP_Leads_Manager::query_leads($args);
// Returns ALL leads for everyone
```

#### After (Role-Based Filtering):
```php
// Auto-filter for agents
if ( $this->is_agent() ) {
    $args['assigned_to'] = get_current_user_id();
}

$result = AQOP_Leads_Manager::query_leads($args);
// Agents: Only assigned leads
// Others: All leads
```

---

### 3. Ownership Checks

#### Get Single Lead:
```php
public function get_lead( $request ) {
    $lead = AQOP_Leads_Manager::get_lead( $lead_id );
    
    // Check ownership for agents
    if ( $this->is_agent() ) {
        if ( (int) $lead->assigned_to !== get_current_user_id() ) {
            return WP_Error('forbidden', 403);
        }
    }
    
    return $lead;
}
```

#### Update Lead:
```php
public function update_lead( $request ) {
    $lead = AQOP_Leads_Manager::get_lead( $lead_id );
    
    // Check ownership for agents
    if ( $this->is_agent() ) {
        if ( (int) $lead->assigned_to !== get_current_user_id() ) {
            return WP_Error('forbidden', 403);
        }
    }
    
    // Proceed with update
}
```

---

## 🎯 Security Benefits

### What's Now Protected:

#### 1. **Data Leakage Prevention**
- ✅ Agents can't query all leads via API
- ✅ Auto-filtering at database level
- ✅ Can't bypass with URL parameters

#### 2. **Unauthorized Actions Prevention**
- ✅ Agents can't create leads
- ✅ Agents can't delete leads
- ✅ Agents can't edit other agents' leads

#### 3. **Admin Interface Protection**
- ✅ Settings page remains admin-only
- ✅ Other pages accessible to all AQOP roles
- ✅ Menu items show based on role

---

## 🧪 Testing Scenarios

### Test 1: Agent Tries to View All Leads
**Request:**
```bash
curl GET "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads" \
  -H "Authorization: Bearer AGENT_TOKEN"
```

**Expected:**
- ✅ Returns only leads assigned to agent
- ✅ Other leads filtered out automatically
- ✅ No way to bypass

### Test 2: Agent Tries to View Unassigned Lead
**Request:**
```bash
curl GET "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads/999" \
  -H "Authorization: Bearer AGENT_TOKEN"
```
(Lead 999 is not assigned to this agent)

**Expected:**
```json
{
  "code": "forbidden",
  "message": "You can only view leads assigned to you.",
  "data": {
    "status": 403
  }
}
```

### Test 3: Agent Tries to Create Lead
**Request:**
```bash
curl POST "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads" \
  -H "Authorization: Bearer AGENT_TOKEN" \
  -d '{"name":"Test","email":"test@test.com","phone":"123"}'
```

**Expected:**
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to create leads.",
  "data": {
    "status": 403
  }
}
```

### Test 4: Manager Accesses All Leads
**Request:**
```bash
curl GET "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads" \
  -H "Authorization: Bearer MANAGER_TOKEN"
```

**Expected:**
- ✅ Returns ALL leads
- ✅ No filtering applied
- ✅ Full access

---

## 📋 Permission Callback Summary

| Permission Method | Who Can Pass | Used By |
|-------------------|--------------|---------|
| `check_permission()` | All AQOP roles | General endpoints |
| `check_read_permission()` | All AQOP roles | GET /leads/{id} |
| `check_create_permission()` | Manager+ only | POST /leads |
| `check_edit_permission()` | All AQOP roles | PUT /leads/{id} |
| `check_delete_permission()` | Manager+ only | DELETE /leads/{id} |

**Note:** Edit permission allows all roles, but ownership is checked in the method itself.

---

## 🔄 Data Flow with Role Filtering

### Agent Requests Leads:
```
Agent makes GET /leads
    ↓
check_permission() → ✅ Pass (is AQOP role)
    ↓
get_leads() method
    ↓
is_agent() → true
    ↓
Auto-add: assigned_to = agent_user_id
    ↓
query_leads(assigned_to=5)
    ↓
Returns only agent's leads ✅
```

### Manager Requests Leads:
```
Manager makes GET /leads
    ↓
check_permission() → ✅ Pass (is AQOP role)
    ↓
get_leads() method
    ↓
is_agent() → false
    ↓
No filtering added
    ↓
query_leads()
    ↓
Returns all leads ✅
```

---

## ✅ Verification Checklist

### REST API
- [x] General permission check allows all AQOP roles
- [x] Create permission restricted to Manager+
- [x] Delete permission restricted to Manager+
- [x] Edit permission allows all but checks ownership
- [x] Agents auto-filtered to assigned leads
- [x] Ownership checked in get_lead() for agents
- [x] Ownership checked in update_lead() for agents

### WordPress Admin
- [x] user_has_aqop_access() method added
- [x] Dashboard accessible to all AQOP roles
- [x] Leads list accessible to all AQOP roles
- [x] Settings restricted to admin only
- [x] Menu registration updated

### Helper Methods
- [x] is_agent() implemented
- [x] is_supervisor_or_above() implemented
- [x] is_manager_or_above() implemented

---

## 🎉 Status: COMPLETE ✅

Backend role enforcement is now fully implemented:
- ✅ Role-based REST API permissions
- ✅ Auto-filtering for agents
- ✅ Ownership checks for single lead access
- ✅ Create/Delete restricted to managers
- ✅ WordPress admin pages use proper capabilities
- ✅ Helper methods for role detection
- ✅ No linter errors

**Backend and frontend are now aligned!** 🔒

---

## 📚 Related Documentation

- Frontend Role Guards: `ROLE_BASED_ROUTING.md`
- Token Refresh: `TOKEN_REFRESH_IMPLEMENTATION.md`
- API Documentation: `API_ENDPOINTS_COMPLETE.md`

---

**Last Updated:** November 17, 2025
**Status:** Production Ready ✅

