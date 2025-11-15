# Operation Platform Core

**Version:** 1.0.0  
**Author:** Muhammed Derviş  
**Website:** https://aqleeat.com

## Description

Operation Platform Core is the foundation of a comprehensive WordPress-based operations management system. It provides essential infrastructure for building powerful operational modules including event logging, notifications, integrations, and analytics.

## Features

### Core Infrastructure

- **Event System**: Centralized logging for all platform activities
- **Notification Engine**: Dynamic, rule-based notification system
- **Integration Hub**: Connect with Airtable, Dropbox, Telegram, and more
- **Security Layer**: Multi-layer security for frontend and backend
- **Control Center**: Comprehensive dashboard for operational oversight

### Key Capabilities

- Real-time event tracking across all modules
- Dynamic notification rules with drag-and-drop interface
- Seamless third-party integrations
- Advanced analytics and reporting
- Role-based access control
- REST API for external integrations

## System Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **PHP Extensions**: json, mysqli, curl

## Installation

1. Upload the `aqop-core` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create required database tables
4. Access the Operation Control Center from the admin menu

## Module Architecture

Operation Platform Core follows a modular architecture where independent modules can be built on top of the core foundation:

```
Operation Platform
├── aqop-core (Foundation)
│   ├── Event System
│   ├── Notification Engine
│   ├── Integration Hub
│   └── Control Center
└── Modules (Independent)
    ├── aqop-leads
    ├── aqop-training
    └── aqop-kb
```

## For Developers

### Building Modules

To create a new module for Operation Platform:

1. Follow WordPress plugin standards
2. Depend on `aqop-core` as a requirement
3. Use the Event System for activity logging
4. Leverage the Integration Hub for third-party connections
5. Follow naming conventions: `aq_{module}_{type}`

### Event System Usage

```php
// Log an event
AQOP_Event_Logger::log('module_name', 'event_type', 'object_type', $object_id, [
    'custom_field' => 'value',
    'country' => 'SA'
]);

// Retrieve events
$events = AQOP_Event_Logger::get_events('lead', $lead_id);
```

### Hooks and Filters

The plugin provides numerous hooks and filters for customization:

- `aqop_core_loaded` - Fires when the core is initialized
- `aqop_event_logged` - Fires when an event is logged
- `aqop_before_save_lead` - Example module-specific hook

## Database Structure

The plugin creates optimized database tables following star schema principles:

- `wp_aq_events_log` - Central event logging table
- `wp_aq_notification_rules` - Dynamic notification rules
- Additional dimension tables for analytics

## Support

For support, documentation, and updates:

- **Documentation**: [Coming Soon]
- **Issues**: Internal tracking system
- **Updates**: Automatic updates through WordPress

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Muhammed Derviş

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

Built with WordPress standards and best practices in mind. Designed for enterprise-level operations management.

---

**Operation Platform Core** - The Foundation for Operational Excellence

