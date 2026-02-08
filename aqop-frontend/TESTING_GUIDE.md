# Agent Dashboard - Testing Guide

## 🚀 Quick Start

### Prerequisites
1. WordPress backend running at `http://localhost:8888/aqleeat-operation`
2. JWT Auth plugin activated
3. AQOP Leads plugin activated
4. Test agent user created

### Start Frontend
```bash
cd /Users/mfarrag/Documents/Operation/aql-leads/aqleeat-operation/aqop-frontend
npm run dev
```

Open: `http://localhost:5174`

---

## 🧪 Test Scenarios

### Scenario 1: Agent Login Flow
**Objective:** Verify authentication works correctly

1. Navigate to `http://localhost:5174/login`
2. Enter agent credentials:
   - Username: `test_agent`
   - Password: your password
3. Click "Sign in"

**Expected Results:**
- ✅ Loading state shows "Signing in..."
- ✅ Redirect to `/dashboard` on success
- ✅ User name displayed in header
- ✅ Role badge shows "AQ_AGENT"
- ✅ No errors in console

**If Failed:**
- Check JWT plugin is activated
- Verify CORS allows `http://localhost:5174`
- Check user has `aq_agent` role
- Check browser console for errors

---

### Scenario 2: View Dashboard
**Objective:** Verify dashboard displays correctly

1. After login, should be on `/dashboard`
2. Observe the dashboard

**Expected Results:**
- ✅ Welcome message with user name
- ✅ 4 statistics cards displayed
- ✅ "My Leads" section visible
- ✅ Recent leads (if any assigned)
- ✅ "View All" button works
- ✅ Navigation menu in header

**Statistics Cards:**
1. My Leads - Total count
2. Pending - Count of pending leads
3. Contacted - Count of contacted leads
4. Converted - Count of converted leads

---

### Scenario 3: View My Leads
**Objective:** Test leads list functionality

1. Click "My Leads" in navigation or "View All" button
2. Should navigate to `/leads`

**Expected Results:**
- ✅ Page title: "My Leads"
- ✅ Search bar visible
- ✅ Filter dropdowns (Status, Priority)
- ✅ Clear Filters button
- ✅ Leads displayed as cards
- ✅ Lead count shown
- ✅ Refresh button works

**Test Filters:**
1. Enter text in search box → leads filter
2. Select "Pending" in status → only pending leads
3. Select "High" in priority → only high priority
4. Click "Clear Filters" → all filters reset

---

### Scenario 4: Search Leads
**Objective:** Test search functionality

1. On `/leads` page
2. Type in search box: lead name, email, or phone
3. Observe results update

**Test Cases:**
- Search by name: "John"
- Search by email: "john@example.com"
- Search by phone: "555"

**Expected Results:**
- ✅ Results filter in real-time
- ✅ No results shows empty state
- ✅ Clear search shows all leads again

---

### Scenario 5: Filter by Status
**Objective:** Test status filtering

1. On `/leads` page
2. Click status dropdown
3. Select "Contacted"

**Expected Results:**
- ✅ Only contacted leads visible
- ✅ Lead count updates
- ✅ Status badge on cards shows "Contacted"
- ✅ Clear filters resets

**Test All Statuses:**
- [ ] Pending
- [ ] Contacted
- [ ] Qualified
- [ ] Converted
- [ ] Lost

---

### Scenario 6: Filter by Priority
**Objective:** Test priority filtering

1. On `/leads` page
2. Click priority dropdown
3. Select "High"

**Expected Results:**
- ✅ Only high priority leads visible
- ✅ Lead count updates
- ✅ Priority badge shows "High"
- ✅ Clear filters resets

**Test All Priorities:**
- [ ] Low
- [ ] Medium
- [ ] High
- [ ] Urgent

---

### Scenario 7: View Lead Details
**Objective:** Test lead detail page

1. On `/leads` page
2. Click "View Details" on any lead
3. Should navigate to `/leads/{id}`

**Expected Results:**
- ✅ Lead name as page title
- ✅ Status and priority badges in header
- ✅ Contact information section visible
- ✅ Email link opens mail client
- ✅ Phone link triggers call
- ✅ WhatsApp link opens in new tab
- ✅ Lead details section shows all info
- ✅ Notes section visible
- ✅ Status dropdown in sidebar
- ✅ Quick actions buttons work
- ✅ Back button returns to `/leads`

**Verify Contact Links:**
1. Click email → opens default mail client
2. Click phone → triggers phone call (mobile)
3. Click WhatsApp → opens WhatsApp web

---

### Scenario 8: Add Note to Lead
**Objective:** Test adding notes functionality

1. On lead detail page `/leads/{id}`
2. Scroll to "Notes & Activity" section
3. Type in note textarea: "Called customer, interested in service"
4. Click "Add Note"

**Expected Results:**
- ✅ Button shows "Adding..." during request
- ✅ Note appears in notes list
- ✅ Note shows your name as author
- ✅ Timestamp is current
- ✅ Textarea clears after adding
- ✅ No errors in console

**Error Cases:**
- Empty note → button disabled
- API error → alert shown
- Network error → error message

---

### Scenario 9: Update Lead Status
**Objective:** Test status update functionality

1. On lead detail page `/leads/{id}`
2. Find "Update Status" in sidebar
3. Select different status from dropdown
4. Click "Update Status"

**Expected Results:**
- ✅ Button shows "Updating..." during request
- ✅ Status badge in header updates
- ✅ Success alert shown
- ✅ Page data refreshes
- ✅ New status saved in database

**Test Status Changes:**
- Pending → Contacted
- Contacted → Qualified
- Qualified → Converted
- Any → Lost

---

### Scenario 10: Refresh Data
**Objective:** Test data refresh functionality

1. On `/leads` page
2. Click refresh button (circular arrow icon)

**Expected Results:**
- ✅ Loading indicator shows briefly
- ✅ Leads list updates
- ✅ New leads appear if added
- ✅ Updated leads show changes

---

### Scenario 11: Navigation Flow
**Objective:** Test complete navigation

**Flow:**
1. Login → Dashboard
2. Dashboard → My Leads
3. My Leads → Lead Detail
4. Lead Detail → Back to My Leads
5. My Leads → Dashboard
6. Dashboard → Logout → Login

**Expected Results:**
- ✅ All transitions smooth
- ✅ No broken links
- ✅ Breadcrumbs work
- ✅ Back button works
- ✅ URLs update correctly

---

### Scenario 12: Empty States
**Objective:** Test UI when no data

**Test Cases:**

1. **No Leads Assigned**
   - Login as agent with no leads
   - Dashboard shows empty state
   - Message: "You have no leads assigned yet"

2. **No Notes on Lead**
   - View lead with no notes
   - Notes section shows: "No notes yet"

3. **No Search Results**
   - Search for non-existent lead
   - Shows: "No leads found"
   - Suggestion: "Try adjusting your filters"

**Expected Results:**
- ✅ Friendly empty state messages
- ✅ Helpful suggestions
- ✅ No broken UI
- ✅ Icons display correctly

---

### Scenario 13: Error Handling
**Objective:** Test error scenarios

**Test Cases:**

1. **Invalid Lead ID**
   - Navigate to `/leads/99999`
   - Should show error: "Lead not found"
   - Back button works

2. **Network Error**
   - Disconnect internet
   - Try to load leads
   - Should show error message
   - Retry button works

3. **Auth Token Expired**
   - Wait 15+ minutes
   - Try to fetch data
   - Should redirect to login

4. **Permission Denied**
   - Try to view lead not assigned to you
   - Should show error or redirect

**Expected Results:**
- ✅ Clear error messages
- ✅ No crashes
- ✅ User can recover
- ✅ Helpful instructions

---

### Scenario 14: Responsive Design
**Objective:** Test mobile/tablet layouts

**Test Viewports:**
1. Mobile (375px)
2. Tablet (768px)
3. Desktop (1920px)

**Check:**
- [ ] Login form fits on screen
- [ ] Dashboard cards stack properly
- [ ] Lead cards are readable
- [ ] Navigation menu accessible
- [ ] Buttons are tappable (min 44px)
- [ ] Text is readable
- [ ] Forms work on mobile
- [ ] No horizontal scroll

---

### Scenario 15: Logout Flow
**Objective:** Test logout functionality

1. Click "Logout" button in header
2. Observe behavior

**Expected Results:**
- ✅ API call to blacklist token
- ✅ Redirect to `/login`
- ✅ LocalStorage cleared
- ✅ Can't access `/dashboard` without login
- ✅ Can't access `/leads` without login
- ✅ Must login again

---

## 🐛 Common Issues & Solutions

### Issue: "CORS Error"
**Symptoms:** Can't fetch data, CORS errors in console

**Solution:**
1. Check JWT plugin CORS settings
2. Verify port is `5174` in both places:
   - JWT plugin: `http://localhost:5174`
   - Vite runs on: port `5174`
3. Restart WordPress

### Issue: "401 Unauthorized"
**Symptoms:** Can't fetch leads, unauthorized errors

**Solution:**
1. Check token in localStorage (DevTools → Application → Local Storage)
2. Verify token is valid (not expired)
3. Try logging out and in again
4. Check user role has permissions

### Issue: "Leads Not Loading"
**Symptoms:** Empty state when leads should exist

**Solution:**
1. Check API endpoint: `http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads`
2. Test endpoint in browser or Postman
3. Verify leads are assigned to current user
4. Check browser console for errors
5. Check network tab for failed requests

### Issue: "Can't Add Notes"
**Symptoms:** Error when adding notes

**Solution:**
1. Verify lead is assigned to you
2. Check permissions for current role
3. Verify API endpoint exists
4. Check note is not empty
5. Look at network response for details

### Issue: "Status Not Updating"
**Symptoms:** Status dropdown doesn't save

**Solution:**
1. Verify you have permission to update
2. Check lead is assigned to you
3. Verify status code is valid
4. Check API response in console
5. Refresh page to see if it saved

---

## ✅ Final Checklist

Before considering testing complete, verify:

### Authentication
- [ ] Can login with valid credentials
- [ ] Can't login with invalid credentials
- [ ] Token stored correctly
- [ ] Can logout successfully
- [ ] Can't access protected routes without auth

### Dashboard
- [ ] Statistics display correctly
- [ ] Recent leads show
- [ ] Navigation works
- [ ] Role displayed correctly
- [ ] User name shows

### My Leads
- [ ] Only assigned leads visible
- [ ] Search works
- [ ] Filters work (status, priority)
- [ ] Clear filters works
- [ ] Refresh works
- [ ] Lead count accurate

### Lead Detail
- [ ] All information displays
- [ ] Contact links work
- [ ] Can add notes
- [ ] Can update status
- [ ] Quick actions work
- [ ] Back navigation works

### Error Handling
- [ ] Invalid lead ID handled
- [ ] Network errors handled
- [ ] Auth errors handled
- [ ] Empty states display

### UI/UX
- [ ] No console errors
- [ ] Loading states work
- [ ] Responsive on mobile
- [ ] Buttons are accessible
- [ ] Forms validate input

---

## 📊 Performance Checklist

- [ ] Page loads in < 2 seconds
- [ ] API calls return in < 1 second
- [ ] No unnecessary re-renders
- [ ] Images load properly
- [ ] No memory leaks
- [ ] Smooth animations
- [ ] No layout shifts

---

## 🎉 Testing Complete!

Once all scenarios pass, the Agent Dashboard is ready for production use!

**Need help?** Check `AGENT_DASHBOARD.md` for detailed documentation.

---

**Happy Testing!** 🚀

