# Testing Campaign Questions / Custom Fields

## 🧪 How to Test the Feature

Since you likely don't have leads with custom_fields yet, here's how to test:

---

## Method 1: Create Test Lead via SQL

### Using phpMyAdmin or MySQL:

```sql
-- Insert test lead with new format custom fields
INSERT INTO wp_aq_leads 
(name, email, phone, status_id, custom_fields, created_at) 
VALUES (
  'Test Lead Arabic',
  'test@example.com',
  '+966501234567',
  1,
  '{"q1":{"question":"ما مستواك التعليمي؟","answer":"جامعي"},"q2":{"question":"هل لديك موقع إلكتروني؟","answer":"نعم، لدي موقع"},"q3":{"question":"What is your budget?","answer":"$5,000 - $10,000"}}',
  NOW()
);
```

---

## Method 2: Update Existing Lead

### Via SQL:

```sql
-- Update existing lead #1 with custom fields
UPDATE wp_aq_leads 
SET custom_fields = '{"q1":{"question":"ما هو عمرك؟","answer":"25-35"},"q2":{"question":"المدينة؟","answer":"الرياض"}}'
WHERE id = 1;
```

---

## Method 3: Use WordPress REST API

### Via Postman or cURL:

```bash
curl -X PUT "http://localhost:8888/aqleeat-operation/wp-json/aqop/v1/leads/1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "custom_fields": "{\"q1\":{\"question\":\"Test Question?\",\"answer\":\"Test Answer\"}}"
  }'
```

---

## Method 4: Create via Public Form (Future)

When Meta Lead Ads integration is complete, leads will automatically have custom_fields populated.

---

## 📝 Test Data Examples

### Example 1: Arabic Education Form
```json
{
  "q1": {
    "question": "ما مستواك التعليمي؟",
    "answer": "جامعي - بكالوريوس"
  },
  "q2": {
    "question": "هل لديك خبرة سابقة؟",
    "answer": "نعم، أكثر من 5 سنوات"
  },
  "q3": {
    "question": "المدينة؟",
    "answer": "الرياض"
  }
}
```

### Example 2: Business Inquiry Form
```json
{
  "q1": {
    "question": "What's your company size?",
    "answer": "50-100 employees"
  },
  "q2": {
    "question": "Annual revenue?",
    "answer": "$1M - $5M"
  },
  "q3": {
    "question": "What service are you interested in?",
    "answer": "Digital Marketing"
  }
}
```

### Example 3: Mixed Arabic/English
```json
{
  "q1": {
    "question": "ما هو ميزانيتك الشهرية؟",
    "answer": "$10,000"
  },
  "q2": {
    "question": "Industry?",
    "answer": "التكنولوجيا / Technology"
  }
}
```

### Example 4: Old Format (Legacy)
```json
{
  "education_level": "University Degree",
  "experience_years": "5+",
  "city": "Riyadh",
  "interested_service": "SEO"
}
```

---

## ✅ Expected Results

### When Lead Has Custom Fields:
1. Navigate to lead detail page
2. See "Campaign Questions" section
3. Questions display in bold
4. Answers display below
5. Arabic text flows RTL
6. English text flows LTR
7. Clean borders between items

### When Lead Has NO Custom Fields:
1. Navigate to lead detail page
2. "Campaign Questions" section NOT visible
3. Page flows from "Lead Details" to "Notes & Activity"
4. No errors in console

---

## 🎯 Test Checklist

### Functional Tests
- [ ] Insert test lead with custom_fields
- [ ] Navigate to lead detail page
- [ ] See "Campaign Questions" section
- [ ] Questions display in bold
- [ ] Answers display normally
- [ ] Borders separate items
- [ ] Section appears between Details and Notes

### RTL Tests
- [ ] Add Arabic question
- [ ] Text flows right-to-left
- [ ] Add English question  
- [ ] Text flows left-to-right
- [ ] Mixed content displays correctly

### Format Tests
- [ ] Test new format (question/answer objects)
- [ ] Test old format (key-value pairs)
- [ ] Both display correctly
- [ ] Keys formatted nicely in old format

### Edge Cases
- [ ] Empty custom_fields → Section hidden
- [ ] Null custom_fields → Section hidden
- [ ] Invalid JSON → Section hidden, error logged
- [ ] Very long question → Wraps properly
- [ ] Very long answer → Wraps properly

---

## 🚀 Quick Test SQL

### Copy and paste this into phpMyAdmin:

```sql
-- Create test lead with campaign questions
INSERT INTO wp_aq_leads 
(name, email, phone, whatsapp, status_id, priority, custom_fields, created_at) 
VALUES (
  'حسام أحمد - Test Campaign Questions',
  'hussam@test.com',
  '+966501234567',
  '+966501234567',
  1,
  'medium',
  '{"q1":{"question":"ما مستواك التعليمي؟","answer":"جامعي - بكالوريوس"},"q2":{"question":"هل لديك موقع إلكتروني؟","answer":"نعم، لدي موقع نشط"},"q3":{"question":"What is your monthly budget?","answer":"$5,000 - $10,000"},"q4":{"question":"Industry?","answer":"التكنولوجيا والبرمجة"}}',
  NOW()
);

-- Get the ID of inserted lead
SELECT LAST_INSERT_ID();
```

Then:
1. Login to React frontend
2. Navigate to `/leads/{ID}` (use the ID from above)
3. Scroll down to see "Campaign Questions" section
4. Should see 4 questions with answers in Arabic and English

---

## 🔍 Troubleshooting

### Section Not Showing
**Check:**
1. Does `custom_fields` have data in database?
2. Is it valid JSON?
3. Check browser console for errors
4. Verify lead ID is correct

### Arabic Not Displaying RTL
**Check:**
1. Verify `dir="auto"` attribute is present
2. Check browser RTL support
3. Verify Arabic text is UTF-8 encoded

### Data Format Issues
**Check:**
1. Is custom_fields a JSON string or object?
2. Does it match Format 1 or Format 2?
3. Check console for parsing errors

---

## 📊 Data Flow

```
Meta Lead Ads → Webhook → WordPress
    ↓
Campaign Questions configured in Settings
    ↓
Answers mapped to custom_fields
    ↓
{
  "q1": {
    "question": "Configured question text",
    "answer": "User's answer from Meta"
  }
}
    ↓
Stored in wp_aq_leads.custom_fields
    ↓
React fetches lead via API
    ↓
LeadDetail.jsx displays Campaign Questions section
    ↓
User sees questions and answers beautifully formatted
```

---

## ✅ Status: READY TO TEST

Everything is implemented and ready!

**Next Steps:**
1. Create test lead with custom_fields (use SQL above)
2. Navigate to lead detail page in React
3. Verify Campaign Questions section appears
4. Test with Arabic and English text
5. Verify RTL support works

**Happy Testing!** 🚀

