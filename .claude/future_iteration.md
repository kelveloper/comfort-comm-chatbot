# Future Iterations (P1/P2 Features)

**Last Updated:** Nov 24, 2024

---

## P2: Admin Appearance Settings (User-Customizable Colors)

**Status:** Disabled for P0
**Why:** Client wants locked, custom styling - not user-configurable

### What Was Disabled

**File:** `includes/appearance/chatbot-settings-appearance-text.php`

**Functions disabled:**
- User message color overrides (lines 50-56)
- Bot message color overrides (lines 85-90, 175-182)

**Original behavior:**
- WordPress Admin had color picker fields
- Settings overrode CSS with `!important`
- Default bot color: `#5BC236` (green)

### How to Re-Enable

1. **Uncomment code** in `chatbot-settings-appearance-text.php`:
   - Lines 50-56: User/bot message colors
   - Lines 85-90: User background function
   - Lines 175-182: Bot background function

2. **WordPress Admin path:**
   ```
   Dashboard → Kognetiks Chatbot → Appearance Tab
   ```

3. **Settings available:**
   - User Text Background Color
   - Bot Text Background Color
   - Text Color
   - Header colors
   - Width settings

---

## P1: Enhanced User Experience Features

### 1. Conversation Context Memory
- **What:** Bot remembers conversation across page navigation
- **How:** Store conversation in sessionStorage or database
- **Benefits:** Users don't lose context when browsing site

### 2. Typing Indicators Enhancement
- **What:** Visual "..." indicator when bot is thinking
- **Status:** Partially implemented, needs polish
- **File:** `assets/js/chatbot-chatgpt.js` (showTypingIndicator function)

### 3. Proactive Suggestions
- **What:** "You might also be interested in..." after answers
- **Example:** After answering "Spectrum prices" → suggest "How to sign up"
- **Implementation:** Add related FAQ suggestions to bot response

### 4. Mobile Optimization
- **What:** Responsive design improvements for small screens
- **Touch targets:** Larger buttons (min 44x44px)
- **Font sizes:** Scale appropriately on mobile

### 5. Chat History Scroll
- **What:** Users can scroll up to review earlier answers
- **Status:** Already works, may need UX polish

### 6. Conversation Analytics Dashboard
- **What:** Admin dashboard showing:
  - Most asked questions
  - Automated resolution rate
  - CSAT scores over time
  - Unanswered questions (for FAQ updates)
- **File to create:** New admin page in `includes/settings/`

---

## P2: Nice-to-Have Features

### 1. Multilingual Support (Chinese)
- **Why:** Customer base in Queens has Chinese speakers
- **How:**
  - Detect browser language
  - Translate UI elements
  - Use Gemini with Chinese context
- **Complexity:** High (needs translation management)

### 2. Address-Based Availability Check
- **What:** "What providers are available at my address?"
- **How:** Integration with provider API or database
- **Challenges:** Requires provider API access

### 3. CRM Integration
- **What:** Log conversations to CRM (Salesforce, HubSpot, etc.)
- **Use case:** Sales team can see customer questions
- **Implementation:** Webhook or API integration

### 4. Upgraded Escalation Flow
**Current (P0):**
```
Bot: "Please call (347) 519-9999"
```

**P2 Enhancement:**
```
Bot: "I'm still learning. Can I get your:
- Name: _______
- Phone: _______
A human expert will call you back within 1 hour."
```

### 5. Advanced Personalization
- **What:** Remember returning users
- **Features:**
  - "Welcome back, [Name]!"
  - Show user's past questions
  - Personalized recommendations

### 6. A/B Testing Framework
- **What:** Test different bot responses
- **Example:** Test 2 versions of greeting, measure CSAT
- **Implementation:** Assign users to test groups

### 7. Voice Input (Speech-to-Text)
- **What:** User clicks mic icon, speaks question
- **How:** Web Speech API or Gemini STT
- **File reference:** `assets/js/chatbot-chatgpt.js` (microphone icon already exists)

---

## Code References for Future Work

### Admin Color Settings
```
includes/appearance/chatbot-settings-appearance-text.php (lines 49-182)
includes/settings/chatbot-settings-appearance.php (registration)
```

### Message Styling (CSS)
```
assets/css/chatbot-chatgpt.css (lines 322-354)
```

### FAQ Search
```
includes/chatbot-call-gemini-api.php:48-62 (FAQ matching logic)
```

### Page Context Injection
```
includes/chatbot-call-gemini-api.php:64-99 (current page content)
```

### AJAX Message Sending
```
assets/js/chatbot-chatgpt.js:1165-1594 (submit handler)
```

---

## Database Schema Changes Needed

### For Conversation Analytics (P1)
```sql
CREATE TABLE wp_chatbot_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE,
    total_messages INT,
    automated_resolutions INT,
    csat_positive INT,
    csat_total INT,
    top_questions TEXT
);
```

### For User Personalization (P2)
```sql
CREATE TABLE wp_chatbot_user_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255),
    session_id VARCHAR(255),
    last_visit DATETIME,
    conversation_count INT,
    preferred_language VARCHAR(10)
);
```

---

## Priority Recommendations

**Implement in this order:**

1. **P1: Conversation Analytics Dashboard** (High value, moderate effort)
2. **P1: Mobile Optimization** (Quick wins, improves UX)
3. **P1: Proactive Suggestions** (Increases engagement)
4. **P2: Upgraded Escalation** (Better lead capture)
5. **P2: Multilingual Support** (if data shows demand)

---

## Notes

- All P1/P2 features should maintain P0 FAQ CSV priority
- Keep page context injection active
- Test on mobile devices before deploying P1 mobile optimizations
- Admin appearance settings can be re-enabled anytime with minimal risk
