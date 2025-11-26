# Comfort Comm Chatbot - Technical Specification (P0)

**Last Updated:** Nov 24, 2024
**Status:** P0 Implementation

---

## 1. System Architecture

### 1.1 AI Model
- **Provider:** Google Gemini API
- **Model:** `gemini-1.5-flash` (configurable in WordPress Admin)
- **API Endpoint:** `https://generativelanguage.googleapis.com/v1beta`
- **Configuration:**
  - Max Tokens: 2048
  - Temperature: 0.7
  - Top P: 0.95
- **API Key:** Stored in WordPress options (`chatbot_gemini_api_key`)

### 1.2 Knowledge Base (JSON) - SMART COST OPTIMIZATION
- **Format:** JSON file with Question/Answer/Keywords
- **Location:** `/data/comfort-comm-faqs.json` (55 FAQs)
- **Search Function:** `chatbot_faq_search()` in `chatbot-kn-faq-import.php:219-345`
- **Intelligence:** 4-tier confidence-based routing system
- **Cost Savings:** 80-90% of questions answered FREE (no AI call)

**JSON Structure:**
```json
{
  "id": "cc006",
  "question": "What are the Spectrum internet plans?",
  "answer": "Spectrum internet starts at $40/month...",
  "category": "Internet Plans",
  "keywords": "spectrum plans pricing mbps speed",
  "created_at": "2025-11-25"
}
```

**Intelligent Routing System:**

**TIER 1: Pre-Processing Rules (0% API cost)**
- **Trigger:** Sensitive keywords detected BEFORE FAQ/AI processing
- **Keywords:** billing, payment, account balance, login, password, cancel service
- **Action:** Immediate escalation to (347) 519-9999 - NO API CALL
- **Coverage:** ~20% of questions
- **Code:** `chatbot-call-gemini-api.php:48-71`

**TIER 2: Very High Confidence FAQ (0% API cost)**
- **Trigger:** FAQ match confidence ≥ 80% (exact/phrase match)
- **Matching:** Exact question, phrase containment, weighted keywords
- **Action:** Return FAQ answer directly - NO API CALL
- **Coverage:** ~50-60% of questions
- **Response Time:** <100ms
- **Code:** `chatbot-kn-faq-import.php:245-259` + `chatbot-call-gemini-api.php:68-73`

**TIER 3: High Confidence FAQ (~0.001 cost)**
- **Trigger:** FAQ match confidence 60-79%
- **Action:** Minimal AI call with instruction "Rephrase in 1-2 sentences"
- **Coverage:** ~10-15% of questions
- **Cost:** ~$0.001 per question (minimal tokens)
- **Code:** `chatbot-call-gemini-api.php:75-78`

**TIER 4: Medium/Low Confidence (full AI, ~0.004 cost)**
- **Trigger:** FAQ confidence 20-59% OR no match
- **Action:** Full AI processing with FAQ context or pure AI
- **Coverage:** ~10-20% of questions
- **Cost:** ~$0.003-0.005 per question
- **Code:** `chatbot-call-gemini-api.php:80-91`

**Enhanced FAQ Matching Algorithm:**
- Exact question match = 100% confidence score
- Phrase containment = 85-90% confidence
- Weighted keyword scoring (question words worth 2x vs keywords)
- Partial word matching for variations
- Comprehensive match boost (+15% if 80%+ words matched)

**Expected Cost Savings:**
- Before: 100 questions = 100 API calls = $0.50/day = **$15/month**
- After: 100 questions = 15 API calls = $0.055/day = **$1.65/month**
- **Savings: 89% reduction ($13.35/month saved)**

### 1.3 WordPress Integration
- **Base Plugin:** Kognetiks Chatbot v2.3.7
- **Type:** Custom WordPress plugin
- **Display Methods:**
  - Shortcode: `[chatbot]`
  - Widget injection
  - Floating chat window (default)
- **AJAX Endpoint:** `admin-ajax.php`
- **Action:** `chatbot_chatgpt_send_message`

---

## 2. Context System (P0 Features)

### 2.1 Page Context Injection ✅ IMPLEMENTED
**File:** `includes/chatbot-call-gemini-api.php:64-99`

**How it works:**
1. Captures WordPress page ID via `get_the_id()`
2. Fetches page title, URL, and content via `get_post($page_id)`
3. Strips HTML tags and shortcodes
4. Limits to first 800 words (prevents token overflow)
5. Injects into Gemini context

**Context sent to Gemini:**
```
CURRENT PAGE CONTEXT:
The user is currently viewing the page titled: "[Page Title]"
Page URL: [URL]

Page Content:
[First 800 words...]

When answering questions, you can reference information from this page...
```

**Capabilities:**
- Bot knows what page user is viewing
- Can answer "What page am I on?"
- References page content: "On this page, it says..."

### 2.2 Conversation History
**File:** `includes/chatbot-call-gemini-api.php:178-184`

**Setting:** `chatbot_chatgpt_conversation_continuation`
- **Default:** `Off`
- **WordPress Admin Path:** Settings → General → Additional Setup → "Conversation Continuation"
- **Recommendation:** Enable for P0 (better UX)

**When enabled:**
- Bot remembers previous questions in session
- Can answer follow-up questions
- Example:
  - User: "What are Spectrum prices?"
  - Bot: "[Answer]"
  - User: "What about T-Mobile?" ← Bot remembers context

### 2.3 Conversation Intelligence - Auto-Escalation
**File:** `assets/js/chatbot-chatgpt.js:142-185, 1388-1408`

**Purpose:** Detect user frustration and automatically escalate to human support

**State Tracking:**
```javascript
conversationState = {
    failedAttempts: 0,
    consecutiveDissatisfaction: 0,
    lastUserMessage: ''
}
```

**Dissatisfaction Detection:**
Monitors user messages for phrases indicating frustration:
- "doesn't help", "not helpful", "didn't help"
- "this doesn't work", "not working", "still not"
- "doesn't answer", "not what i", "not answering"

**Auto-Escalation Logic:**
- **Trigger 1:** 2 consecutive expressions of dissatisfaction
- **Trigger 2:** 4 total failed attempts in conversation
- **Action:** Display escalation message and STOP sending to AI (saves cost)
- **Message:** "Please call (347) 519-9999 for direct assistance..."

**Example Flow:**
```
User: "How do I restart my modem?"
Bot: [FAQ answer]
User: "This doesn't help" ← Dissatisfaction #1, consecutiveDissatisfaction = 1
Bot: [AI asks clarifying questions]
User: "Still not working" ← Dissatisfaction #2, consecutiveDissatisfaction = 2
Bot: [AUTO-ESCALATE - no AI call] "Please call (347) 519-9999..."
```

**Benefits:**
- Prevents frustrated users from wasting time
- Stops unnecessary AI calls when user needs human help
- Saves ~$0.015-0.025 per escalated conversation
- Improves customer satisfaction (gets help faster)

### 2.4 FAQ Context Priority
**Order of routing (highest to lowest priority):**
1. **Pre-Processing Rules** (billing/account → immediate escalation)
2. **Very High Confidence FAQ** (80%+ match → direct answer, $0)
3. **Auto-Escalation Check** (frustration detected → escalate, $0)
4. **High Confidence FAQ** (60-79% → minimal AI rephrasing)
5. **Medium/Low FAQ** (20-59% → AI with context)
6. **No Match** (0-19% → full AI processing)
7. **General Gemini Knowledge** (fallback)

---

## 3. Message Flow

### 3.1 User Message Processing

```
1. User types message
   ↓
2. JavaScript captures input (chatbot-chatgpt.js:1165)
   ↓
3. AJAX call to WordPress backend
   - Action: chatbot_chatgpt_send_message
   - Data: message, user_id, page_id, session_id, nonce
   ↓
4. Backend processing (chatbot-call-gemini-api.php):
   a. FAQ Search (CSV lookup)
   b. Page Context Injection (current page)
   c. Conversation History (if enabled)
   d. Build Gemini API request
   ↓
5. Call Gemini API
   ↓
6. Parse response
   ↓
7. Return to frontend
   ↓
8. Display in chat UI
```

### 3.2 Session Management
- **Logged-in users:** WordPress `user_id`
- **Anonymous users:** Cookie-based `session_id` (30-day expiry)
- **Cookie name:** `kognetiks_unique_id`
- **Conversation locking:** Prevents duplicate message processing (60s timeout)

---

## 4. UI/UX (P0 Implementation)

### 4.1 Chat Interface
- **Display Style:** Floating chat bubble (default)
- **Position:** Bottom-right corner
- **Header:** "Comfort Comm Chatbot"
- **Input:** Text area with "Enter your question..." placeholder
- **Buttons:** Submit, Erase conversation, Download transcript

### 4.2 Message Styling
**User Messages:**
- Background: `#fffbeb` (light yellow)
- Text color: `#1e3a8a` (dark blue)
- Position: Right-aligned
- Border: `1px solid #fde68a`

**Bot Messages:**
- Background: `#f1f5f9` (light gray) - controlled via CSS
- Text color: `#1e3a8a` (dark blue)
- Position: Left-aligned
- Border: `1px solid #e2e8f0`

**CSS File:** `assets/css/chatbot-chatgpt.css:322-354`

**Note:** Admin appearance overrides DISABLED for P0 (custom styling only)
- See `future_iteration.md` for P2 re-enablement

### 4.3 Greeting Message
**Setting:** `chatbot_chatgpt_initial_greeting`
**Current:** "Hey there! I'm doing great, thanks for asking!..."
**Location:** WordPress Admin → Settings → General → Initial Greeting

---

## 5. Performance & Infrastructure

### 5.1 Response Time
- **Target:** <3 seconds (user submit to bot response)
- **Timeout:** 240 seconds (configurable)
- **Actual:** Depends on Gemini API latency + FAQ search

### 5.2 Error Handling
- **API Failures:** Custom error message (configurable)
- **Network Timeout:** "Oops! Something went wrong" + retry
- **Duplicate Prevention:** 5-minute window via message UUID transients

### 5.3 Rate Limiting
- **Gemini API:** Per Google Cloud project limits
- **WordPress:** No custom rate limiting in P0
- **Conversation Lock:** 60-second timeout prevents stuck conversations

---

## 6. Security (P0)

### 6.1 Input Sanitization
- All user input sanitized: `sanitizeInput()` function
- AJAX nonce verification: `chatbot_message_nonce`
- XSS protection: `htmlspecialchars()`, `strip_tags()`

**File:** `assets/js/chatbot-chatgpt.js:1597-1612`

### 6.2 Output Sanitization
- Bot responses purified: DOMPurify (client-side)
- Markdown rendering (safe subset)
- Links force `target="_blank"`

### 6.3 API Key Security
- Stored in WordPress `wp_options` table
- Not exposed to frontend
- Backend-only API calls

---

## 7. Key Files Modified

### PHP Backend
```
includes/chatbot-call-gemini-api.php
├── Lines 48-71:   Pre-processing escalation rules (NEW - Nov 25)
├── Lines 73-101:  Smart FAQ routing with confidence tiers (NEW - Nov 25)
├── Lines 103-140: Page context injection
├── Lines 178-184: Conversation continuation
└── Lines 216:     Context assembly for Gemini

includes/knowledge-navigator/chatbot-kn-faq-import.php
├── Lines 219-345: Enhanced FAQ search algorithm (NEW - Nov 25)
│                  - Multi-tier confidence scoring
│                  - Exact/phrase/keyword matching
│                  - Weighted scoring with boost logic
├── Lines 399-476: FAQ CRUD functions (add/update/get)
└── Lines 478-558: AJAX handlers for FAQ management

includes/settings/chatbot-settings-registration-kn.php
├── Lines 218-425: FAQ Management UI (NEW - Nov 25)
│                  - Add/Edit/Delete buttons
│                  - Modal popup form
│                  - jQuery AJAX integration
└── Lines 233-273: FAQ table display with actions

includes/appearance/chatbot-settings-appearance-text.php
├── Lines 50-56:   Admin color overrides DISABLED
└── Lines 85-90, 175-182: Admin overrides DISABLED
```

### JavaScript Frontend
```
assets/js/chatbot-chatgpt.js
├── Lines 136-140:  Idle timeout DISABLED for testing (Nov 25)
├── Lines 142-185:  Conversation state tracking (NEW - Nov 25)
│                   - detectDissatisfaction()
│                   - shouldAutoEscalate()
│                   - getEscalationMessage()
├── Lines 660-687:  resetIdleTimer() DISABLED (Nov 25)
├── Lines 917-925:  CSAT exclusions for system messages (Nov 25)
├── Lines 1388-1408: Auto-escalation integration (NEW - Nov 25)
├── Lines 1165-1594: Submit message handler
└── Lines 830-945:  appendMessage() with CSAT
```

### CSS Styling
```
assets/css/chatbot-chatgpt.css
├── Lines 322-338: User message styling (FIXED with !important)
└── Lines 340-354: Bot message styling
```

### Data Files
```
data/comfort-comm-faqs.json
└── 55 FAQs for Comfort Communication Inc. (NEW - Nov 25)
    Categories: Store Info, Internet Plans, Mobile Services,
                Installation, Billing, Troubleshooting, etc.
```

---

## 8. WordPress Admin Settings (P0 Configuration)

### Required Settings to Configure:

**API/Gemini Tab:**
- ✅ Gemini API Key (required)
- Model: `gemini-1.5-flash` (default)
- Max Tokens: 2048
- Temperature: 0.7

**General Tab:**
- Chatbot Name: "Comfort Comm Chatbot"
- Initial Greeting: [Custom greeting message]
- Conversation Continuation: **ENABLE** (recommended for P0)
- Bot Prompt: "Enter your question..."

**Appearance Tab:**
- Display Style: Floating (default)
- Width: Narrow
- **Note:** Color overrides disabled for P0 (CSS-controlled)

---

## 9. Testing Checklist (P0)

### Functional Tests
- [ ] FAQ questions return correct answers from CSV
- [ ] "What page am I on?" returns correct page name
- [ ] Page content references work ("On this page...")
- [ ] User messages visible (text appears in yellow box)
- [ ] Bot messages visible (text appears in gray box)
- [ ] Conversation history works (follow-up questions)
- [ ] Multiple messages don't duplicate (no 3x logging)

### Integration Tests
- [ ] Homepage page context loads
- [ ] FAQ CSV file accessible
- [ ] Gemini API key valid
- [ ] AJAX calls succeed
- [ ] Session management works

### Browser Tests
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile (responsive)

---

## 10. Known Issues & Bugs Fixed

### ✅ Fixed (Nov 24, 2024)
1. **User messages invisible** - CSS color override issue
   - Fix: Added `!important` to user text color
   - File: `chatbot-chatgpt.css:325, 335-338`

2. **JavaScript error: `subsequentGreeting` undefined**
   - Broke chatbot initialization
   - Fix: Commented out undefined variable check
   - File: `chatbot-chatgpt.js:495-498`

3. **Admin color overrides conflicting with custom CSS**
   - Green bot messages overriding gray
   - Fix: Disabled admin appearance functions
   - File: `chatbot-settings-appearance-text.php`

### ⚠️ Known Issues (Not Critical for P0)
1. **Debug message logs 3x** - Submit button triggered multiple times
   - File: `chatbot-chatgpt.js:1252`
   - Impact: Minor (just console spam)

---

## 11. Deployment Notes

### Pre-Launch
1. Populate FAQ CSV with Top 10-25 questions
2. Verify Gemini API key in Admin
3. Enable "Conversation Continuation" setting
4. Test on staging site
5. Clear browser cache before testing

### Post-Launch Monitoring
- Check chatbot logs: `/chatbot-logs/` directory
- Monitor Gemini API usage (Google Cloud Console)
- Review conversation transcripts weekly

---

## 12. Future Enhancements (P1/P2)

See `future_iteration.md` for:
- Admin appearance settings (P2)
- CSAT prompt implementation (P1)
- Sensitive question detection (P0 - TODO)
- Analytics dashboard (P1)
- Multilingual support (P2)

---

## Appendix: Technical Dependencies

### WordPress
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+

### External APIs
- Google Gemini API (requires API key)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 10. AI-Powered Feedback Loop System (Nov 26, 2024)

### 10.1 Overview
**Purpose:** Automatically improve FAQ knowledge base based on user feedback
**Trigger:** Thumbs-down feedback with optional user comments
**Output:** AI-generated suggestions to improve/create FAQs
**Result:** Self-improving chatbot accuracy over time

### 10.2 Architecture

#### Components
1. **Feedback Collection** (`includes/chatbot-csat.php`)
   - Captures thumbs up/down with optional comment
   - Stores: question, answer, comment, confidence_score, timestamp
   - Storage: WordPress options table (`chatbot_chatgpt_csat_data`)

2. **Analysis Engine** (`includes/chatbot-feedback-analysis.php`)
   - Filters feedback by time period (weekly/monthly/quarterly/yearly/all)
   - Sends thumbs-down feedback to Gemini 2.5 Flash
   - Generates actionable improvement suggestions
   - Returns: improve existing FAQ OR create new FAQ

3. **FAQ Management** (AJAX handlers)
   - `chatbot_ajax_add_faq` - Adds new FAQ to JSON
   - `chatbot_ajax_edit_faq` - Appends keywords to existing FAQ
   - Direct JSON file manipulation with auto-ID generation

### 10.3 Time-Based Filtering

**Time Periods:**
- Weekly: Last 7 days (`-7 days`)
- Monthly: Last 30 days (`-30 days`)
- Quarterly: Last 90 days (`-90 days`)
- Yearly: Last 365 days (`-365 days`)
- All Time: No filter

**Implementation:**
```php
$cutoff_date = date('Y-m-d H:i:s', strtotime('-7 days'));
$filtered = array_filter($responses, function($r) use ($cutoff_date) {
    return $r['timestamp'] >= $cutoff_date;
});
```

### 10.4 Confidence Score Tracking

**Storage:** Added to CSAT feedback data
```php
$confidence_score = chatbot_faq_search($question)['confidence'];
// Values: 'very_high', 'high', 'medium', 'low', 'unknown'
```

**Display:** Color-coded badges in feedback table
- Very High (80%+): Green `#10b981`
- High (60-80%): Blue `#3b82f6`
- Medium (40-60%): Orange `#f59e0b`
- Low (20-40%): Red `#ef4444`
- Unknown: Gray `#94a3b8`

**AI Usage:** Helps prioritize improvements
- Low confidence → Suggest adding keywords
- Unknown → Suggest creating new FAQ

### 10.5 AI Analysis Process

#### Step 1: Filter Feedback
```php
// Get thumbs-down only
$thumbs_down = array_filter($all_responses, function($r) {
    return $r['feedback'] === 'no';
});

// Prioritize feedback with comments
$with_comments = array_filter($thumbs_down, function($r) {
    return !empty($r['comment']);
});

// Take up to 10 items (comments first)
$to_analyze = array_slice($with_comments, 0, 10);
```

#### Step 2: Build AI Prompt
```php
$prompt = "Analyze customer feedback for FAQ system...

EXISTING FAQ DATABASE:
cc001: Where is your store located? [location address...]
cc002: What are your business hours? [hours open...]
...

NEGATIVE FEEDBACK:
1. Question: How much does Spectrum cost?
   Answer: Spectrum starts at $40/month...
   Confidence Score: medium
   User Comment: Need info about contract terms
   Feedback: 👎 Thumbs Down

Suggest improvements: IMPROVE existing FAQ or CREATE new FAQ
Respond with JSON array..."
```

#### Step 3: Gemini API Call
```php
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
$response = wp_remote_post($url, [
    'body' => json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 4096
        ]
    ]),
    'timeout' => 45
]);
```

#### Step 4: Parse AI Response
```json
[
  {
    "feedback_number": 1,
    "action_type": "improve",
    "existing_faq_id": "cc006",
    "suggested_keywords": ["contract", "monthly", "terms"],
    "reasoning": "User feedback indicates missing pricing details"
  },
  {
    "feedback_number": 2,
    "action_type": "create",
    "suggested_faq": {
      "question": "Are there any contract requirements?",
      "answer": "Contract requirements vary by carrier...",
      "keywords": "contract no-contract commitment term"
    },
    "reasoning": "Multiple users asked but no FAQ exists"
  }
]
```

### 10.6 FAQ Management

#### Add New FAQ
```php
function chatbot_ajax_add_faq() {
    // Load existing FAQs
    $faqs = json_decode(file_get_contents($faq_file), true);

    // Generate new ID (auto-increment)
    $last_id = end($faqs)['id']; // e.g., "cc063"
    $id_num = intval(substr($last_id, 2)) + 1;
    $new_id = 'cc' . str_pad($id_num, 3, '0', STR_PAD_LEFT); // "cc064"

    // Create new FAQ
    $new_faq = [
        'id' => $new_id,
        'question' => $faq_data['question'],
        'answer' => $faq_data['answer'],
        'category' => $faq_data['category'] ?? 'General',
        'keywords' => $faq_data['keywords'] ?? '',
        'created_at' => current_time('mysql')
    ];

    // Save to JSON
    $faqs[] = $new_faq;
    file_put_contents($faq_file, json_encode($faqs, JSON_PRETTY_PRINT));
}
```

#### Edit Existing FAQ
```php
function chatbot_ajax_edit_faq() {
    foreach ($faqs as &$faq) {
        if ($faq['id'] === $faq_id) {
            // Append new keywords
            $existing = $faq['keywords'] ?? '';
            $faq['keywords'] = trim($existing . ' ' . $new_keywords);
            break;
        }
    }
    file_put_contents($faq_file, json_encode($faqs, JSON_PRETTY_PRINT));
}
```

### 10.7 UI Components

#### Time Period Selector
```html
<select id="feedback-period">
    <option value="weekly">Weekly (Last 7 days)</option>
    <option value="monthly">Monthly (Last 30 days)</option>
    <option value="quarterly">Quarterly (Last 90 days)</option>
    <option value="yearly">Yearly (Last 365 days)</option>
    <option value="all">All Time</option>
</select>
```

#### Action Buttons
```html
<!-- For improvements -->
<button onclick='chatbotEditFAQ(...)'>Edit FAQ</button>

<!-- For new FAQs -->
<button onclick='chatbotAddFAQ(...)'>Add to Knowledge Base</button>
```

#### Clear Data Button
```html
<button onclick="chatbotClearFeedback()"
        style="background: #ef4444; color: white;">
    Clear Feedback Data
</button>
```

### 10.8 Complete Workflow

1. **User Experience:**
   - User asks question → Gets answer
   - Clicks 👎 thumbs down
   - Modal popup: "Help us improve! (optional comment)"
   - Submits feedback with/without comment

2. **Admin Experience:**
   - Navigate to Reporting Overview
   - See CSAT metrics + Recent Feedback table (with confidence scores)
   - Select time period (e.g., "Weekly")
   - Click "Analyze Feedback" button
   - AI shows suggestions:
     - 🔧 Improve FAQ cc006: Add keywords "contract, terms"
     - ✨ Create FAQ: "Are there contract requirements?"
   - Click "Edit FAQ" or "Add to Knowledge Base"
   - JSON updated instantly

3. **Result:**
   - Better FAQ matching
   - Higher confidence scores
   - Fewer thumbs-down responses
   - Self-improving chatbot

### 10.9 Files Modified/Added

#### New Files
- `includes/chatbot-feedback-analysis.php` (302 lines)
  - `chatbot_ajax_analyze_feedback()` - Main analysis handler
  - `chatbot_analyze_feedback_with_ai()` - Gemini API call
  - `chatbot_generate_suggestions_html()` - UI generation
  - `chatbot_ajax_clear_feedback()` - Reset data
  - `chatbot_ajax_add_faq()` - Add new FAQ
  - `chatbot_ajax_edit_faq()` - Edit existing FAQ

#### Modified Files
- `includes/chatbot-csat.php` - Added confidence score capture
- `includes/settings/chatbot-settings-reporting.php` - Added UI controls
- `assets/js/chatbot-chatgpt.js` - Fixed markdown rendering
- `assets/css/chatbot-chatgpt.css` - Fixed text overflow
- `data/comfort-comm-faqs.json` - Now 66 FAQs (up from 55)

### 10.10 Security

- Nonce verification: `chatbot_feedback_analysis`, `chatbot_clear_feedback`, `chatbot_faq_management`
- Capability check: `current_user_can('manage_options')`
- Input sanitization: `sanitize_text_field()`, `sanitize_textarea_field()`
- File permissions: JSON file write access required
- Confirmation dialogs: Clear data, Add FAQ, Edit FAQ

### 10.11 Performance

- AI Analysis: ~5-10 seconds (depends on feedback count)
- FAQ Add/Edit: <1 second (JSON write)
- Clear Data: <1 second (option update)
- Time Filtering: In-memory array filter (negligible)

### 10.12 Future Enhancements

- Auto-schedule weekly analysis (WordPress cron)
- Email admin with analysis summary
- FAQ version history
- A/B testing for FAQ variations
- Bulk FAQ import/export
- Category-based filtering
