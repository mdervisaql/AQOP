# Airtable Sync Optimization - Fix Excessive Logging & Switch to Incremental Sync

**Date:** 2026-02-17  
**Status:** ✅ Completed

---

## Problem

The AQOP WordPress platform's Airtable sync was causing disk space issues:

- **Auto-sync** runs every 30 minutes via wp-cron
- Calls `sync_from_airtable()` which fetches **ALL 26,435 records** every time
- Each record triggers ~53 `error_log()` calls
- **Result:** ~1.4 million log lines every 30 minutes
- **Impact:** Server disk filled up

---

## Solution

### Change 1: Add Debug Flag to Logging

**File:** `wp-content/plugins/aqop-leads/includes/class-airtable-sync.php`

#### What was done:
1. Added `private $debug = false;` property to class
2. Wrapped **all 46** `error_log('[AQOP Sync...')` calls with `$this->debug &&`

#### Result:
- Logging is now **disabled by default**
- Can be enabled by setting `$debug = true` only when troubleshooting
- Reduces log output from **~1.4 million lines** to **0 lines** per sync run

---

### Change 2: Switch Auto-Sync to Incremental Smart Sync

**File:** `wp-content/plugins/aqop-leads/aqop-leads.php`

#### What changed:

**Before:**
```php
$sync = new AQOP_Airtable_Sync();
$result = $sync->sync_from_airtable(); // Fetches ALL 26,435 records
```

**After:**
```php
$sync = new AQOP_Airtable_Sync();

// Use incremental sync_chunk with smart sync filter
$offset = '';
$total_processed = 0;
$max_iterations = 100;

do {
    $result = $sync->sync_chunk($offset, 100, false); // false = smart sync
    
    $total_processed += $result['chunk_processed'];
    $total_created += $result['chunk_created'];
    $total_updated += $result['chunk_updated'];
    $total_marked += $result['chunk_marked'];
    $offset = $result['next_offset'];

} while (!$result['is_complete'] && $iteration < $max_iterations);
```

#### How it works:
1. **Smart Sync Filter:** `sync_chunk($offset, 100, false)` uses `filterByFormula` in Airtable API:
   ```
   OR(sync_with_aqop = FALSE(), sync_with_aqop = BLANK())
   ```
2. **Only fetches unsynced records** (not all 26,435)
3. **Marks records as synced** after successful processing
4. **Next sync:** Only fetches newly added/unsynced records

#### Impact:
- **First run:** May process many records (all unsynced)
- **Subsequent runs:** Only processes new records (could be 0-100)
- **Massive reduction** in API calls, processing time, and log output

---

## Change 3: Database Configuration (Separate Task)

After deploying the code changes, enable smart sync in the database:

```sql
INSERT INTO wp_options (option_name, option_value, autoload) 
VALUES ('aqop_airtable_smart_sync_enabled', '1', 'yes') 
ON DUPLICATE KEY UPDATE option_value = '1';
```

**Note:** This is already implemented in the sync logic, but needs to be verified in the database.

---

## What NOT to Change

✅ **Manual full sync still works** - `sync_from_airtable()` method remains unchanged  
✅ **Cron schedule unchanged** - 30-minute interval is fine with incremental sync  
✅ **No changes to other error_log calls** - Only `[AQOP Sync` prefix was wrapped  
✅ **Auto-sync summary logs remain** - They only fire once per sync run (minimal impact)

---

## Expected Results

### Before:
- **Records fetched per sync:** 26,435
- **Log lines per sync:** ~1.4 million
- **Disk usage:** Growing rapidly
- **Server impact:** High

### After:
- **Records fetched per sync:** Only unsynced records (0-100 typically)
- **Log lines per sync:** ~0 (debug disabled)
- **Disk usage:** Stable
- **Server impact:** Minimal

---

## Deployment Steps

1. ✅ Update `class-airtable-sync.php` (debug flag added)
2. ✅ Update `aqop-leads.php` (incremental sync implemented)
3. 🔄 Deploy to server
4. 🔄 Verify `aqop_airtable_smart_sync_enabled` option is set to `1`
5. 🔄 Monitor next auto-sync run (check logs for "incremental sync")

---

## Files Modified

- `wp-content/plugins/aqop-leads/includes/class-airtable-sync.php`
- `wp-content/plugins/aqop-leads/aqop-leads.php`

---

## Testing Checklist

- [ ] Manual full sync still works from admin panel
- [ ] Auto-sync runs every 30 minutes
- [ ] Auto-sync only processes unsynced records
- [ ] Records are marked as synced after processing
- [ ] Debug logging is disabled by default
- [ ] Server disk usage remains stable
