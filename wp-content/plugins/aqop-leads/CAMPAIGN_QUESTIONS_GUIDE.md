# Campaign Questions Management - Complete Guide

## ✅ Feature Complete

Campaign Questions Management has been added to the WordPress Admin Settings page.

---

## 📍 Access

**WordPress Admin:**
```
Leads → Settings → Campaign Questions tab
```

---

## 🎯 Purpose

Configure custom questions for each campaign to map Meta Lead Ads answers to your database fields.

### Use Case:
When Meta Lead Ads collects answers to custom questions (like "What is your education level?" / "ما مستواك التعليمي؟"), you can map these to campaign-specific questions for proper data handling.

---

## 🏗️ Data Structure

### Stored in `wp_options`:
```
Option Name: aqop_campaign_questions
```

### Data Format:
```json
{
  "campaign_123": {
    "q1": {
      "text": "ما مستواك التعليمي؟",
      "type": "select"
    },
    "q2": {
      "text": "هل لديك موقع إلكتروني؟",
      "type": "radio"
    },
    "q3": {
      "text": "What's your budget?",
      "type": "text"
    }
  },
  "campaign_456": {
    "q1": {
      "text": "Company size?",
      "type": "select"
    }
  }
}
```

---

## ✨ Features

### 1. View Campaign Questions
- ✅ Lists all active campaigns
- ✅ Shows all questions for each campaign
- ✅ Displays question ID, text, and type
- ✅ RTL support for Arabic text

### 2. Add Questions
- ✅ Question ID (format: q1, q2, q3...)
- ✅ Question Text (supports Arabic and English)
- ✅ Field Type (text, select, radio, checkbox, textarea)
- ✅ Validation (prevents duplicate IDs)
- ✅ Real-time updates via AJAX

### 3. Edit Questions
- ✅ Click "Edit" button
- ✅ Prompts for new text and type
- ✅ Updates existing question
- ✅ Page auto-refreshes

### 4. Delete Questions
- ✅ Click "Delete" button
- ✅ Confirmation dialog
- ✅ Removes question
- ✅ Page auto-refreshes

---

## 🎨 UI Components

### Tab Navigation
```
[Lead Sources] [Lead Statuses] [Integrations] [Notifications] [Campaign Questions]
```

### Campaign Card Layout
```
┌─────────────────────────────────────────┐
│ 📢 Campaign Name #123                   │
├─────────────────────────────────────────┤
│ Question ID │ Question Text  │ Type │ Actions │
│ q1          │ ما مستواك؟    │ Text │ [Edit] [Delete] │
│ q2          │ هل لديك موقع؟ │ Select │ [Edit] [Delete] │
├─────────────────────────────────────────┤
│ Add New Question                        │
│ Question ID: [q3___]                    │
│ Question Text: [________]               │
│ Field Type: [Text ▼]                    │
│ [+ Add Question]                        │
└─────────────────────────────────────────┘
```

### Field Type Badges
- **Text** - Blue badge
- **Select/Radio** - Purple badge
- **Checkbox** - Green badge
- **Textarea** - Orange badge

---

## 🔧 How to Use

### Adding a Question

1. **Navigate to Settings**
   ```
   WordPress Admin → Leads → Settings → Campaign Questions tab
   ```

2. **Find Your Campaign**
   - Scroll to the campaign card
   - Each active campaign has its own section

3. **Fill Out Form**
   - **Question ID:** Enter `q1`, `q2`, `q3`, etc. (must be unique)
   - **Question Text:** Enter question in Arabic or English
   - **Field Type:** Select from dropdown

4. **Click "Add Question"**
   - Question is saved
   - Page reloads
   - Question appears in the table

### Editing a Question

1. **Click "Edit" button** on the question row
2. **Enter new question text** in prompt
3. **Enter new field type** in second prompt
4. **Question updates** and page reloads

### Deleting a Question

1. **Click "Delete" button** on the question row
2. **Confirm deletion** in dialog
3. **Question removed** and page reloads

---

## 📊 Field Types

| Type | Description | Use Case |
|------|-------------|----------|
| **text** | Single-line text input | Name, short answers |
| **textarea** | Multi-line text input | Long messages, descriptions |
| **select** | Dropdown selection | Choose one from multiple options |
| **radio** | Radio buttons | Choose one (visual display) |
| **checkbox** | Checkboxes | Multiple selections |

---

## 🔐 Security Features

### ✅ Implemented Security:
- **Nonce verification** - Prevents CSRF attacks
- **Permission checks** - Only admins can manage questions
- **Input sanitization** - All inputs sanitized
- **Question ID validation** - Regex pattern enforcement
- **Duplicate prevention** - Can't add same ID twice
- **XSS prevention** - All output escaped

### Validation Rules:
- Question ID must match: `q[0-9]+` (e.g., q1, q2, q10)
- Question text cannot be empty
- Field type must be valid enum
- Campaign must exist

---

## 🌐 RTL Support

### Arabic Text Handling:
- ✅ `dir="auto"` attribute on text inputs
- ✅ Automatic text direction detection
- ✅ Proper text alignment
- ✅ Compatible with WordPress RTL admin

### Example Arabic Questions:
```
q1: ما مستواك التعليمي؟ (What's your education level?)
q2: هل لديك موقع إلكتروني؟ (Do you have a website?)
q3: ما هو ميزانيتك الشهرية؟ (What's your monthly budget?)
```

---

## 🔌 AJAX Endpoints

### 1. Add Question
```
Action: aqop_add_campaign_question
Method: POST
Nonce: aqop_campaign_questions
```

**Parameters:**
- `campaign_id` - Campaign identifier (e.g., campaign_123)
- `question_id` - Question ID (e.g., q1)
- `question_text` - Question text
- `question_type` - Field type

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Question added successfully.",
    "question": {
      "text": "...",
      "type": "text"
    }
  }
}
```

### 2. Edit Question
```
Action: aqop_edit_campaign_question
Method: POST
Nonce: aqop_campaign_questions
```

**Parameters:**
- `campaign_id` - Campaign identifier
- `question_id` - Question ID
- `question_text` - New question text
- `question_type` - New field type

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Question updated successfully.",
    "question": { ... }
  }
}
```

### 3. Delete Question
```
Action: aqop_delete_campaign_question
Method: POST
Nonce: aqop_campaign_questions
```

**Parameters:**
- `campaign_id` - Campaign identifier
- `question_id` - Question ID

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Question deleted successfully."
  }
}
```

---

## 💾 Database Storage

### Option Details:
- **Option Name:** `aqop_campaign_questions`
- **Auto Load:** No (for performance)
- **Format:** JSON encoded array
- **Size:** Typically < 10 KB

### Retrieval:
```php
$questions = get_option( 'aqop_campaign_questions', array() );
$campaign_123_questions = $questions['campaign_123'] ?? array();
```

### Update:
```php
$questions = get_option( 'aqop_campaign_questions', array() );
$questions['campaign_123']['q4'] = array(
    'text' => 'New question?',
    'type' => 'text'
);
update_option( 'aqop_campaign_questions', $questions );
```

---

## 🧪 Testing Scenarios

### Test 1: Add Question
1. Go to Settings → Campaign Questions
2. Find a campaign
3. Fill form:
   - ID: `q1`
   - Text: `ما مستواك التعليمي؟`
   - Type: `select`
4. Click "Add Question"
5. **Expected:** Question added, page reloads, question visible in table

### Test 2: Add Duplicate Question ID
1. Try to add question with existing ID (e.g., `q1`)
2. **Expected:** Error: "Question ID already exists for this campaign"

### Test 3: Invalid Question ID
1. Try to add question with ID: `question1` (wrong format)
2. **Expected:** Error: "Question ID must be in format: q1, q2, q3, etc."

### Test 4: Edit Question
1. Click "Edit" on existing question
2. Enter new text: `What is your company size?`
3. Enter new type: `radio`
4. **Expected:** Question updated, page reloads

### Test 5: Delete Question
1. Click "Delete" on a question
2. Confirm deletion
3. **Expected:** Question removed, page reloads

### Test 6: Arabic Text
1. Add question with Arabic text: `ما هو عمرك؟`
2. **Expected:** Text displays correctly (right-to-left)
3. Edit question with mixed Arabic/English
4. **Expected:** Both languages display correctly

---

## 🎯 Use Case Example

### Meta Lead Ads Form:
```
Question 1: ما مستواك التعليمي؟
- ثانوي
- جامعي
- دراسات عليا

Question 2: هل لديك موقع إلكتروني؟
- نعم
- لا
- قريباً
```

### Configuration in AQOP:
```
Campaign: "Meta Ads Campaign 2025"

q1: 
  Text: ما مستواك التعليمي؟
  Type: select

q2:
  Text: هل لديك موقع إلكتروني؟
  Type: radio
```

### When Lead Received:
```php
// Meta sends:
{
  "q1": "جامعي",
  "q2": "نعم"
}

// AQOP maps:
$campaign_questions = get_option('aqop_campaign_questions')['campaign_123'];
$q1_question = $campaign_questions['q1']['text']; // "ما مستواك التعليمي؟"
$q1_answer = "جامعي"; // From Meta

// Store in custom_fields
$lead->custom_fields = json_encode([
  'education_level' => 'جامعي',
  'has_website' => 'نعم'
]);
```

---

## ⚙️ Technical Implementation

### Files Modified:

1. **`admin/views/settings.php`**
   - Added "Campaign Questions" tab
   - Added questions table for each campaign
   - Added add/edit/delete forms
   - Added CSS styles
   - Added JavaScript AJAX handlers

2. **`admin/class-leads-admin.php`**
   - Added 3 AJAX action hooks in constructor
   - Added `ajax_add_campaign_question()` method
   - Added `ajax_edit_campaign_question()` method
   - Added `ajax_delete_campaign_question()` method

### Code Structure:
```php
// In constructor
add_action('wp_ajax_aqop_add_campaign_question', ...);
add_action('wp_ajax_aqop_edit_campaign_question', ...);
add_action('wp_ajax_aqop_delete_campaign_question', ...);

// Methods
public function ajax_add_campaign_question() { ... }
public function ajax_edit_campaign_question() { ... }
public function ajax_delete_campaign_question() { ... }
```

---

## 📝 Data Format Examples

### Simple Campaign:
```json
{
  "campaign_1": {
    "q1": {
      "text": "What's your budget?",
      "type": "select"
    },
    "q2": {
      "text": "Company size?",
      "type": "text"
    }
  }
}
```

### Multilingual Campaign:
```json
{
  "campaign_arabic": {
    "q1": {
      "text": "ما مستواك التعليمي؟",
      "type": "select"
    },
    "q2": {
      "text": "هل تملك شركة؟",
      "type": "radio"
    }
  },
  "campaign_english": {
    "q1": {
      "text": "What's your job title?",
      "type": "text"
    }
  }
}
```

---

## 🚀 Future Enhancements

### Potential Improvements:
- [ ] Drag and drop question reordering
- [ ] Question options (for select/radio/checkbox)
- [ ] Question validation rules
- [ ] Import/Export questions
- [ ] Copy questions between campaigns
- [ ] Question templates
- [ ] Bulk delete
- [ ] Preview mode
- [ ] API endpoint to get questions

---

## ✅ Checklist

- [x] Tab added to settings page
- [x] Campaign list displayed
- [x] Add question form created
- [x] Edit question functionality
- [x] Delete question functionality
- [x] AJAX handlers implemented
- [x] Nonce security added
- [x] Permission checks added
- [x] Input sanitization
- [x] Question ID validation
- [x] RTL support for Arabic
- [x] Professional UI design
- [x] Color-coded field types
- [x] Error handling
- [x] Success messages
- [x] No linter errors

---

## 🎉 Status: PRODUCTION READY

Campaign Questions Management is fully functional and ready to use for Meta Lead Ads integration.

**Next:** Configure questions for your campaigns to enable proper answer mapping!

---

**Documentation:** `CAMPAIGN_QUESTIONS_GUIDE.md`
**Last Updated:** November 17, 2025

