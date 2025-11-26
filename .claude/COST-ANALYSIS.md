# 💰 **Cost Analysis Report: Chatbot API & Database Options**
**Prepared for**: Anson Dai
**Date**: November 25, 2025
**Prepared by**: Kelvin Saldana

---

## 📊 **Executive Summary**

**Current Setup**: Google Gemini Flash 2.0 (Free Tier)
**Current Monthly Cost**: **$0**
**Realistic Monthly Cost**: **$10-30/month** (Gemini Paid) or **$2-3/month** (OpenAI mini)
**Recommendation**: **Start with Free Tier, upgrade when needed** OR **Use OpenAI GPT-4o mini for best value**

---

## ⚠️ **IMPORTANT: Understanding API Requests vs Conversations**

### **Key Difference**:

**1 Conversation** = One customer chat session (multiple messages)
**1 API Request** = Each individual message sent by user

### **Example**:
```
Customer asks: "What are your internet plans?"      ← API Request #1
Bot responds: "We offer Spectrum at $49.99..."

Customer asks: "What about business plans?"         ← API Request #2
Bot responds: "Business plans start at..."

Customer asks: "Can I get installation help?"       ← API Request #3
Bot responds: "Yes, we offer free installation..."
```

**This is**:
- 1 conversation (one customer)
- 3 API requests (3 charges)

**Average chatbot conversation**: 2-3 messages per customer

---

## 1️⃣ **Google Gemini API Analysis**

### **Free Tier Limits** ✅ **(Currently Using)**

| Metric | Limit | Real Impact |
|--------|-------|-------------|
| **Requests per Day** | 25 RPD | ~**10-12 conversations/day** (at 2.5 msg avg) |
| **Requests per Minute** | 5 RPM | ~**2 conversations/minute** |
| **Tokens per Minute** | 32,000 TPM | ~8,000 words/minute (plenty) |
| **Context Window** | 1 million tokens | More than enough |
| **Commercial Use** | ✅ Allowed | Perfect for business |
| **Cost** | **$0/month** | Great for testing |

### **When Free Tier Works**:
- ✅ Less than **10 conversations/day**
- ✅ Low-traffic website
- ✅ Testing/prototype phase
- ✅ Off-peak season

### **When You Need Paid Tier**:
- ❌ More than **10-15 conversations/day**
- ❌ Peak business hours with multiple customers
- ❌ Growing business traffic

### **Paid Tier Pricing** (when you exceed free limits)

| Model | Input (per 1M tokens) | Output (per 1M tokens) | Quality |
|-------|----------------------|------------------------|---------|
| **Gemini 2.5 Flash** | $0.15 | $0.60 | Good, fast |
| **Gemini 2.5 Pro** | $1.25 | $5.00 | Better, slower |

**Cost Examples** (Gemini Flash):

| Daily Conversations | API Requests/Day | Monthly Cost |
|---------------------|------------------|--------------|
| 10 | 25 | **$0** (free tier) |
| 20 | 50 | **$10-15** |
| 40 | 100 | **$20-30** |
| 100 | 250 | **$50-80** |

---

## 2️⃣ **OpenAI ChatGPT API Comparison**

### **Pricing** (Pay-as-you-go, NO free tier)

| Model | Input (per 1M tokens) | Output (per 1M tokens) | Quality | Best For |
|-------|----------------------|------------------------|---------|----------|
| **GPT-4o mini** ⭐ | $0.15 | $0.60 | Very good | **Best value!** |
| **GPT-3.5 Turbo** | $0.50 | $1.50 | Good | High volume |
| **GPT-4o** | $3.00 | $10.00 | Excellent | Premium quality |

### **Cost Examples** (Based on Real Usage)

**20 conversations/day, 50 API requests/day, 1,500 convos/month**:

| Model | Monthly Cost | Notes |
|-------|--------------|-------|
| **GPT-4o mini** | **$2-3** | ⭐ **Best value** - cheaper than Gemini paid! |
| GPT-3.5 Turbo | $6-9 | Good quality, moderate cost |
| GPT-4o | $24-36 | Premium quality, expensive |

**40 conversations/day, 100 API requests/day, 3,000 convos/month**:

| Model | Monthly Cost | Notes |
|-------|--------------|-------|
| **GPT-4o mini** | **$4-6** | Still very affordable |
| GPT-3.5 Turbo | $12-18 | Moderate cost |
| GPT-4o | $48-72 | Premium pricing |

### **Key Differences**:

⚠️ **OpenAI has NO free tier** - you pay from the first API call
✅ **BUT GPT-4o mini is CHEAPER than Gemini paid tier!**
✅ **Better quality responses than Gemini**
⚠️ **Requires credit card upfront**

---

## 3️⃣ **Database Storage for "Dynamic Learning"**

### **What Anson Asked About** (from meeting at 17:40-21:07)

> "Chatbot that learns what type of questions are asked and builds responses based on that"

### **Good News: You Already Have This!** ✅

Your WordPress database **already stores**:
- ✅ All conversation logs (`wp_chatbot_chatgpt_conversation_log` table)
- ✅ Every question users ask
- ✅ Every answer given by bot
- ✅ Timestamps, session IDs, user IDs
- ✅ CSAT feedback (helpful/not helpful ratings)
- ✅ Sentiment scores

### **Database Storage Cost**

**Current Storage**: ~2-5 MB for conversation logs
**After 1 year**: ~50-100 MB (still tiny)
**WordPress Database**: Included with hosting
**Cost**: **$0** 🎉

### **"Dynamic Learning" Implementation Options**

**Option A: Manual Review** (Current - Recommended) ✅
- Review "Reporting" tab weekly
- See what questions customers ask most
- Add popular questions to FAQ manually
- Check CSAT scores to improve answers
- **Cost**: $0 (just your time)

**Option B: AI-Powered Auto-Learning** (Future Enhancement)
- Use Gemini to analyze conversation logs monthly
- Auto-extract common question patterns
- Auto-suggest new FAQs
- Monthly batch processing (1-2 hours of AI time)
- **Cost**: ~$5-10/month additional

**Recommendation**: Start with Option A (manual), add Option B later if needed.

---

## 4️⃣ **Complete Monthly Cost Breakdown**

### **Scenario 1: Free Tier (Testing Phase)** ✅

| Component | Service | Cost | Works If... |
|-----------|---------|------|-------------|
| AI API | Google Gemini Free | **$0** | <10 conversations/day |
| Database | WordPress DB | $0 (included) | Unlimited |
| Conversation Logging | Built-in | $0 | Unlimited |
| CSAT Feedback | Built-in | $0 | Unlimited |
| FAQ System | Built-in | $0 | Unlimited |
| **TOTAL** | | **$0/month** | Low traffic only |

**Best for**: Testing, low traffic, off-season

---

### **Scenario 2: Gemini Paid Tier** (Medium Traffic)

| Component | Service | Cost | Works If... |
|-----------|---------|------|-------------|
| AI API | Gemini Flash Paid | **$10-30** | 20-40 conversations/day |
| Database | WordPress DB | $0 | Unlimited |
| All other features | Built-in | $0 | Unlimited |
| **TOTAL** | | **$10-30/month** | Growing business |

**Best for**: Staying with Google ecosystem, medium traffic

---

### **Scenario 3: OpenAI GPT-4o mini** ⭐ **(BEST VALUE)**

| Component | Service | Cost | Works If... |
|-----------|---------|------|-------------|
| AI API | OpenAI GPT-4o mini | **$2-6** | 20-40 conversations/day |
| Database | WordPress DB | $0 | Unlimited |
| All other features | Built-in | $0 | Unlimited |
| **TOTAL** | | **$2-6/month** | Any traffic level |

**Best for**: Best quality-to-cost ratio, predictable low costs

---

### **Scenario 4: Premium Quality** (OpenAI GPT-4o)

| Component | Service | Cost | Works If... |
|-----------|---------|------|-------------|
| AI API | OpenAI GPT-4o | **$24-72** | 20-40 conversations/day |
| Database | WordPress DB | $0 | Unlimited |
| All other features | Built-in | $0 | Unlimited |
| **TOTAL** | | **$24-72/month** | High-end experience |

**Best for**: Premium customer experience, higher budget

---

## 📈 **Cost Projections by Daily Traffic**

### **Small Retail Store (Comfort Comm Typical)**

| Daily Conversations | API Requests/Day | Gemini Free | Gemini Paid | OpenAI mini | OpenAI GPT-4o |
|---------------------|------------------|-------------|-------------|-------------|---------------|
| 5-10 | 12-25 | ✅ **$0** | $0-10 | $1-2 | $10-18 |
| **15-20** | **38-50** | ❌ Need paid | **$10-15** | **$2-3** ⭐ | $20-30 |
| **30-40** | **75-100** | ❌ Need paid | **$20-30** | **$4-6** ⭐ | $40-60 |
| 50-60 | 125-150 | ❌ Need paid | $30-45 | $6-9 | $60-90 |
| 100+ | 250+ | ❌ Need paid | $50-100 | $10-20 | $120-240 |

**⭐ = Best value for money**

---

## 🎯 **Recommendations (Updated)**

### **Phase 1: Start (First 1-2 Weeks)**

✅ **Use Gemini Free Tier**
- Test with real customers
- Monitor actual usage in "Reporting" tab
- See how many conversations/day you actually get
- **Cost**: $0

**Why**: No risk, test real-world usage before committing

---

### **Phase 2: Production (After Testing)**

Choose based on your actual traffic:

#### **If you get 5-10 conversations/day** (Low traffic)
✅ **Stay on Gemini Free Tier**
- **Cost**: $0/month
- You're within free limits
- Works great

#### **If you get 15-40 conversations/day** (Typical retail) ⭐
✅ **Use OpenAI GPT-4o mini** (BEST VALUE)
- **Cost**: $2-6/month
- Cheaper than Gemini paid
- Better response quality
- Predictable low costs

**OR**

⚪ **Upgrade to Gemini Paid**
- **Cost**: $10-30/month
- Stay in Google ecosystem
- Good if you prefer Google

#### **If you get 50+ conversations/day** (High traffic)
✅ **Use OpenAI GPT-4o mini**
- **Cost**: $6-15/month
- Still very affordable
- Scales well

---

### **"Dynamic Learning" Feature**

**Immediate solution** (Free) ✅:
1. Check "Reporting" tab every Monday
2. Review conversation logs
3. Look for repeated questions
4. Add common questions to FAQ
5. Check CSAT scores - improve low-rated answers

**Future enhancement** ($5-10/month):
- Monthly AI analysis of conversation logs
- Auto-suggest new FAQs
- Pattern recognition for trending questions
- **Not needed immediately** - manual review works great

---

## 🔧 **API Key Setup Instructions**

### **Google Gemini API Key** ✅ (Current Setup)

**Steps**:
1. Go to: https://ai.google.dev/
2. Click "Get API Key" in Google AI Studio
3. Sign in with Google account
4. Click "Create API Key"
5. Copy the key

**Add to WordPress**:
1. Go to **WordPress Admin > Kognetiks > Chatbot**
2. Click **"General"** tab
3. Under "AI Platform Choice", select **"Google Gemini"**
4. Click **"Save Settings"**
5. Go to **"API/Gemini"** tab
6. Paste your API key in **"Gemini API Key"** field
7. Click **"Save Settings"**
8. Look for green success message

**To Upgrade to Paid**:
1. Go to: https://ai.google.dev/gemini-api/docs/billing
2. Click "Enable billing"
3. Add credit card
4. Same API key works - automatic upgrade

---

### **OpenAI ChatGPT API Key** (If Switching)

**Steps**:
1. Go to: https://platform.openai.com/
2. Sign up or log in
3. Click **"API"** in top menu
4. Go to **"API Keys"** in left sidebar
5. Click **"Create new secret key"**
6. Give it a name (e.g., "Comfort Comm Chatbot")
7. Copy the key immediately (you can't see it again!)

**Add Billing** (Required):
1. Go to **"Settings" > "Billing"**
2. Click **"Add payment method"**
3. Add credit card
4. Set usage limit (e.g., $10/month) to prevent overcharges

**Add to WordPress**:
1. Go to **WordPress Admin > Kognetiks > Chatbot**
2. Click **"General"** tab
3. Under "AI Platform Choice", select **"OpenAI"**
4. Click **"Save Settings"**
5. Go to **"API/ChatGPT"** tab
6. Paste your API key in **"API Key"** field
7. Under "Model Choice", select **"gpt-4o-mini"** (cheapest, best value)
8. Click **"Save Settings"**
9. Look for green success message

---

## 💡 **Quick Decision Guide**

### **"What should I use?"**

**If you want FREE (for testing)**:
→ Use **Gemini Free Tier** (max 10 convos/day)

**If you want CHEAPEST (production)**:
→ Use **OpenAI GPT-4o mini** ($2-6/month)

**If you want ZERO SETUP**:
→ Keep **current Gemini Free**, upgrade when needed

**If you want BEST QUALITY**:
→ Use **OpenAI GPT-4o** ($24-72/month)

**If you want GOOGLE ECOSYSTEM**:
→ Use **Gemini Paid** ($10-30/month)

---

## 📊 **Real-World Example: Comfort Comm**

### **Assumptions**:
- Small retail store in Queens, NY
- Website gets 30-50 visitors/day
- 20-30% engage with chatbot
- Average 2.5 messages per conversation

### **Estimated Usage**:
- **10-15 conversations/day**
- **25-38 API requests/day**
- **300-450 conversations/month**
- **750-950 API requests/month**

### **Cost Comparison**:

| Option | Monthly Cost | Quality | Recommendation |
|--------|--------------|---------|----------------|
| Gemini Free | **$0** | Good | ✅ Try first 1-2 weeks |
| **Gemini Paid** | **$10-15** | Good | ⚪ Okay if staying with Google |
| **OpenAI mini** | **$2-3** | Very Good | ⭐ **BEST VALUE** |
| OpenAI GPT-4o | $20-30 | Excellent | Premium option |

**My Recommendation for Comfort Comm**:
1. Start with **Gemini Free** for 1-2 weeks (test real usage)
2. Switch to **OpenAI GPT-4o mini** after testing (best value at $2-3/month)
3. Monitor costs monthly
4. Upgrade to GPT-4o only if you need better quality

---

## ❓ **FAQ**

### **"What if I exceed my daily limit?"**
- **Gemini Free**: Requests fail, customers see error
- **Gemini Paid**: No limits, just pay per usage
- **OpenAI**: No limits, just pay per usage

### **"Can I switch APIs without losing FAQs?"**
- ✅ Yes! FAQs are stored separately in your plugin
- ✅ Conversation history stays in database
- ✅ Just change API key in settings

### **"How do I monitor my costs?"**
- **Gemini**: Check Google AI Studio dashboard
- **OpenAI**: Check platform.openai.com usage page
- **WordPress**: Check "Reporting" tab for request counts

### **"What about the 'dynamic learning database'?"**
- ✅ You already have it (conversation logging)
- ✅ It's free (uses WordPress database)
- ✅ No extra cost
- ✅ Review manually or add AI analysis later

---

## 📚 **Sources**

- [Gemini API Free Tier Limits](https://ai.google.dev/gemini-api/docs/rate-limits)
- [Gemini API Pricing](https://ai.google.dev/gemini-api/docs/pricing)
- [OpenAI API Pricing](https://openai.com/api/pricing/)
- [OpenAI Pricing Documentation](https://platform.openai.com/docs/pricing)
- [Gemini Free Tier Guide 2025](https://blog.laozhang.ai/api-guides/gemini-api-free-tier/)
- [ChatGPT API Cost Analysis 2025](https://www.cursor-ide.com/blog/chatgpt-api-prices)

---

## ✅ **Action Items for Anson**

**Immediate (This Week)**:
1. ✅ Keep using Gemini Free Tier
2. ✅ Monitor "Reporting" tab daily to track actual usage
3. ✅ Note how many conversations/day you get

**After 1-2 Weeks**:
1. Review actual daily conversation count
2. If under 10/day → Stay on Gemini Free ($0)
3. If over 10/day → Switch to OpenAI GPT-4o mini ($2-3/month)
4. I'll help you set up the API key if switching

**Monthly (Ongoing)**:
1. Check monthly costs in API dashboard
2. Review conversation logs for common questions
3. Add popular questions to FAQ
4. Optimize responses based on CSAT scores

---

**Need help with anything? Let me know!**

**Last Updated**: November 25, 2025
