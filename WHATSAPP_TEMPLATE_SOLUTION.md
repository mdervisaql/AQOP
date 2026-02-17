# 📨 حل استخدام Template Messages بدل Text Messages

## المشكلة والحل:

```
❌ المشكلة: لا يمكن إرسال رسائل نصية عادية إلى أرقام لم ترسل رسالة أولاً
✅ الحل: استخدام Template Messages (رسائل القوالب)
```

---

## 🛠️ الخطوات الكاملة:

### الخطوة 1: إنشاء Template في Meta

اذهب إلى: https://www.facebook.com/business/tools/whatsapp

```
1. اختر: WhatsApp Business Account
2. اذهب إلى: Message Templates
3. اضغط: Create Template

ملئ البيانات:
├─ Template Name: "hello"
├─ Category: "Marketing"
├─ Language: "Arabic"
└─ Body:
   السلام عليكم ورحمة الله وبركاته
   
   شكراً لتواصلك معنا.
   سيتم التواصل معك قريباً.
```

### الخطوة 2: استخدام Template من الكود

**الكود الجاهز موجود بالفعل في:**
```
wp-content/plugins/aqop-leads/includes/integrations/class-whatsapp-integration.php
```

**الدالة:**
```php
public function send_template($phone_number, $template_name, $language = 'en_US', $components = array())
```

### الخطوة 3: تفعيل استخدام Templates من الواجهة

**ملف الواجهة:**
```
wp-content/plugins/aqop-leads/admin/views/lead-detail.php
```

**تعديل مقترح:**

بدل إرسال Text Message مباشرة، اسأل المستخدم:
```
[ ] إرسال رسالة نصية عادية (فقط للمحادثات السابقة)
[ ] إرسال رسالة قالب (للأرقام الجديدة) ← الخيار الأفضل
```

---

## 💻 كود PHP - اختبر هذا:

أضف هذا الملف في: `/wp-content/test-whatsapp-template.php`

```php
<?php
/**
 * Test WhatsApp Template Sending
 */

// تحميل WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

// التحقق من الصلاحيات
if (!current_user_can('manage_options')) {
    wp_die('Access Denied');
}

// استدعاء الكلاس
require_once(WP_CONTENT_DIR . '/plugins/aqop-leads/includes/integrations/class-whatsapp-integration.php');

$whatsapp = new AQOP_WhatsApp_Integration();

// إعدادات الاختبار
$phone = '+201023894135';  // الرقم المراد الإرسال إليه
$template_name = 'hello';  // اسم الـ Template (غيره إذا كان لديك اسم مختلف)
$language = 'ar_AR';       // اللغة

echo '<h2>Testing WhatsApp Template</h2>';
echo '<p>Phone: ' . esc_html($phone) . '</p>';
echo '<p>Template: ' . esc_html($template_name) . '</p>';

// محاولة الإرسال
$result = $whatsapp->send_template(
    $phone,
    $template_name,
    $language,
    array()
);

if (is_wp_error($result)) {
    echo '<div style="color: red; border: 1px red solid; padding: 10px;">';
    echo '<h3>Error:</h3>';
    echo '<p>' . esc_html($result->get_error_message()) . '</p>';
    echo '</div>';
} else {
    echo '<div style="color: green; border: 1px green solid; padding: 10px;">';
    echo '<h3>Success!</h3>';
    echo '<p>Template sent successfully!</p>';
    echo '<pre>' . esc_html(print_r($result, true)) . '</pre>';
    echo '</div>';
}
?>
```

**طريقة الاستخدام:**
```
1. احفظ الملف في: wp-content/test-whatsapp-template.php
2. اذهب إلى: https://your-site.com/wp-content/test-whatsapp-template.php
3. شاهد النتيجة
```

---

## 🎯 تعديل الواجهة الحالية:

### في ملف: `admin/js/lead-detail.js`

بدل هذا:
```javascript
$.ajax({
    url: aqopLeads.ajaxurl,
    method: 'POST',
    data: {
        action: 'aqop_send_whatsapp_message',
        lead_id: leadId,
        message: messageText,
        nonce: aqopLeads.nonce
    }
});
```

غيّره إلى:
```javascript
$.ajax({
    url: aqopLeads.ajaxurl,
    method: 'POST',
    data: {
        action: 'aqop_send_whatsapp_message',
        lead_id: leadId,
        type: 'template',  // ← أضف هذا
        template_name: 'hello',  // ← وهذا
        language: 'ar_AR',  // ← وهذا
        nonce: aqopLeads.nonce
    }
});
```

---

## 🔧 في ملف: `api/class-whatsapp-api.php`

تعديل دالة `send_message`:

```php
public function send_message($request)
{
    $lead_id = $request->get_param('lead_id');
    $message = $request->get_param('message');
    $type = $request->get_param('type') ?: 'text';  // ← اضفنا هذا
    $template_name = $request->get_param('template_name');
    $language = $request->get_param('language') ?: 'ar_AR';

    // Get Lead Phone
    global $wpdb;
    $lead = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}aq_leads WHERE id = %d", 
        $lead_id
    ));

    if (!$lead) {
        return new WP_Error('not_found', 'Lead not found', array('status' => 404));
    }

    $phone = $lead->whatsapp ?: $lead->phone;
    if (!$phone) {
        return new WP_Error('no_phone', 'Lead has no phone', array('status' => 400));
    }

    // إرسال Template أو Text
    if ($type === 'template') {
        $result = $this->whatsapp->send_template(
            $phone, 
            $template_name, 
            $language,
            array()
        );
    } else {
        $result = $this->whatsapp->send_message($phone, $message);
    }

    if (is_wp_error($result)) {
        return new WP_Error(
            'send_failed', 
            $result->get_error_message(), 
            array('status' => 400)
        );
    }

    // Log the message
    AQOP_Event_Logger::log(
        'leads', 
        'whatsapp_message_sent', 
        'lead', 
        $lead_id,
        array(
            'type' => $type,
            'template_name' => $template_name,
            'phone' => $phone,
        )
    );

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => $result
    ), 200);
}
```

---

## 📱 خيارات الإرسال من الواجهة:

### الخيار 1: زر واحد (استخدم Template دائماً)
```html
<button id="send-template-message" class="button button-primary">
    📨 Send WhatsApp Message
</button>
```

### الخيار 2: خيارين
```html
<div class="whatsapp-send-options">
    <button id="send-text-message" class="button">
        📝 Send Text (if contacted before)
    </button>
    <button id="send-template-message" class="button button-primary">
        📨 Send Template (for new contacts)
    </button>
</div>
```

### الخيار 3: Select Template
```html
<select id="template-select">
    <option value="">-- Select Template --</option>
    <option value="hello">رسالة ترحيب</option>
    <option value="follow_up">رسالة متابعة</option>
    <option value="confirmation">رسالة تأكيد</option>
</select>
<button id="send-template" class="button button-primary">
    Send Selected Template
</button>
```

---

## ⚡ الحل السريع (بدون تعديل الواجهة):

**استخدم هذا الأمر من الـ Database:**

```sql
-- أضف بيانات Template إلى قاعدة البيانات
INSERT INTO wp_options 
(option_name, option_value) 
VALUES 
('aqop_whatsapp_templates', 
 '{"hello": {"name": "hello", "language": "ar_AR"}}');
```

ثم استخدم هذا الكود PHP:

```php
<?php
$whatsapp = new AQOP_WhatsApp_Integration();

$phone = get_post_meta($lead_id, 'whatsapp', true);
$result = $whatsapp->send_template(
    $phone,
    'hello',
    'ar_AR',
    array()
);

if (!is_wp_error($result)) {
    echo 'تم الإرسال بنجاح!';
} else {
    echo 'خطأ: ' . $result->get_error_message();
}
?>
```

---

## 📋 الخطوات المقترحة للتطبيق:

```
1. ✅ إنشاء Template في Meta Dashboard
   ├─ اسم Template: "hello"
   └─ النص: الرسالة المطلوبة

2. ✅ اختبار من: /wp-content/test-whatsapp-template.php
   └─ تأكد من الإرسال الناجح

3. ✅ تعديل الواجهة (اختياري)
   └─ أضف زر "Send Template"

4. ✅ استخدام Template للأرقام الجديدة
   └─ كل الأرقام ستعمل الآن!
```

---

## 🎯 الخلاصة:

```
OLD: Text Messages فقط → فشل للأرقام الجديدة ❌
NEW: Template Messages → يعمل مع أي رقم ✅
```

**المزايا:**
- ✅ يعمل مع أي رقم
- ✅ في أي وقت
- ✅ معتمد من Meta
- ✅ مظهر احترافي
- ✅ يمكن جدولة الإرسال

---

**الآن؟**

```
1. اذهب إلى Meta وأنشئ Template
2. اختبر من الـ test file
3. أخبرني إذا نجح!
```

