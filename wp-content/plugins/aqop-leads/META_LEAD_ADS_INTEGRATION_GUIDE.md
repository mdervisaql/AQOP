# Meta (Facebook) Lead Ads Integration Guide

## 📋 Overview

The AQOP Platform now supports direct integration with Meta (Facebook) Lead Ads to automatically receive and process leads from your Facebook and Instagram campaigns.

---

## 🎯 Features

### **Webhook Integration**
- ✅ Automatic lead capture from Meta Lead Ads
- ✅ Real-time webhook processing
- ✅ Signature verification for security
- ✅ Comprehensive logging and debugging

### **Field Mapping**
- ✅ Standard fields (name, email, phone, etc.)
- ✅ Custom campaign questions
- ✅ Automatic data transformation
- ✅ Campaign-specific question handling

### **Admin Interface**
- ✅ Easy configuration in WordPress admin
- ✅ Connection status monitoring
- ✅ Test webhook functionality
- ✅ Activity logging and debugging

---

## 🚀 Quick Setup (5 Minutes)

### **Step 1: Create Meta App**

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click "Create App" → "Business" → "Lead Ads"
3. Fill in app details and create

### **Step 2: Configure Webhook**

1. In your Meta app, go to "Webhooks" → "Add Callback URL"
2. Enter your webhook URL: `https://yourdomain.com/wp-json/aqop/v1/meta/webhook`
3. Subscribe to "Lead Ad" events

### **Step 3: AQOP Settings**

1. Go to **AQOP Platform → Leads → Settings → Meta Integration**
2. Enter **Verify Token** from Meta app
3. Enter **App Secret** from Meta app
4. Click "Save Meta Settings"

### **Step 4: Test Integration**

1. Click "Send Test Webhook" button
2. Check "Recent Webhook Activity" for success
3. Verify lead was created in AQOP

---

## 🔧 Detailed Configuration

### **Meta App Setup**

#### **1. Create Business App**
```
Meta for Developers → Create App → Business → Lead Ads
App Name: AQOP Lead Integration
App Contact Email: your-email@domain.com
```

#### **2. Add Webhooks Product**
```
App Dashboard → Add Product → Webhooks
```

#### **3. Configure Webhook**
```
Callback URL: https://yourdomain.com/wp-json/aqop/v1/meta/webhook
Verify Token: [Create a secure token, e.g., 'aqop_webhook_2024']
Subscribe to: leadgen
```

#### **4. Get App Secret**
```
App Settings → Basic → App Secret
Copy and save securely
```

### **AQOP Settings Configuration**

#### **Access Settings**
```
WordPress Admin → AQOP Platform → Leads → Settings → Meta Integration
```

#### **Required Fields**
- **Verify Token**: Must match Meta webhook verify token
- **App Secret**: For signature verification (optional but recommended)

#### **Webhook URL**
```
Auto-generated: https://yourdomain.com/wp-json/aqop/v1/meta/webhook
Copy this URL to Meta webhook configuration
```

---

## 📊 Field Mapping Guide

### **Standard Meta Fields**

| Meta Field | AQOP Field | Notes |
|------------|------------|-------|
| `full_name` | `name` | Primary name field |
| `email` | `email` | Contact email |
| `phone_number` | `phone` | Contact phone |
| `city` | `city` | Location city |
| `country` | `country` | Country code |
| `message` | `message` | Additional message |

### **Custom Campaign Questions**

Meta allows custom questions in lead forms. These are mapped as:

```json
{
  "custom_question_1": "What is your budget?",
  "custom_question_2": "What services interest you?",
  "question_1": "How did you hear about us?"
}
```

**Mapped to AQOP custom_fields:**
```json
{
  "custom_question_1": {
    "question": "What is your budget?",
    "answer": "User's answer here"
  }
}
```

### **Lead Source**

All Meta leads are automatically tagged with:
- **Source**: `facebook`
- **Status**: `new` (can be changed by agents)

---

## 📋 Meta Payload Structure

### **Standard Payload Format**
```json
{
  "object": "page",
  "entry": [{
    "id": "page_id",
    "time": 1234567890,
    "changes": [{
      "field": "leadgen",
      "value": {
        "leadgen_id": "123456789",
        "form_id": "987654321",
        "page_id": "page_123",
        "adgroup_id": "ad_456",
        "created_time": 1234567890,
        "field_data": [
          {"name": "full_name", "values": ["John Doe"]},
          {"name": "email", "values": ["john@example.com"]},
          {"name": "phone_number", "values": ["+1234567890"]},
          {"name": "custom_question_1", "values": ["Answer here"]}
        ]
      }
    }]
  }]
}
```

### **Processing Flow**
1. **Receive**: Webhook endpoint accepts POST requests
2. **Verify**: Check signature (if app secret configured)
3. **Parse**: Extract lead data from field_data array
4. **Map**: Transform Meta fields to AQOP format
5. **Validate**: Ensure required fields present
6. **Create**: Insert lead into AQOP database
7. **Log**: Record activity for debugging

---

## 🔍 Testing & Debugging

### **Test Webhook Button**

Located in **Settings → Meta Integration → Test Webhook**

**What it does:**
- Generates sample Meta payload
- Sends POST request to your webhook endpoint
- Creates test lead in AQOP
- Shows result in activity log

### **Webhook Activity Log**

**Location:** Settings → Meta Integration → Recent Webhook Activity

**Shows:**
- Timestamp of webhook events
- Event type (verification, received, processed, error)
- Success/failure status
- Lead creation confirmation
- Error messages and details

### **Common Issues**

#### **Webhook Not Receiving**
- ✅ Check webhook URL is correct
- ✅ Verify HTTPS (required for production)
- ✅ Check Meta app has correct permissions
- ✅ Confirm webhook is subscribed to "leadgen"

#### **Leads Not Creating**
- ✅ Check verify token matches
- ✅ Verify app secret (if configured)
- ✅ Check field mapping in logs
- ✅ Ensure required fields present

#### **Signature Verification Failed**
- ✅ App secret must match Meta app secret exactly
- ✅ No extra spaces or characters
- ✅ Required for production security

---

## 🔒 Security Features

### **Signature Verification**
- Uses SHA256 HMAC with app secret
- Prevents unauthorized webhook calls
- Optional but highly recommended for production

### **Input Validation**
- All data sanitized before database insertion
- SQL injection protection via prepared statements
- XSS protection on all output

### **Rate Limiting**
- Built-in WordPress protections
- IP-based restrictions available
- Admin monitoring of suspicious activity

---

## 📈 Monitoring & Analytics

### **Connection Status**
- Green "Ready" when configured
- Yellow "Not Configured" when missing settings
- Real-time status in admin interface

### **Lead Tracking**
- All Meta leads tagged with source "facebook"
- Track conversion rates by source
- Monitor campaign performance

### **Performance Metrics**
- Webhook response times
- Success/failure rates
- Lead creation statistics

---

## 🛠️ Troubleshooting

### **Webhook Verification Failed**
```
Error: Invalid verification token
```
**Fix:**
- Ensure verify token in AQOP matches Meta exactly
- Check for extra spaces or characters
- Regenerate token if compromised

### **Signature Verification Failed**
```
Error: Invalid signature
```
**Fix:**
- Verify app secret matches Meta app secret
- Ensure no encoding issues
- Check for character encoding problems

### **Lead Not Created**
```
Error: Missing required fields
```
**Fix:**
- Check Meta form has required fields
- Verify field mapping in webhook logs
- Ensure form is published and active

### **Webhook Not Receiving**
```
No webhook events in logs
```
**Fix:**
- Verify webhook URL in Meta is correct
- Ensure HTTPS for production
- Check Meta app permissions
- Confirm webhook subscription active

---

## 📚 API Reference

### **Webhook Endpoints**

#### **POST /wp-json/aqop/v1/meta/webhook**
- Receives Meta lead data
- Processes and creates AQOP leads
- Returns success/error response

#### **GET /wp-json/aqop/v1/meta/webhook**
- Meta webhook verification
- Requires hub.mode, hub.challenge, hub.verify_token
- Returns challenge string for verification

### **Admin AJAX Endpoints**

#### **POST wp-admin/admin-ajax.php?action=aqop_get_meta_test_payload**
- Returns test webhook payload
- Used by test webhook button
- Requires manage_options capability

---

## 🚀 Advanced Configuration

### **Custom Field Mapping**

For complex integrations, you can modify field mapping in:
```
aqop-leads/api/class-meta-webhook-api.php
Method: map_meta_lead_to_aqop()
```

### **Campaign-Specific Logic**

Add custom processing based on campaign ID:
```php
if ($meta_lead['adgroup_id'] === 'specific_campaign') {
    // Custom logic here
}
```

### **Additional Validation**

Extend validation in the same method:
```php
// Add custom validation rules
if (empty($lead_data['email'])) {
    // Custom validation logic
}
```

---

## 📞 Support & Resources

### **Official Resources**
- [Meta for Developers](https://developers.facebook.com/docs/marketing-api/guides/lead-ads/)
- [Webhook Documentation](https://developers.facebook.com/docs/graph-api/webhooks/)
- [Lead Ads Guide](https://www.facebook.com/business/ads/lead-ads)

### **AQOP Resources**
- WordPress Admin → AQOP → Leads → Settings → Meta Integration
- Webhook logs for debugging
- Test webhook functionality

---

## 📝 Changelog

### **Version 1.0.0**
- ✅ Initial Meta Lead Ads integration
- ✅ Webhook verification and signature validation
- ✅ Standard and custom field mapping
- ✅ Admin configuration interface
- ✅ Test webhook functionality
- ✅ Comprehensive logging and debugging
- ✅ Security features and input validation

---

## 🎯 Next Steps

1. **Setup Complete** ✅
   - Meta app created
   - Webhook configured
   - AQOP settings saved

2. **Test Integration** 🔄
   - Send test webhook
   - Verify lead creation
   - Check activity logs

3. **Create Lead Ads** 📢
   - Design lead forms
   - Add custom questions
   - Launch campaigns

4. **Monitor Performance** 📊
   - Track lead quality
   - Monitor conversion rates
   - Optimize campaigns

---

## 💡 Pro Tips

### **Lead Form Optimization**
- Keep forms short (3-5 questions max)
- Use clear, specific questions
- Test different question types
- A/B test form designs

### **Campaign Targeting**
- Use detailed audience targeting
- Test different ad creatives
- Monitor cost per lead
- Optimize based on conversion data

### **Lead Quality**
- Set expectations clearly in ads
- Qualify leads with good questions
- Follow up quickly (within 5 minutes)
- Use lead scoring for prioritization

---

**🎉 Happy converting! Your Meta Lead Ads are now integrated with AQOP Platform.**
