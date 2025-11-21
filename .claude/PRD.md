# Comfort Comm ChatBot PRD

**Project:** Sales Up & Chatbot Pilot
**Owner:** Kelvin Saldana & Anson Dai
**Date:** 11/6/25

---

## Problem

Comfort Communication Inc. is a specialized telecommunications and broadband agent operating in Queens. The small operation team of 4-5 employees faces fluctuating customer volume, leading to employees and managers spending **10-20 minutes per customer** manually answering repetitive FAQs:

- **New Customer:** "What are the internet options/speeds for [Provider]?"
- **New Customer:** "What's the cost/price for [Plan]?"
- **Existing Customer:** "How do I check my bill?"
- **Existing Customer:** "Why is there an installation fee?"
- **General Support:** "How-to: Reboot modem/router."

### Pain Points

- **User:** Customers with simple questions must wait 10-30 minutes during busy periods
- **Business:** Lack of time and staff capacity are the biggest barriers to growth
- **Business:** Internal knowledge is not scalable - key info exists "from memory"

---

## Opportunity

Embed a dynamic, AI-powered chatbot on Comfort Communication Inc.'s WordPress website (https://comfort-business.com/). The bot will:

- Function 24/7 using NLP to understand and instantly answer Top 10-25 FAQs
- Free up the 4-5 person Operations team
- **P0 Goal:** Escalate complex inquiries to human operators by providing contact information

### Market Opportunity

- Improve customer satisfaction with scalable, 24/7 first line of service
- Scale business by freeing Ops team to focus on high-value, complex support

---

## Users & Needs

### Primary Users
- **Customers** (New & Recurring)

### Secondary Users
- **Operations Team / Employees** (beneficiaries of automation)

### User Needs

- **New Customer:** Get quick, clear answers about providers, plans, and pricing to make fast, confident purchasing decisions
- **Recurring Customer:** Find answers to common support questions (billing, troubleshooting) without waiting on the phone
- **Operations Team Member:** Reduce time spent on simple, repetitive questions to focus on complex support issues

---

## Proposed Solution

A dynamic, AI-powered chatbot using a **hybrid approach**:

1. **NLP-first:** Understands typed, open-ended questions in English
2. **Guided bubbles:** Fallback for users who aren't sure what to ask
3. **Human handoff:** Provides contact info for complex/sensitive inquiries

The bot will 'read' questions, find correct pre-approved answers from the FAQ Knowledge Base, and write natural, conversational responses **only from approved knowledge**.

### Top 3 MVP Value Props

1. **[The Vitamin]** - Provides 24/7, on-demand knowledge base (store hours, providers, how-to guides)
2. **[The Painkiller]** - Instantly answers top FAQs, eliminating wait times and freeing Ops team
3. **[The Steroid]** - Understands and answers questions in plain English

---

## Goals & Non-Goals

### Goals

- **[P0]** Increase Operations Team Capacity by delivering NLP-powered self-service that automates Top 10-25 repetitive inquiries
- **[P0]** Ensure high-quality UX with helpful, easy-to-use bot measured by CSAT score

### Non-Goals

- Multilingual support (e.g., Chinese)
- Direct integration with internal databases (billing systems, plan expirations via API)
- Automating tasks (e.g., processing mobile plan recharges) - focus is on automating **questions**
- Specific revenue targets

---

## Success Metrics

| Goal | Signal | Metric | Target |
|------|--------|--------|--------|
| **[P0] Reduce Manual Work** | Bot successfully resolving inquiries | Automated Resolutions per Week | >4 |
| **[P0] User Satisfaction** | Users find bot helpful and easy to use | CSAT Score (Rated "Helpful" / Total rated) | >70% |

---

## Requirements

### Legend
- **[P0]** = MVP for GA release
- **[P1]** = Important for delightful experience
- **[P2]** = Nice-to-have

---

### User Journey 1: New Customer

**Context:** NLP-first, designed to answer new customer questions. Preset bubbles are fallback.

#### P0 Requirements

- User can type natural language questions (e.g., "What are your Spectrum prices?" or "Are you open on Sundays?")
- Bot uses NLP to understand and provide direct answers from Knowledge Base
- Fallback: Display preset "bubble" questions

**Bubble Decision Tree:**

```
Main Menu:
├── Internet Plans & Pricing
│   ├── What are the options for Spectrum?
│   ├── What are the options for T-Mobile?
│   └── Residential vs. Business Plans
├── Mobile Plans
├── Store Hours & Location
└── How to Sign Up
```

- After answer, show "Was this helpful? Yes/No" prompt for CSAT

#### P2 Requirements

- User can ask about providers with address-based availability check
- Bot provides disclaimer that pricing/availability are estimates
- Multilingual support (Chinese)

---

### User Journey 2: Recurring Customer

**Context:** NLP-first, focused on common support issues. Bot 'reads' problems and finds correct how-to guides.

#### P0 Requirements

- User can type natural language questions (e.g., "my internet is slow" or "how do I check my bill?")
- Bot uses NLP to provide correct how-to guide from Knowledge Base
- Fallback: Display preset support bubbles

**Support Bubble Tree:**

```
Main Menu:
├── Billing Questions
│   ├── How do I check my bill?
│   ├── Why is there an activation/installation fee?
│   ├── Is my auto-pay set up?
│   └── Why did my price go up?
├── Internet Troubleshooting
└── Mobile Plan Questions
```

- After answer, show "Was this helpful? Yes/No" prompt for CSAT

#### P2 Requirements

- Real-time data lookups via API (e.g., "When does my plan expire?")
- Feedback loop to learn from conversations and improve answers

---

### User Journey 3: Security & Escalation (Critical Handoff)

**Context:** Universal fallback. P0 = simple "call us", P1 = capture user info.

#### P0 Requirements

- **Sensitive questions:** Bot must NOT attempt to answer
  - Examples: "What is my personal bill amount?", "What's my SSN on file?", "Change my password"

- **Response for sensitive inquiries:**
  > "For your account's security, I can't access personal billing or account details. Please call our team at (347) 519-9999 for secure help."

- **Cannot understand fallback:**
  > "I'm still learning, and I want to get you the right answer. Please call our team at (347) 519-9999, and they can help you directly."

#### P2 Requirements

- Upgraded escalation to capture user info:
  > "I'm still learning, and I want to get you the right answer. Can I get your Name and Phone Number for a human expert to call you back?"

---

## Appendix

### Resources

- **Designs:** Wireframe of the Chatbot
- **Meeting notes:** All Pursuit and Anson Meeting Transcripts
- **Other resources:** Google Doc: Chatbot Top FAQs

### Project Baseline & Metric Validation

The team's baseline for manual inquiries:

- **Daily Customer Volume:** ~5-10 customers/day (5 business days/week)
- **Daily FAQ Volume:** ~20-40% ask repetitive questions

**Validation:** Baseline confirms ~11-12 FAQ-based interactions per week. P0 Success Metric of >30% automation rate represents automating at least 3-4 of these known weekly interactions.
