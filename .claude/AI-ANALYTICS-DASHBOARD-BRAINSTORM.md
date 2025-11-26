# AI-Powered Analytics Dashboard - Brainstorm

**Project:** Comfort Comm Chatbot Analytics Dashboard
**Date:** November 25, 2024
**Goal:** Build an intelligent analytics dashboard using AI to analyze conversation data and provide actionable insights

---

## 📊 Available Data Sources

### Database Tables:
1. **`wp_chatbot_chatgpt_conversation_log`**
   - `id` - Unique conversation entry ID
   - `session_id` - User session identifier
   - `user_id` - WordPress user ID (or anonymous cookie ID)
   - `page_id` - Page where conversation occurred
   - `interaction_time` - Timestamp of interaction
   - `user_type` - ENUM: 'Chatbot', 'Visitor', 'Prompt Tokens', 'Completion Tokens', 'Total Tokens'
   - `message_text` - Actual message content (question or answer)
   - `assistant_id` - Assistant/bot identifier
   - `assistant_name` - Name of assistant
   - `sentiment_score` - FLOAT (for sentiment analysis)

2. **`wp_chatbot_chatgpt_interactions`**
   - `date` - Interaction date
   - `count` - Number of interactions per day

### Other Data:
- FAQ database (`data/comfort-comm-faqs.json`)
- System logs (`wp-content/debug.log`)
- CSAT responses (stored separately)

---

## 🤖 AI-Powered Analytics Features

### 1. **Conversation Intelligence**

#### A. AI Conversation Summaries
**What:** Generate intelligent summaries of customer conversations
```
Example:
Session #12345 (Nov 25, 2024):
- Customer asked about pricing for family plans
- Budget constraint: $50 for 2 people
- Outcome: Directed to call for personalized quote
- Sentiment: Neutral → Positive
```

**Implementation:**
- Feed conversation pairs (Q&A) to Gemini
- Prompt: "Summarize this customer service conversation, focusing on: customer intent, budget/constraints, services discussed, and outcome"
- Store summaries in new table: `wp_chatbot_conversation_summaries`

**Metrics:**
- Total conversations per day/week/month
- Average conversation length (message count)
- Conversation resolution rate (ended with FAQ vs escalation)

---

#### B. Question Gap Analysis (MOST IMPORTANT!)
**What:** Identify questions users ask that are NOT in the FAQ database

**How:**
1. Query all "Visitor" messages from conversation log
2. For each question, check FAQ match confidence score
3. If confidence < 40% (medium/low match), it's a "gap question"
4. Use Gemini to:
   - Cluster similar gap questions
   - Summarize common themes
   - Generate suggested FAQ entries

**Output:**
```
Top 10 Unanswered Questions This Week:
1. "Can I use my own router?" (asked 15 times) - No FAQ match
   Suggested FAQ: "Router Compatibility"

2. "Do you offer senior discounts?" (asked 8 times) - No FAQ match
   Suggested FAQ: "Senior & Student Discounts"
```

**Business Value:** 🔥 **HUGE** - Client knows exactly what FAQs to add!

---

#### C. Most Asked Questions
**What:** Track which FAQs are being hit most frequently

**Implementation:**
- Track FAQ ID when FAQ is matched (modify `chatbot-kn-faq-import.php` to log which FAQ was returned)
- New table: `wp_chatbot_faq_usage`
  - `faq_id`
  - `question`
  - `hit_count`
  - `last_asked`

**Output:**
```
Top 5 Most Asked Questions (Last 7 Days):
1. "What are Spectrum prices?" - 45 hits
2. "Where is your store located?" - 32 hits
3. "Do you install?" - 28 hits
4. "What carriers do you work with?" - 21 hits
5. "Can I keep my phone number?" - 18 hits
```

---

### 2. **API Usage & Cost Analytics**

#### A. API Request Tracking
**Current Data Available:**
- `Prompt Tokens`, `Completion Tokens`, `Total Tokens` logged in conversation_log

**Metrics to Calculate:**
```javascript
Daily API Metrics:
- Total API calls
- Tier 1 (Pre-processing): 0 calls - $0.00
- Tier 2 (Very High FAQ): 35 calls - $0.00
- Tier 3 (High FAQ): 12 calls - $0.012
- Tier 4 (Full AI): 8 calls - $0.032
- Total Cost: $0.044

Weekly/Monthly Cost Projections:
- This week: $0.31
- This month (projected): $1.33
- Savings vs no FAQ: $13.67 (91% reduction)
```

#### B. Token Usage Analysis
**Metrics:**
- Average tokens per request
- Peak usage times
- Token efficiency (responses per 1K tokens)
- Cost per conversation

**AI Analysis:**
- Identify conversations using excessive tokens
- Suggest prompt optimizations
- Detect anomalies (unusually long responses)

---

### 3. **Customer Sentiment & Satisfaction**

#### A. Sentiment Trends
**Current:** `sentiment_score` column exists but unused

**Implement:**
- Use Gemini to analyze customer message sentiment
- Track sentiment progression within conversations
- Identify conversations that went negative

**Metrics:**
```
Weekly Sentiment:
😊 Positive: 65%
😐 Neutral: 28%
😞 Negative: 7%

Conversations Requiring Attention:
- Session #12389 - Started neutral, ended frustrated
- Session #12401 - Negative throughout, auto-escalated
```

#### B. CSAT Integration
**Current:** CSAT (thumbs up/down) exists

**Enhancements:**
- Correlate CSAT with conversation topics
- "Which topics get thumbs down?"
- AI suggestion: "FAQ #CC015 (Internet speeds) has 60% thumbs down - needs rewrite"

---

### 4. **Performance & Efficiency Metrics**

#### A. Response Time Analytics
**Track:**
- Average time to first response
- FAQ hit rate (% of questions answered by FAQ vs AI)
- Escalation rate (% conversations ending in "call us")

#### B. User Journey Analytics
**Questions to Answer:**
- What page do most conversations start on?
- Do users on pricing pages have different questions?
- Which pages have highest escalation rate?

---

### 5. **Business Intelligence**

#### A. Product/Service Interest
**AI Analysis:**
```
Service Interest (Last 30 Days):
- Spectrum Internet: 145 mentions
- Verizon Fios: 67 mentions
- Mobile plans: 89 mentions
- Installation: 52 mentions

Budget Trends:
- $30-$50 range: 42% of questions
- $50-$100 range: 31% of questions
- $100+: 15% of questions
- No budget mentioned: 12%
```

**Business Value:** Client knows what to market!

#### B. Competitor Analysis
**Track mentions of:**
- Competitors (Optimum, Verizon, T-Mobile)
- Switching reasons
- Price comparisons

---

## 🎨 Dashboard Layout Proposal

```
┌─────────────────────────────────────────────────────────────┐
│  Comfort Comm Chatbot Analytics Dashboard                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  📊 OVERVIEW (Last 7 Days)                                  │
│  ┌────────────┬────────────┬────────────┬────────────┐     │
│  │ 127        │ $0.44      │ 89%        │ 4.2/5.0   │     │
│  │ Total Chats│ API Cost   │ FAQ Hit    │ Avg CSAT  │     │
│  └────────────┴────────────┴────────────┴────────────┘     │
│                                                              │
│  🤖 AI INSIGHTS                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 🔍 Gap Analysis: 12 questions not in FAQ database    │  │
│  │    Top gap: "Can I use my own router?" (8 times)    │  │
│  │    [Generate Suggested FAQs]                         │  │
│  ├──────────────────────────────────────────────────────┤  │
│  │ 📈 Trending Topics: Family/Bundle plans ↑ 35%      │  │
│  │ 💰 Budget Range: $40-$60 most common                │  │
│  │ 😊 Sentiment: Mostly positive (71%)                 │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  📋 MOST ASKED QUESTIONS                                    │
│  1. Spectrum pricing (45 hits) - FAQ #CC006                │
│  2. Store location (32 hits) - FAQ #CC001                  │
│  3. Installation info (28 hits) - FAQ #CC020               │
│                                                              │
│  💡 SUGGESTED FAQ ADDITIONS                                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 1. "Router Compatibility" - Asked 8x, no FAQ match   │  │
│  │    [Preview] [Add to FAQ] [Dismiss]                  │  │
│  │                                                       │  │
│  │ 2. "Senior Discounts" - Asked 5x, no FAQ match      │  │
│  │    [Preview] [Add to FAQ] [Dismiss]                  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  💬 RECENT CONVERSATIONS (AI Summaries)                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Session #12456 - 2 hours ago                         │  │
│  │ Topic: Family plan pricing                           │  │
│  │ Budget: $50 for 2 people                            │  │
│  │ Outcome: Escalated to phone                         │  │
│  │ Sentiment: Positive                                  │  │
│  │ [View Full Conversation]                             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  💰 COST ANALYSIS                                           │
│  [Interactive Chart: Daily API costs over time]            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Implementation Plan

### Phase 1: Data Collection (Week 1)
- [ ] Add FAQ tracking (log which FAQ was used)
- [ ] Create new tables:
  - `wp_chatbot_conversation_summaries`
  - `wp_chatbot_faq_usage`
  - `wp_chatbot_gap_questions`
- [ ] Implement API cost tracking per tier

### Phase 2: AI Analysis Engine (Week 2)
- [ ] Build conversation summarizer (Gemini API)
- [ ] Build gap question detector
- [ ] Build FAQ suggestion generator
- [ ] Implement sentiment analysis

### Phase 3: Dashboard UI (Week 3)
- [ ] Design dashboard layout
- [ ] Implement metrics widgets
- [ ] Create AI insights panel
- [ ] Add interactive charts

### Phase 4: Automation (Week 4)
- [ ] Daily automated reports via email
- [ ] Weekly AI analysis runs
- [ ] Auto-suggestions for FAQ additions

---

## 💡 Additional AI-Powered Features

### Auto-FAQ Generator
**Workflow:**
1. Detect gap question asked 3+ times
2. AI generates FAQ entry:
   - Question (cleaned up)
   - Answer (based on how agent responded)
   - Keywords
   - Category
3. Admin reviews and approves with one click

### Conversation Quality Score
**AI evaluates:**
- Did bot understand the question?
- Was response helpful?
- Was tone appropriate?
- Did it escalate when needed?

**Output:** Quality score 1-10 per conversation

### Anomaly Detection
**AI alerts when:**
- Sudden spike in specific question type
- Unusual API costs
- Drop in CSAT scores
- Increase in escalations

---

## 🎯 Business Value Summary

### For Your Client:
1. **Know what FAQs to add** - AI tells them exactly what customers are asking
2. **Track ROI** - See cost savings from FAQ system
3. **Improve customer satisfaction** - Identify problem areas
4. **Make data-driven decisions** - What services to promote

### For You:
1. **Sell value** - Show client the intelligence behind the chatbot
2. **Reduce manual work** - AI generates FAQ suggestions automatically
3. **Demonstrate expertise** - Advanced analytics = premium pricing
4. **Continuous improvement** - Always know how to optimize

---

## 🚀 Quick Wins (Start Here)

1. **Gap Question Analysis** - Easiest, highest value
2. **Cost Tracking Dashboard** - Show cost savings
3. **Top FAQs Widget** - Simple but useful
4. **AI Conversation Summaries** - Impressive and practical

---

## Questions to Answer

1. Should we run AI analysis:
   - Real-time (expensive)?
   - Nightly batch (cheaper)?
   - On-demand when admin views dashboard?

2. How much conversation history to analyze?
   - Last 7 days?
   - Last 30 days?
   - All time with date filters?

3. Budget for AI analysis:
   - ~$0.01 per conversation summary
   - ~$0.05 for gap analysis (batch of 100 questions)
   - Acceptable monthly AI analytics budget?

4. Data retention:
   - Keep all conversation logs forever?
   - Archive old data?
   - GDPR compliance needs?

---

## Tech Stack Recommendations

**Backend:**
- PHP (existing WordPress)
- Gemini API for AI analysis
- MySQL for data storage

**Frontend:**
- Chart.js or Recharts for visualizations
- WordPress admin pages (consistent with existing UI)
- AJAX for real-time updates

**Automation:**
- WordPress Cron for scheduled analysis
- Email reports via wp_mail()

---

## Next Steps

1. **Feedback:** Which features are highest priority for your client?
2. **Budget:** What's the monthly budget for AI analysis calls?
3. **Timeline:** When do you want to launch this?
4. **Design:** Want to match existing WordPress admin UI or create custom design?

Let's discuss and refine based on your priorities! 🚀
