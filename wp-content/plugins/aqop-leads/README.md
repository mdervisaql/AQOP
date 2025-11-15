# Operation Platform - Leads Module

**Version:** 1.0.0  
**Requires:** Operation Platform Core 1.0.0+  
**Author:** Muhammed Derviş

## Description

Comprehensive leads management system built on Operation Platform Core. Provides complete lead lifecycle management with analytics, Airtable synchronization, and automated notifications.

## Features

### Lead Management
- ✅ Complete CRUD operations
- ✅ Status tracking (Pending → Contacted → Qualified → Converted → Lost)
- ✅ Priority levels (Low, Medium, High, Urgent)
- ✅ Multi-channel contact info (Email, Phone, WhatsApp)
- ✅ Country and source tracking
- ✅ Campaign assignment

### Analytics & Tracking
- ✅ All operations logged via Event Logger
- ✅ Complete audit trail
- ✅ Performance metrics
- ✅ Conversion tracking
- ✅ Source analytics

### Integrations
- ✅ Auto-sync to Airtable
- ✅ Dropbox file storage
- ✅ Telegram notifications
- ✅ Webhook support

### User Features
- ✅ Lead assignment to users
- ✅ Notes and comments
- ✅ Custom fields support
- ✅ Activity timeline

## Installation

1. **Install Operation Platform Core** (required)
2. Upload `aqop-leads` folder to `/wp-content/plugins/`
3. Activate through WordPress admin
4. Tables will be created automatically

## Database Schema

### Tables Created (5)

1. **`wp_aq_leads`** - Main leads table
2. **`wp_aq_leads_status`** - Lead statuses (5 pre-loaded)
3. **`wp_aq_leads_sources`** - Lead sources (6 pre-loaded)
4. **`wp_aq_leads_campaigns`** - Marketing campaigns
5. **`wp_aq_leads_notes`** - Lead notes/comments

### Pre-loaded Data

**Statuses:**
- Pending (معلق)
- Contacted (تم الاتصال)
- Qualified (مؤهل)
- Converted (محول)
- Lost (خاسر)

**Sources:**
- Facebook Ads
- Google Ads
- Instagram Ads
- Website Form
- Referral
- Direct Contact

## Usage

### Create a Lead

```php
$lead_id = AQOP_Leads_Manager::create_lead( array(
    'name'        => 'John Doe',
    'email'       => 'john@example.com',
    'phone'       => '+1234567890',
    'whatsapp'    => '+1234567890',
    'country_id'  => 1,  // Saudi Arabia
    'source_id'   => 1,  // Facebook
    'status_id'   => 1,  // Pending
    'priority'    => 'high',
    'notes'       => 'Initial contact via Facebook',
) );
```

### Update Lead

```php
AQOP_Leads_Manager::update_lead( $lead_id, array(
    'status_id' => 2,  // Contacted
    'notes'     => 'Follow-up call completed',
) );
```

### Assign Lead

```php
AQOP_Leads_Manager::assign_lead( $lead_id, $user_id );
```

### Change Status

```php
AQOP_Leads_Manager::change_status( $lead_id, $new_status_id );
```

### Add Note

```php
AQOP_Leads_Manager::add_note( $lead_id, 'Customer interested in product X' );
```

### Query Leads

```php
$results = AQOP_Leads_Manager::query_leads( array(
    'status'   => 1,  // Pending
    'country'  => 1,  // Saudi Arabia
    'priority' => 'high',
    'limit'    => 50,
) );

foreach ( $results['results'] as $lead ) {
    echo $lead->name;
}
```

## Integration with Core

### Event Logging

All operations automatically log events:
- `lead_created`
- `lead_updated`
- `lead_deleted`
- `lead_assigned`
- `lead_status_changed`
- `lead_note_added`

### Airtable Sync

Leads auto-sync to Airtable on:
- Creation
- Update
- Status change
- Assignment

### Action Hooks

```php
// After lead created
add_action( 'aqop_lead_created', 'my_handler', 10, 2 );

// After status changed
add_action( 'aqop_lead_status_changed', 'my_handler', 10, 3 );
```

## Requirements

- **Operation Platform Core:** 1.0.0+
- **WordPress:** 5.8+
- **PHP:** 7.4+

## License

GPL v2 or later

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

