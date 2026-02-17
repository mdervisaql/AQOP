# Campaign Questions Display - Implementation Complete

## ✅ Feature Added

Campaign Questions section has been added to the Lead Detail page to display custom fields data from Meta Lead Ads or other sources.

---

## 📍 Location

**File:** `src/pages/Agent/LeadDetail.jsx`

**Position:** Between "Lead Details" and "Notes & Activity" sections

---

## 🎯 Purpose

Display campaign-specific questions and answers that were collected when the lead was submitted (e.g., from Meta Lead Ads custom questions).

---

## 📊 Supported Data Formats

### Format 1: New Format (Question/Answer Objects)
```json
{
  "q1": {
    "question": "ما مستواك التعليمي؟",
    "answer": "جامعي"
  },
  "q2": {
    "question": "هل لديك موقع إلكتروني؟",
    "answer": "نعم"
  },
  "q3": {
    "question": "What's your company size?",
    "answer": "10-50 employees"
  }
}
```

**Display:**
```
Campaign Questions
─────────────────
ما مستواك التعليمي؟
جامعي

هل لديك موقع إلكتروني؟
نعم

What's your company size?
10-50 employees
```

### Format 2: Old Format (Simple Key-Value)
```json
{
  "education_level": "University",
  "has_website": "Yes",
  "budget_range": "5000-10000"
}
```

**Display:**
```
Campaign Questions
─────────────────
Education Level
University

Has Website
Yes

Budget Range
5000-10000
```

---

## 🎨 UI Design

### Layout:
```
┌──────────────────────────────────┐
│ Campaign Questions               │
├──────────────────────────────────┤
│ Question 1 Text (bold)          │
│ Answer text                      │
│ ──────────────────────────       │
│ Question 2 Text (bold)          │
│ Answer text                      │
│ ──────────────────────────       │
│ Question 3 Text (bold)          │
│ Answer text                      │
└──────────────────────────────────┘
```

### Styling:
- **Card:** White background, rounded shadow
- **Title:** "Campaign Questions" in bold
- **Questions:** Font-semibold, gray-700
- **Answers:** Normal weight, gray-900
- **Separators:** Border between items
- **RTL:** Automatic direction for Arabic text

---

## 🌐 RTL Support

### Features:
- ✅ `dir="auto"` attribute on questions and answers
- ✅ Automatic text direction detection
- ✅ Arabic text flows right-to-left
- ✅ English text flows left-to-right
- ✅ Mixed content supported

### Example:
```
Question: "ما مستواك التعليمي؟" → Displays RTL
Answer: "جامعي" → Displays RTL

Question: "What's your budget?" → Displays LTR
Answer: "$5,000 - $10,000" → Displays LTR
```

---

## 🔧 Technical Implementation

### Component Logic:
```javascript
{lead.custom_fields && (() => {
  try {
    // Parse JSON if string
    const customFields = typeof lead.custom_fields === 'string' 
      ? JSON.parse(lead.custom_fields) 
      : lead.custom_fields;
    
    // Don't show if empty
    if (!customFields || Object.keys(customFields).length === 0) {
      return null;
    }

    // Render questions
    return (
      <div className="bg-white rounded-lg shadow-md p-6">
        <h2>Campaign Questions</h2>
        {Object.entries(customFields).map(([key, value]) => {
          // Format 1: New format
          if (value?.question && value?.answer) {
            return <QuestionDisplay question={value.question} answer={value.answer} />;
          }
          
          // Format 2: Old format
          return <KeyValueDisplay key={key} value={value} />;
        })}
      </div>
    );
  } catch (error) {
    console.error('Error parsing custom_fields:', error);
    return null;
  }
})()}
```

### Format Detection:
```javascript
// Checks if value has both question and answer properties
if (value && typeof value === 'object' && value.question && value.answer) {
  // New format
} else {
  // Old format
}
```

### Key Formatting (Old Format):
```javascript
// Converts: "education_level" → "Education Level"
key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
```

---

## 💾 Database Field

### Lead Object:
```javascript
{
  id: 123,
  name: "Ahmed Ali",
  email: "ahmed@example.com",
  custom_fields: '{"q1":{"question":"...","answer":"..."}}',  // JSON string or object
  ...
}
```

### Backend (WordPress):
```php
// Stored in wp_aq_leads table
custom_fields: LONGTEXT  // JSON encoded string
```

---

## 📝 Example Scenarios

### Scenario 1: Meta Lead Ads with Arabic Questions
**Data from Meta:**
```json
{
  "q1": {
    "question": "ما مستواك التعليمي؟",
    "answer": "جامعي"
  },
  "q2": {
    "question": "هل لديك خبرة سابقة؟",
    "answer": "نعم، أكثر من 5 سنوات"
  }
}
```

**Display:**
```
Campaign Questions
━━━━━━━━━━━━━━━━━━
ما مستواك التعليمي؟
جامعي

هل لديك خبرة سابقة؟
نعم، أكثر من 5 سنوات
```

### Scenario 2: Mixed Arabic and English
**Data:**
```json
{
  "q1": {
    "question": "ما هو ميزانيتك الشهرية؟",
    "answer": "$5,000 - $10,000"
  },
  "q2": {
    "question": "What's your industry?",
    "answer": "Technology"
  }
}
```

**Display:** Both questions display correctly with appropriate text direction

### Scenario 3: Legacy Format
**Data:**
```json
{
  "company_size": "50-100",
  "annual_revenue": "1M-5M",
  "interested_in": "Premium Package"
}
```

**Display:**
```
Campaign Questions
━━━━━━━━━━━━━━━━━━
Company Size
50-100

Annual Revenue
1M-5M

Interested In
Premium Package
```

---

## 🎨 CSS Classes

### Applied Styles:
```css
/* Container */
.bg-white.rounded-lg.shadow-md.p-6

/* Title */
.text-xl.font-semibold.text-gray-900.mb-4

/* Questions Container */
.space-y-4

/* Question Item */
.border-b.border-gray-200.pb-4.last:border-b-0.last:pb-0

/* Question (Bold) */
.text-sm.font-semibold.text-gray-700.mb-1

/* Answer (Normal) */
.text-gray-900
```

### RTL Attribute:
```html
<dt dir="auto">Question text</dt>
<dd dir="auto">Answer text</dd>
```

---

## 🧪 Testing Scenarios

### Test 1: New Format Display
**Create lead with:**
```json
{
  "custom_fields": "{\"q1\":{\"question\":\"Test?\",\"answer\":\"Yes\"}}"
}
```

**Expected:**
- ✅ Section visible
- ✅ Question in bold
- ✅ Answer below question
- ✅ Proper spacing

### Test 2: Arabic Content
**Create lead with:**
```json
{
  "custom_fields": "{\"q1\":{\"question\":\"ما مستواك؟\",\"answer\":\"عالي\"}}"
}
```

**Expected:**
- ✅ Arabic text displays right-to-left
- ✅ Proper alignment
- ✅ No text overflow

### Test 3: Old Format
**Create lead with:**
```json
{
  "custom_fields": "{\"company_size\":\"50-100\",\"budget\":\"High\"}"
}
```

**Expected:**
- ✅ Keys formatted nicely ("Company Size", "Budget")
- ✅ Values display correctly
- ✅ Section visible

### Test 4: No Custom Fields
**Create lead with:**
```json
{
  "custom_fields": null
}
```
or
```json
{
  "custom_fields": "{}"
}
```

**Expected:**
- ✅ Section NOT visible
- ✅ No errors in console
- ✅ Page renders normally

### Test 5: Invalid JSON
**Create lead with:**
```json
{
  "custom_fields": "invalid json{{"
}
```

**Expected:**
- ✅ Error caught gracefully
- ✅ Section NOT visible
- ✅ Error logged to console
- ✅ Page still renders

---

## 🔍 Conditional Display Logic

### When Section Shows:
```javascript
lead.custom_fields exists
AND custom_fields is not empty
AND custom_fields parses successfully
AND custom_fields has at least 1 key
```

### When Section Hides:
```javascript
lead.custom_fields is null
OR custom_fields is empty string
OR custom_fields is "{}"
OR custom_fields fails to parse
```

---

## 💡 Use Cases

### 1. Meta Lead Ads Integration
When Meta sends lead data with custom question answers, they're stored in `custom_fields` and displayed here.

### 2. Public Form Submissions
If public forms collect additional data, it can be stored in `custom_fields`.

### 3. External Integrations
Any external source can send custom data that gets mapped to `custom_fields`.

### 4. Campaign-Specific Data
Different campaigns can collect different questions, all displayed in this section.

---

## 🎯 Benefits

### For Agents:
- ✅ See all campaign question answers at a glance
- ✅ Better understand lead context
- ✅ Make informed contact decisions

### For Managers:
- ✅ Review collected campaign data
- ✅ Verify question mapping is correct
- ✅ Quality control

### For Business:
- ✅ Capture rich lead data
- ✅ Better lead qualification
- ✅ Improved targeting
- ✅ Higher conversion rates

---

## 📚 Related Features

### WordPress Admin Side:
**Settings → Campaign Questions**
- Configure questions for each campaign
- Define question IDs (q1, q2, q3...)
- Set question text and type

### React Frontend:
**Lead Detail Page**
- Display configured questions
- Show collected answers
- RTL support for multilingual

### Meta Lead Ads Integration:
- Webhook receives question answers
- Maps to configured campaign questions
- Stores in custom_fields
- Displays on lead detail page

---

## ✅ Implementation Checklist

- [x] Section added after Lead Details
- [x] Conditional rendering (only if custom_fields exists)
- [x] JSON parsing with error handling
- [x] Format 1 support (question/answer objects)
- [x] Format 2 support (key-value pairs)
- [x] RTL support with `dir="auto"`
- [x] Clean card design
- [x] Proper spacing and borders
- [x] Bold questions, normal answers
- [x] Empty state handling
- [x] Error handling
- [x] No console errors
- [x] No linter errors

---

## 🎉 Status: COMPLETE ✅

Campaign Questions display is now fully functional on the Lead Detail page!

**Features:**
- ✅ Supports both data formats
- ✅ RTL support for Arabic
- ✅ Clean, professional design
- ✅ Error handling
- ✅ Conditional display

**Ready to use!** Add `custom_fields` data to a lead and view it on the detail page. 🚀

---

**Last Updated:** November 17, 2025
**Component:** `src/pages/Agent/LeadDetail.jsx`

