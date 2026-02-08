# 🛠️ فحص WhatsApp - Debugging Code

## كود PHP للفحص السريع

يمكنك إضافة هذا الكود مؤقتاً في `functions.php` أو ملف منفصل:

```php
<?php
// File: wp-content/debug-whatsapp.php
// Add this line in wp-config.php after ABSPATH:
// require_once( ABSPATH . 'debug-whatsapp.php' );

// Enable only for logged-in admins
if ( ! function_exists( 'debug_whatsapp_settings' ) ) {
    function debug_whatsapp_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access Denied' );
        }

        echo '<div style="padding: 20px; background: #f5f5f5; font-family: monospace;">';
        echo '<h2>WhatsApp Configuration Debug</h2>';
        
        // Check all WhatsApp options
        $whatsapp_options = array(
            'aqop_whatsapp_phone_id',
            'aqop_whatsapp_access_token',
            'aqop_whatsapp_business_id',
            'aqop_whatsapp_business_name',
            'aqop_whatsapp_webhook_token',
        );

        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr style="background: #333; color: white;">';
        echo '<th style="padding: 10px; text-align: left;">Option Name</th>';
        echo '<th style="padding: 10px; text-align: left;">Status</th>';
        echo '<th style="padding: 10px; text-align: left;">Value (masked)</th>';
        echo '</tr>';

        foreach ( $whatsapp_options as $option ) {
            $value = get_option( $option );
            $status = empty( $value ) ? '❌ EMPTY' : '✓ SET';
            $display = empty( $value ) ? 'N/A' : substr( $value, 0, 4 ) . 'XXXX' . substr( $value, -4 );
            
            echo '<tr style="border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 10px;">' . esc_html( $option ) . '</td>';
            echo '<td style="padding: 10px;">' . esc_html( $status ) . '</td>';
            echo '<td style="padding: 10px; font-family: monospace;">' . esc_html( $display ) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        // Check if WhatsApp class exists
        echo '<h3 style="margin-top: 20px;">Class Status</h3>';
        if ( class_exists( 'AQOP_WhatsApp_Integration' ) ) {
            echo '<p style="color: green;">✓ AQOP_WhatsApp_Integration class found</p>';
        } else {
            echo '<p style="color: red;">✗ AQOP_WhatsApp_Integration class NOT found</p>';
        }

        // Check database tables
        echo '<h3 style="margin-top: 20px;">Database Status</h3>';
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'aq_leads' => 'Leads Table',
            $wpdb->prefix . 'aq_leads_notes' => 'Notes Table',
        );

        foreach ( $tables as $table => $name ) {
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
            $status = $exists ? '✓ EXISTS' : '✗ MISSING';
            echo '<p>' . esc_html( $name ) . ': ' . esc_html( $status ) . '</p>';
        }

        echo '</div>';
    }
    add_action( 'wp_loaded', 'debug_whatsapp_settings' );
}
?>
```

**استخدام:**
1. أضف هذا الكود إلى ملف `functions.php`
2. أو أضفه إلى ملف جديد وأستدعه من `wp-config.php`
3. اذهب إلى أي صفحة WordPress
4. ستظهر معلومات التشخيص في الأسفل

---

## JavaScript Console Code

يمكنك تشغيل هذا الكود في Console (F12):

```javascript
// 1. Check AJAX Configuration
console.group('AQOP Configuration');
console.log('AJAX URL:', aqopLeads?.ajaxurl);
console.log('API Root:', aqopLeads?.root);
console.log('Nonce:', aqopLeads?.nonce ? 'Set ✓' : 'Missing ✗');
console.log('Strings:', aqopLeads?.strings);
console.groupEnd();

// 2. Try to Send Test Message
console.group('Send Test Message');

let leadId = new URLSearchParams(window.location.search).get('lead_id');
console.log('Lead ID from URL:', leadId);

if (leadId) {
    let testData = {
        action: 'aqop_send_whatsapp_message',
        lead_id: leadId,
        message: 'Test message from console',
        nonce: aqopLeads?.nonce
    };
    
    console.log('Sending:', testData);
    
    fetch(aqopLeads?.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(testData)
    })
    .then(response => response.json())
    .then(data => console.log('Response:', data))
    .catch(error => console.error('Error:', error));
} else {
    console.log('No lead_id in URL');
}

console.groupEnd();

// 3. Check Network Requests
console.log('Check the "Network" tab to see AJAX requests');
```

**استخدام:**
1. افتح صفحة Lead Detail
2. اضغط F12 لفتح Developer Tools
3. اذهب إلى Console tab
4. انسخ الكود أعلاه والصقه
5. اضغط Enter
6. شاهد المعلومات التي تظهر

---

## WP-CLI Command (إذا كان متوفراً)

إذا كان لديك WP-CLI:

```bash
# 1. الحصول على جميع خيارات WhatsApp
wp option list --search='aqop_whatsapp'

# 2. التحقق من قيمة معينة
wp option get aqop_whatsapp_phone_id

# 3. تعيين قيمة جديدة
wp option set aqop_whatsapp_phone_id 'YOUR_NEW_ID'

# 4. حذف خيار
wp option delete aqop_whatsapp_phone_id

# 5. الحصول على معلومات الموقع
wp option get siteurl
wp option get home
```

---

## SQL Query (في PhpMyAdmin)

```sql
-- 1. عرض جميع إعدادات WhatsApp
SELECT * FROM wp_options 
WHERE option_name LIKE 'aqop_whatsapp%' 
OR option_name LIKE 'aqop_%token%';

-- 2. عرض جميع الإعدادات المتعلقة بـ AQOP
SELECT * FROM wp_options 
WHERE option_name LIKE 'aqop_%' 
ORDER BY option_name;

-- 3. عرض معلومات الموقع
SELECT option_name, option_value FROM wp_options 
WHERE option_name IN ('siteurl', 'home', 'admin_email');

-- 4. معلومات عن آخر عميل تم إنشاؤه
SELECT * FROM wp_aq_leads 
ORDER BY id DESC 
LIMIT 5;

-- 5. معلومات عن الملاحظات
SELECT l.id, l.name, l.whatsapp, COUNT(n.id) as note_count
FROM wp_aq_leads l
LEFT JOIN wp_aq_leads_notes n ON l.id = n.lead_id
GROUP BY l.id
ORDER BY l.id DESC
LIMIT 10;
```

---

## PHP Function للاختبار

أضف هذا الكود مؤقتاً في ملف:

```php
<?php
/**
 * Test WhatsApp Configuration
 */
function test_whatsapp_config() {
    echo "=== WhatsApp Configuration Test ===\n\n";
    
    // 1. Check Options
    echo "1. Checking Options:\n";
    $phone_id = get_option('aqop_whatsapp_phone_id');
    $access_token = get_option('aqop_whatsapp_access_token');
    $business_id = get_option('aqop_whatsapp_business_id');
    
    echo "   Phone ID: " . (empty($phone_id) ? 'EMPTY ❌' : 'SET ✓') . "\n";
    echo "   Access Token: " . (empty($access_token) ? 'EMPTY ❌' : 'SET ✓') . "\n";
    echo "   Business ID: " . (empty($business_id) ? 'EMPTY ❌' : 'SET ✓') . "\n";
    
    if (empty($phone_id) || empty($access_token) || empty($business_id)) {
        echo "\n❌ MISSING CONFIGURATION - Cannot proceed\n";
        return false;
    }
    
    // 2. Test Class
    echo "\n2. Checking WhatsApp Class:\n";
    if (!class_exists('AQOP_WhatsApp_Integration')) {
        echo "   ❌ Class not found\n";
        return false;
    }
    echo "   ✓ Class exists\n";
    
    // 3. Try to instantiate
    echo "\n3. Testing Instantiation:\n";
    try {
        $whatsapp = new AQOP_WhatsApp_Integration();
        echo "   ✓ Instance created successfully\n";
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    // 4. Test Connection
    echo "\n4. Testing Connection to Meta API:\n";
    $test_result = $whatsapp->test_connection();
    if (is_wp_error($test_result)) {
        echo "   ❌ Connection failed: " . $test_result->get_error_message() . "\n";
    } else {
        echo "   ✓ Connection successful\n";
    }
    
    echo "\n=== Test Complete ===\n";
    return true;
}

// Usage:
// test_whatsapp_config();
?>
```

---

## الخطوات المقترحة:

### خطوة 1: Run الـ Debug PHP
```
1. أضف الكود في functions.php
2. اذهب إلى WordPress dashboard
3. خذ صورة للمعلومات التي تظهر
```

### خطوة 2: Check الـ Database
```
1. اذهب إلى PhpMyAdmin
2. شغّل الـ SQL queries أعلاه
3. خذ صورة للنتائج
```

### خطوة 3: Test من Console
```
1. افتح صفحة Lead
2. اضغط F12
3. اذهب إلى Console
4. اشغّل الـ JavaScript code أعلاه
5. خذ صورة للنتائج
```

---

**بعد الفحص:**

أرسل لي:
- [ ] صورة من Debug Info
- [ ] نتائج SQL queries
- [ ] رسائل الخطأ من Console
- [ ] أي معلومات إضافية

وسأحل المشكلة بنسبة 100%! ✅

