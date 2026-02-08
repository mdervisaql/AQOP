# API Response Standardization - Complete

## ✅ All API Responses Now Consistent

Every endpoint now returns a predictable, standardized format that the frontend can rely on.

---

## 📋 Standard Response Format

### Success Response:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful message"
}
```

### Error Response:
```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

---

## 📊 All Endpoints Updated

### 1. GET /aqop/v1/leads
**Response:**
```json
{
  "success": true,
  "data": {
    "results": [...],
    "total": 150,
    "pages": 3,
    "page": 1,
    "per_page": 50
  }
}
```

### 2. GET /aqop/v1/leads/{id}
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    ...
  }
}
```

### 3. POST /aqop/v1/leads
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 124,
    "name": "New Lead",
    ...
  },
  "message": "Lead created successfully."
}
```

### 4. PUT /aqop/v1/leads/{id}
**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Updated Lead",
    ...
  },
  "message": "Lead updated successfully."
}
```

### 5. DELETE /aqop/v1/leads/{id}
**Response:**
```json
{
  "success": true,
  "data": {
    "deleted": true,
    "id": 123
  },
  "message": "Lead deleted successfully."
}
```

### 6. GET /aqop/v1/leads/stats
**Response:**
```json
{
  "success": true,
  "data": {
    "total_leads": 150,
    "pending_leads": 45,
    "contacted_leads": 60,
    "qualified_leads": 25,
    "converted_leads": 15,
    "lost_leads": 5,
    "by_status": { ... }
  }
}
```

### 7. POST /aqop/v1/leads/{id}/notes
**Response:**
```json
{
  "success": true,
  "data": {
    "note_id": 456
  },
  "message": "Note added successfully."
}
```

### 8. GET /aqop/v1/leads/{id}/notes
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "lead_id": 123,
      "user_id": 5,
      "note_text": "Called customer...",
      "created_at": "2025-11-17 10:30:00"
    }
  ]
}
```

### 9. GET /aqop/v1/leads/statuses
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "status_code": "pending",
      "status_name_en": "Pending",
      "status_name_ar": "معلق",
      "color": "#718096"
    }
  ]
}
```

### 10. GET /aqop/v1/leads/countries
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "country_code": "SA",
      "country_name_en": "Saudi Arabia",
      "country_name_ar": "السعودية"
    }
  ]
}
```

### 11. GET /aqop/v1/leads/sources
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "source_code": "facebook",
      "source_name": "Facebook Ads",
      "source_type": "paid"
    }
  ]
}
```

---

## 🎯 Benefits

### For Frontend Developers:
- ✅ Always check `response.success`
- ✅ Always access data via `response.data`
- ✅ Always display message via `response.message`
- ✅ Predictable structure
- ✅ Easy error handling

### For API Consistency:
- ✅ All endpoints follow same pattern
- ✅ TypeScript-friendly
- ✅ Self-documenting
- ✅ Easy to debug

### For Error Handling:
- ✅ Consistent error structure
- ✅ HTTP status codes in data.status
- ✅ Clear error messages
- ✅ Error codes for programmatic handling

---

## 🔧 Frontend Usage Pattern

### Standard Pattern:
```javascript
try {
  const response = await apiClient.get('/aqop/v1/leads');
  
  if (response.success && response.data) {
    const leads = response.data.results;
    // Use the data
  }
} catch (error) {
  console.error('Error:', error.message);
  // Handle error
}
```

### With All Endpoints:
```javascript
// Get leads
const { data: { results } } = await getLeads();

// Get single lead
const { data: lead } = await getLead(123);

// Create lead
const { data: newLead, message } = await createLead(leadData);
// message = "Lead created successfully."

// Update lead
const { data: updatedLead, message } = await updateLead(123, updates);
// message = "Lead updated successfully."

// Delete lead
const { data: { deleted, id }, message } = await deleteLead(123);
// message = "Lead deleted successfully."
```

---

## 📝 Response Structure Details

### Success Response Structure:
```typescript
{
  success: boolean;        // Always true for success
  data: object | array;    // The actual data
  message?: string;        // Optional success message
}
```

### Error Response Structure (WP_Error):
```typescript
{
  code: string;            // Error code (e.g., 'lead_not_found')
  message: string;         // Human-readable error message
  data: {
    status: number;        // HTTP status code (400, 401, 403, 404, 500)
  }
}
```

---

## 🧪 Testing Updated Responses

### Test 1: Get Leads
```bash
curl GET "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads" \
  -H "Authorization: Bearer TOKEN"
```

**Expected:**
```json
{
  "success": true,
  "data": {
    "results": [...],
    "total": 10,
    "pages": 1,
    "page": 1,
    "per_page": 50
  }
}
```

### Test 2: Create Lead
```bash
curl POST "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"name":"Test","email":"test@test.com","phone":"123"}'
```

**Expected:**
```json
{
  "success": true,
  "data": {
    "id": 999,
    "name": "Test",
    "email": "test@test.com",
    ...
  },
  "message": "Lead created successfully."
}
```

### Test 3: Error Response
```bash
curl GET "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads/99999" \
  -H "Authorization: Bearer TOKEN"
```

**Expected:**
```json
{
  "code": "lead_not_found",
  "message": "Lead not found.",
  "data": {
    "status": 404
  }
}
```

---

## ✅ Updated Methods

| Method | Old Format | New Format | Status |
|--------|------------|------------|--------|
| `get_leads()` | ✅ Already standardized | `{ success, data }` | ✅ |
| `get_lead()` | ✅ Already standardized | `{ success, data }` | ✅ |
| `create_lead()` | ❌ `{ message, lead }` | `{ success, data, message }` | ✅ Fixed |
| `update_lead()` | ✅ Already standardized | `{ success, data, message }` | ✅ |
| `delete_lead()` | ❌ `{ message, deleted, id }` | `{ success, data, message }` | ✅ Fixed |
| `get_stats()` | ✅ Already standardized | `{ success, data }` | ✅ |
| `add_note()` | ✅ Already standardized | `{ success, data, message }` | ✅ |
| `get_notes()` | ✅ Already standardized | `{ success, data }` | ✅ |
| `get_statuses()` | ❌ Raw array | `{ success, data }` | ✅ Fixed |
| `get_countries()` | ❌ Raw array | `{ success, data }` | ✅ Fixed |
| `get_sources()` | ❌ Raw array | `{ success, data }` | ✅ Fixed |

---

## 🔄 Migration Guide

### If You Have Custom Frontend Code:

#### Before:
```javascript
// get_statuses() returned raw array
const statuses = await apiClient.get('/aqop/v1/leads/statuses');
// statuses = [{id:1, name:...}, ...]
```

#### After:
```javascript
// Now returns standardized format
const response = await apiClient.get('/aqop/v1/leads/statuses');
const statuses = response.data;
// response = { success: true, data: [{...}] }
```

**Fix:** Update code to access `response.data` instead of using response directly.

---

## 📚 Best Practices

### Frontend Error Handling:
```javascript
async function fetchLeads() {
  try {
    const response = await apiClient.get('/aqop/v1/leads');
    
    if (response.success && response.data) {
      setLeads(response.data.results);
      return;
    }
    
    // Fallback for unexpected response
    setError('Unexpected response format');
  } catch (error) {
    // Network error or API error
    setError(error.message);
  }
}
```

### Success Message Display:
```javascript
const { data, message } = await createLead(leadData);
// Show success toast
showToast(message); // "Lead created successfully."
```

---

## ✅ Verification Checklist

- [x] get_leads() returns { success, data }
- [x] get_lead() returns { success, data }
- [x] create_lead() returns { success, data, message }
- [x] update_lead() returns { success, data, message }
- [x] delete_lead() returns { success, data, message }
- [x] get_stats() returns { success, data }
- [x] add_note() returns { success, data, message }
- [x] get_notes() returns { success, data }
- [x] get_statuses() returns { success, data }
- [x] get_countries() returns { success, data }
- [x] get_sources() returns { success, data }
- [x] All WP_Errors include status code
- [x] No linter errors

---

## 🎉 Status: COMPLETE ✅

All API endpoints now return consistent, standardized responses:
- ✅ Success: `{ success: true, data, message }`
- ✅ Error: `{ code, message, data: { status } }`
- ✅ 11 endpoints updated
- ✅ Fully documented
- ✅ Production ready

**Frontend can now handle all responses uniformly!** 📦✅

---

**Last Updated:** November 17, 2025
**Status:** Production Ready

