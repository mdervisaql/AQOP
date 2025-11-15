<?php
/**
 * Operation Platform Core - Configuration Example
 *
 * Add these constants to your wp-config.php file to enable integrations.
 * Place them before the line: "That's all, stop editing! Happy publishing."
 *
 * @package AQOP_Core
 * @since   1.0.0
 */

// ============================================================================
// AIRTABLE INTEGRATION
// ============================================================================

/**
 * Airtable API Key
 *
 * Get your API key from: https://airtable.com/account
 * Format: key... (starts with "key")
 */
define( 'AQOP_AIRTABLE_API_KEY', 'keyXXXXXXXXXXXXXX' );

/**
 * Airtable Base ID
 *
 * Find in your Airtable base URL: https://airtable.com/appXXXXXXXXXXXXXX
 * Format: app... (starts with "app")
 */
define( 'AQOP_AIRTABLE_BASE_ID', 'appXXXXXXXXXXXXXX' );

/**
 * Airtable Table Name
 *
 * The name of your Airtable table (case-sensitive)
 * Example: 'Leads', 'Customers', 'Training Sessions'
 */
define( 'AQOP_AIRTABLE_TABLE_NAME', 'Leads' );

// ============================================================================
// DROPBOX INTEGRATION
// ============================================================================

/**
 * Dropbox Access Token
 *
 * Get your access token from: https://www.dropbox.com/developers/apps
 * 1. Create an app
 * 2. Generate access token
 * Format: sl.XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 */
define( 'AQOP_DROPBOX_ACCESS_TOKEN', 'sl.XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );

// ============================================================================
// TELEGRAM INTEGRATION
// ============================================================================

/**
 * Telegram Bot Token
 *
 * Get your bot token from @BotFather on Telegram:
 * 1. Start chat with @BotFather
 * 2. Send /newbot
 * 3. Follow instructions
 * 4. Copy the token
 * Format: 123456789:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 */
define( 'AQOP_TELEGRAM_BOT_TOKEN', '123456789:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX' );

// ============================================================================
// OPTIONAL: PER-MODULE CONFIGURATION
// ============================================================================

/**
 * You can override configurations per module:
 */

// Leads Module - Specific Airtable Table
// define( 'AQOP_LEADS_AIRTABLE_TABLE', 'Leads' );

// Training Module - Specific Airtable Table
// define( 'AQOP_TRAINING_AIRTABLE_TABLE', 'Training Sessions' );

// ============================================================================
// SECURITY NOTES
// ============================================================================

/*
 * IMPORTANT SECURITY RECOMMENDATIONS:
 *
 * 1. Never commit wp-config.php to version control
 * 2. Keep your API keys secret
 * 3. Rotate keys regularly
 * 4. Use environment-specific keys (dev, staging, production)
 * 5. Monitor API usage in respective dashboards
 * 6. Set up IP restrictions where possible
 *
 * For production environments, consider using:
 * - Environment variables
 * - Secret management services
 * - Encrypted configuration files
 */

// ============================================================================
// TESTING CONFIGURATION
// ============================================================================

/*
 * To test if your configuration is working:
 *
 * 1. Airtable Test:
 *    $status = AQOP_Integrations_Hub::check_integration_health( 'airtable' );
 *    var_dump( $status );
 *
 * 2. Dropbox Test:
 *    $status = AQOP_Integrations_Hub::check_integration_health( 'dropbox' );
 *    var_dump( $status );
 *
 * 3. Telegram Test:
 *    $status = AQOP_Integrations_Hub::check_integration_health( 'telegram' );
 *    var_dump( $status );
 *
 * Or check from WordPress admin:
 * Operation Center → Integrations → Health Check
 */

