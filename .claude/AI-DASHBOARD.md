API Call Cost Management: Each interaction costs $0.10; targeting 1,900 conversations daily, using a $20 budget.

Knowledge Base Development: Building an embedded vector database minimizes API dependency, enhancing cost control and continuous learning.

Weekly Updates Plan: Starting with weekly reviews for 4-6 months, then shifting to longer intervals based on data collected.

Chatbot Experience Improvement: User journey and UI enhancements are in progress, with a focus on input fields and fixing front-end issues.

Implementation Timeline: Prioritizing the vector database and dashboard; initial mock data testing to ensure effective deployment before real data usage.

Strategic Focus: Aiming for a cost-effective chatbot that aligns with telecom services, enhancing brand trust and competitive edge.


Notes
API Cost Management and Knowledge Base Strategy
The team decided to limit costly API calls by prioritizing an expanding internal knowledge base to reduce external queries (00:26).

Kelvin Saldana explained that each user interaction generates at least two API calls, costing about $0.10 per request, with a daily budget of $20 supporting roughly 1,900 conversations (00:26).

This cost structure risks escalating as website traffic grows, especially if users repeatedly test the chatbot.
To control costs, they will build an embedded vector database to store known answers, reducing reliance on external API calls.
When the chatbot can’t answer from the database, it will route the question through the API and log it for review.
This approach balances cost management with continuous learning by updating the knowledge base manually based on logged gaps.
They will implement a dashboard plugin within WordPress that tracks unanswered or new questions weekly, providing a prioritized list for manual database updates (03:57).

This dashboard will analyze frequent unanswered questions and suggest answers to add.
Weekly analysis was chosen to limit API usage while maintaining timely updates.
The team agreed on a human-in-the-loop process to vet new content, avoiding risks like incorrect or sensitive data entering the knowledge base.
This solution also helps identify irrelevant or out-of-scope queries, which the chatbot will be programmed to reject explicitly.
Question Frequency Analysis and Update Cadence
The team agreed to start with weekly updates and later reduce frequency after accumulating enough data over several months (10:08).

Anson Dai suggested starting with weekly review cycles to capture diverse user questions, then shifting to monthly or longer intervals after 4 to 6 months of data collection (10:08).

This phased approach allows the knowledge base to mature with a broad question set first.
After 4 to 6 months, less frequent updates reduce operational overhead while maintaining accuracy.
Kelvin confirmed the ability to add a dropdown option for update frequency (weekly, monthly, yearly) to the dashboard for flexibility (24:32).
The team agreed that only questions unanswered by the database but resolved via API calls will be logged, avoiding overload from all interactions (24:40).

This targeted logging ensures relevance and cost efficiency.
The chatbot will flag questions it cannot answer, signaling topics needing database expansion.
They plan to manually filter and select from the top 3 to 10 frequently asked but unanswered questions for inclusion.
This process prevents irrelevant or off-topic queries from polluting the knowledge base.
Chatbot User Experience and Content Management
The team reviewed the current chatbot flow and identified areas for content and UI improvement to better serve customer needs (16:51).

Kelvin shared a sample user journey prompting the chatbot to handle typical questions like pricing for Spectrum services using existing database info (16:51).

Anson requested adding essential signup details like phone number and email fields to improve completeness (19:51).
Kelvin acknowledged front-end styling issues, including UI glitches with editing and scrolling, committing to fixes (22:51).
They confirmed the chatbot allows editing both questions and answers directly, simplifying content updates (22:51).
Naming the chatbot was discussed, with "Steven" chosen as a placeholder reflecting internal culture (21:44).

The chatbot is programmed with guardrails to reject out-of-scope topics like cryptocurrency or stocks, improving brand safety (27:20).
They plan to track user feedback via thumbs up/down, helping identify unhelpful answers and further refine content (28:39).
Kelvin emphasized maintaining a manual review step to prevent faulty or sensitive info from entering the knowledge base.
Implementation Timeline and Ownership
The team outlined a practical path forward balancing development effort, cost, and operational control (05:36).

Kelvin will build the embedded vector database and dashboard plugin first, aiming to demo it soon for feedback (06:05).

The initial version will use mock data, transitioning to real user data after testing (26:56).
The dashboard will support weekly frequency by default, with plans to add flexible cadence options (24:40).
The manual update process will be owned by the Conflicts Communications team, ensuring content accuracy and relevance (26:54).
This phased rollout will help manage cost risks while enabling continuous chatbot improvement.
The team agreed to monitor API call volumes and costs closely, adjusting the frequency and scope of queries as needed (08:09).

Kelvin highlighted the risk of runaway API costs if unchecked, especially with open-ended user questions.
The human review loop is critical to maintain quality and avoid database bloat.
They will track success by measuring reductions in API calls and improvements in answering common questions.
Further feature requests, like UI enhancements, will be prioritized based on user feedback after initial deployment.
Strategic Vision and Competitive Positioning
The team emphasized building a scalable, cost-effective chatbot that reflects company expertise and protects brand integrity (26:43).

Kelvin underscored the importance of keeping the chatbot focused on Comfort Communications’ core telecom services, avoiding irrelevant content (26:43).

This focus supports clear customer journeys and reduces confusion.
The approach provides competitive differentiation by combining AI with curated human knowledge.
It helps build trust as customers get accurate, vetted answers quickly.
The chatbot’s ongoing learning via manual updates aligns with long-term goals of improving engagement without ballooning costs.
Anson framed the dashboard and update cadence as tools to capture customer intent and improve service quality over time (11:26).

This iterative strategy supports continuous business growth.
They see the chatbot as a way to gather real customer questions, informing marketing and product teams.
The human-in-the-loop model balances automation benefits with control and brand safety.
The team’s vision is a smart assistant that eases customer workload while managing operational costs sensibly.

Action items
Kelvin Saldana
Continue developing the embedded vector database and dashboard plugin with weekly analytics for unanswered FAQs (06:00)
Implement configurable frequency dropdown for AI analysis schedule (weekly/monthly/yearly) as per client feedback (24:00)
Enhance UI for easier editing of questions and answers, and improve user experience with scrollable lists (23:00)
Integrate guardrails to block unrelated questions and integrate thumbs up/down feedback analytics for quality control (27:00)
Provide demonstration of the updated dashboard and workflow to Anson and team after development milestones (06:00)
Anson Dai
Provide detailed service information for chatbot knowledge base, including business hours, contact details, and signup info to finalize conversation flows (19:00)
Review the current mock data and give feedback on chatbot responses and user journey; decide on chatbot name ('Steven') and other branding elements (21:00)
Monitor usage and frequently asked questions for first 4–6 months to inform knowledge base expansion and reduce AI analysis frequency over time (12:00)


Transcript

Hello. 
Sorry, the camera. 
You can hear me or are you ready? 
Oh yeah, I can hear you. I can hear you. 
Oh, okay, sorry. Okay, so I will start with so the cost of API calls. So I did a little bit of research. So currently I'm using Gemini, right? And Gemini, whenever you request something it's 10 cents basically. 
But. 
So let me show you. 
What do you mean by request something? 
Okay, so let's say I ask LLM, what is the price of spectrum? Now that's a request. So now it generates a generator request for just asking the question and for giving answer it's giving another request. 
Oh I see. 
Okay, so on. 
On a budget for. 
I put for $20 to have just like a request. So like just having a conversation. So there's two requests for a question and the answer. That's, that's one conversation. 
For $20 budget you can have about. 
A thousand nine hundred per day. That's yes. So also now depending on how many people would go on the website and. 
Also there are people who like maybe. 
Some, some people can tinker with the website, right. And they're just requesting calls and calls. So these are also things that I wanted to kind of limit as well. 
And, but I also wanted to speak on. 
Remember that feedback, the, that learning along the way from users response. So. 
It was, it's preferable that we make our knowledge base or our database to be bigger because the more response it gets, the less it uses API calls. So what I thought of, because. 
I. 
Was, I, I am able to build something like it's called an embedded vector database. It's, it's complicated and I don't want to complicate things and then you know, the chatbot will break because we're also limited on our weeks. So what I can do is. 
Build. 
A dashboard where if the AI, let's say whenever the user asks a question, if it doesn't get collected from the knowledge base or database that we have, it will send towards it will obviously pick up on our API keys to answer it if needed or if it doesn't answer it'll go to a dashboard in our plugin. So like if you go to your WordPress, you see how there's elementary and. 
Then it'll be our plugin. 
So there's going to be a dashboard there where if it doesn't answer the user's question, you can use. It's going to be like analysis that will tell you oh, these are. 
Most asked question of this week, let's just say for this week. 
And then it'll give you additional information and that additional information, you can basically. 
Manually use that and put it in your database. 
So right now it's going to be more like human. Like I have a human loop where. 
The person will have to manually update. 
The knowledge base, but you'll have. 
An. 
AI dashboard that would basically capture conversations. And if it doesn't respond with our current database, it will let you know. And then this is how we can train the model. 
Okay. 
Which is. 
And it's. And it's free. 
Yes. Right. That's the main point. Yes. 
Yeah. 
So that's how I'm going by. 
This is. 
What are. 
Do you see anything? 
No, I actually really like that idea. 
Yeah. 
Okay. 
Awesome. 
Yeah, yeah. So, yeah, that's what I was actually tackling all day, looking at the cost and. No, no worries. It's. 
It was pretty fun. But. 
Okay. 
I was just looking at. 
Yeah. The cost of how much. 
Because what I was seeing is that every time we chat. 
With it's just using an API request. 
And I could see like on, along the line if we're just letting it, you know, request AP requests to. Through an LLM, it's just gonna. 
You know, cost more and more. 
Yes. 
If, when, when your website gets, you know, more and more views. 
So if we are, we're just trying to let it teach, we're just gonna have that dashboard where it'll help you figure out what questions are being asked. And then it could be a weekly thing, a monthly thing. 
It's. 
It's up to you as well. I, I can, I want to build it first to see how it looks like, and then I'm going to show you if this is something you ask vision as well. 
Okay. So essentially. Sorry to interrupt. If you were going to continue. No, no, but essentially the gist of what would be happening is we're kind of keeping what you already built, except instead of CSV, we'll add a plus sign, whatever question answer category right there. Then there would be a dashboard to show us live frequently asked questions. And then we could try to get answers for that and then just incorporate it into that database of questions. 
That's correct. 
So like also. So, yeah, so instead of like you manually like looking at conversations, we can have an AI basically do that for you. So that would, and that would be also API request, but that's not a lot because we're going to do it Weekly. Right. It's going to just take all the questions and answers, figure out what it was being most asked, and it'll just. 
Give you, like, answer, basically. 
Okay. 
And then with that answer, we can. You can manually just put that in your database. 
Right? 
Right. How does that sound? 
Yeah, that's fine. That API request, though, would cost, like, for example, 10 cents or whatever, right? 
Yeah, yeah. It all depends on what LLM. They're basically around the same price. 
Okay. 
But, but I would just say. From, from what I figured out, it's. It looks small, but it adds up. I just, I. Yeah, and I just know, you know, I don't know if you ever need a chatbot, but I, I sometimes, like, or like just an LLM, you just keep asking questions and questions. It technically, like, all adds up. 
And especially for like a small business like you. 
You never know. Like whenever somebody just wants to just. 
Run up your API calls. 
Right. So it's just safeguards. Right. We want your chatbot to know already. 
The answers through your database. 
So you shouldn't even look up answers online because you already have it. And even. It'll help, it'll help you guys understand, like, if we have like a AI analysis dashboard in the background, like in your plugin, it will also help you guys understand what are being asked. 
I'm guessing. I'm not quite understanding how the API would work and how much it would cost. Is it. Every new question that comes up, it'll, like, store it and that would count as a request. Or like, it would just, for example, have like a, A log of conversations. And then every week or month or whatever, it would just go through all these logs, like, oh, yeah, what is your vision for that? 
I was thinking a weekly basis because, you know, if, if people come in daily, I'm not sure yet because I think daily is like too much. 
Too much. 
Too much calls or even. 
Yeah, I was envisioning weekly. Right. And with those, and things get logged into your database, but if you just have it there, I, I was thinking of like, you know, it needs. 
To be removed someday because your database is going to get bigger and bigger. 
So if it's a. 
Weekly basis, we can remove any. 
Conversation log. Or depending on how much you guys want, if it's a monthly basis, you. 
Want to remove the conversation logs from the month, we'll remove it, but we'll have analysis of that month. 
That way you can just keep, like. 
Like an AI summary over it. 
What if instead we have like an option of how often like a drop down for example, so then drop down like because what I'm thinking is a week makes sense, right. Once we're starting off. Because we just want to see what question type of questions they ask. And that continued for maybe like I. 
Don'T know. 
Four to six months somewhere along that timeline. And I'm thinking like that's given a decent amount of time, right. It should capture a lot of different questions and then we can get those answers. So we can kind of reduce the frequency of the need for what's it called it. Looking through the logs and figuring out questions. 
Okay, so. 
Can you just rephrase the last part? 
From where? 
From. 
The four to six months part. 
Yes, yes. 
Okay. So I was thinking right as we start, right. We start with a small set of questions and answers, right. And then if we have it on like a weekly basis, they will be able to capture those types of questions that customers ask. Right. Now once we accumulate a decent amount of questions, say over a four to six month timeline, we can just reduce the frequency of when the AI would go through all the questions and come up with like new questions that are asked that we didn't take into account. 
Got it. 
That makes sense. 
Okay, so just basically having a timeline for four to six months, have it just whatever questions are most frequently asked, right? You just want to like figure out what is being asked for those four. 
Four to six months and then from. 
There you will compile. 
A FAQ for it, right? 
Yeah, like a quote unquote final version. I was thinking because I was kind of explaining the reasoning why I would want a drop down for how long the AI would I guess run it, run through all the logs and find the questions that it couldn't answer. If that makes sense. 
Sort of. 
I'm trying. 
Because I'm also thinking like what if? No, but I don't think it's, it's going along the same line. I'm just trying to make sure like. 
As well, like guardrails. 
What if somebody asks unrelated questions but it's most frequently asked, you know, I'm trying to figure out how. Because I don't want the AI to, you know, like if it's most, like if they ask most of the time sometimes, you know, it's not something you the A like we want to. 
Put in your database. You know what I mean? 
Right, right. Right. 
So. I can't, I'm gonna continue working on it. 
Instead of compiling like maybe three or like I don't know how many questions, like three to five or whatever. Maybe like a list of 10 or so. And then we can kind of say like, oh, you know, this question is not relevant. You can kind of ignore questions like this or whatever. 
Okay. 
Or, or like. I, I think this might be a little bit. I don't know how doable but it would be a solution. I, I don't, I just have no clue about the. Please actual doability of this. Like. Or what is the word for it? Whatever, if you are able to do it. 
Capability. 
Yeah. I don't know the term for it. But regardless, I was thinking like if it's not necessarily like related to like telecommunications like or like mobile plan or whatever. It would just be like directly give like. Oh, I do not know how to answer this. You are in the. You're asking the wrong type of question. I can't, I'm not programmed to answer this type of question. The situation. 
Here, let me also show you what actually maybe if we do like a. 
What do you call it, a workflow or. 
Like a user journey is what I meant to say. 
You see my screen? So let's start from here. 
Okay, so this is how we start off. Right. 
This is. 
Hi there. 
I'm comfortable Palms Metro Assistant. 
How can I help you with your. 
Internet TV or phone service today? So instead of these bubbles, let's. 
What is something. 
We should ask? I was maybe thinking like spectrum prices. 
Right. Because that was one of the questions we have in our database or anything. Well, what do you. 
Yeah, like let's see what it. If I were to open. Internet service with spectrum at this location, how much would it cost? 
Say one more time. 
Sorry. 
No, no worries. If I were to open Spectrum Internet Services. Yeah. How much would it cost? Whatever. Yeah, that's fine. Okay. 
So this was also based on your. 
Website as well, right? 
Is this, is this okay or. Because right now we're only taking the. 
Our knowledge base information. It's not looking up anything on the LLM. 
Right, right. 
Okay. 
Yeah. I mean that looks fine. 
Yeah. Yeah. 
Okay, so now let's say there was. 
Like a follow up or let's say. 
Because I know we don't have so much information I need. 
What information? I need to sign up. 
Okay. Oh, we can remove these. It's the styling as well actually. 
Okay. 
Yeah, read it, tell me. 
Confirm your service location and valid folder id. It's Missing a couple things. Like for example, we need like a phone number. Email. That's on the top of my head again, it's a little bit different based on what company? But that's for the most part. 
Yeah, yeah, for sure. It's giving you like a general idea. 
Okay, okay, let me. Okay, let me just see our database real quick. 
Also. Yeah, I forgot to say I added all these based on your. 
The website as well. 
Okay. 
Yeah. 
Oh, I actually don't know the business hours. Is there a way that you can. 
Oh yeah. It's 10:30 to 7:30. 
Okay. 
10:30Am to 7:30pm and this is every day. 
Okay. Actually, now that we're here as well, what is there a name that you want to call the chatbot? 
That way we can like remove any of these. Like. 
Let me think. 
You can, yeah, you can think about. 
It if you want later on too. 
But we can have it just a name for now. It's not really, you know, priorities. 
We can call it Steven, whatever. My boss's nickname. 
Okay. 
Okay, perfect. 
Yeah. 
And. 
Yeah, right here, look. Would you say like. 
Let me see if there is a second. Okay, so I, I think I removed the sign up. 
Maybe that's why they didn't give proper information. It took from the LLM as well. 
Because it was giving those astro signs. So it was probably an API request and then. But I can. 
Oh yes. Is there also like, I guess an easy way to edit the. The answers? For example, like I wasn't sure if I saw an edit option. I wasn't looking too closely at that. 
Oh yeah. 
They should cross my mind if like. 
Yeah, sorry. Do you mean like these questions or. 
I was thinking of the answers. Like for example, like for if it was like missing a part where we wanted to add something in or change something, we wouldn't have to like completely delete the entry and then. 
No, no. All right, now I see there's a. 
What do you call it? 
It's a front end issue. You saw how it just pops up and then it gets removed. So yeah, you can edit the question. 
And the answer here. 
Okay, awesome. 
Awesome. Yeah. 
But I'll fix as well. Also deleting it. Yeah, this is into add. It will be here. I can also, you know, these are small things. These are I guess like user interface things where you don't have to scroll. 
All in the bottom which is have it like a box where you can scroll through. 
It's easy. Yeah, but. Okay, but other than that, how do you feel about having like that weekly month, or you say you want it for six months, just know what's frequently being asked. And then we can also build from there as well. But I'll keep this for now since this is already information, you guys. 
Yeah, no, I was just thinking if there was like just some way to change the frame frequency of. Because obviously we want to start weekly. Right. But then later on. 
Oh, freak. 
Oh. To monthly and. Or yearly. Yeah, yeah. Yes. Yes. Oh, yeah, I can definitely. 
Yes, definitely. 
And then I guess would the database be like keeping track of every single question and answer or like question response? Or would it just be like, for example, like questions it doesn't recognize? 
For example. Oh, good question. 
I was. 
I feel like that would be more efficient. 
What to have just what's not being answered. 
Yeah, like the one. The questions or responses that it. Like it's out of the norm. 
Yeah. 
Yeah, that's exactly how I was thinking where. Okay, if it doesn't get from the knowledge base, it will obviously get it from an API call. Right. But now knowing that it's getting from an API call, it means that it's not being prioritizing the knowledge base. So then that's giving like a trigger. We can make a trigger where it's like it's not collecting from here and. 
It'S getting frequently asked. 
You know what I mean? Yes. 
That's how. 
Yeah, that's how I envision it. 
Or we can have the chatbot to be like, oh, I don't have this information now as well. And then that'll give us a signal. 
Like, oh, this is something that the customer is asking. 
But yeah, it shouldn't give like all of it. 
Right, right. 
Because that would. 
Anything. Yeah. 
Overload the database. 
Yeah, anything that's. That's needed for. To upload the knowledge base on its own. Because having the AI do most of the work can I see a lot. 
Of things that can break, like give. 
Even wrong information or maybe some people post sensitive information there. And now that's getting created as a. 
Knowledge base and then we. 
And then, you know, it's giving out answers on that. 
Right, right. That would not work. 
So that. So I think having a human in the loop for now. 
Yes. 
Like for this project, it's. 
It's. I think it's perfectly fine. 
Okay. 
Yeah. 
Does this sound good? 
Yes, that sounds great. 
Okay. Okay. 
So then. Yeah, that's. That's what I was thinking with. 
Because of the cost, I was like, okay, we want to also make sure. 
Whatever knowledge we're adding it needs to. 
Be sent to a human first, which. 
Will be anybody of you guys in conflict communications. And yeah, that's what I got so far. I'm going to continue working on it. Let me. Right now, this is all mock data. I'm not, I'm gonna tell you this honestly, this part. 
Sorry. 
All right, so this is actual data. So I was testing it out, right? Like, I was asking questions that it shouldn't be asking as well. 
Whereas, like, what is bitcoin? It says, hey there, we can't deal with cryptocurrencies. 
I can tell you it was telling me a little bit about cryptocurrency, which is not something we want. 
Right. 
I fixed, I fixed that. 
Right. 
I was like, what is. 
The stocks? 
Right? It's always going to be now guardrails to directly ask, like, no, we are. 
A chatbot just for comfort communication only. 
Ask related questions about that. 
Right? 
And if you need, you know, assistance. 
Just call you guys. 
So just like, even this, like. 
Helps you guys, right? Getting feedback safe. 
If you see a lot of. 
This. 
Is what I'm also envisioning where if there's a lot of thumbs down, those are probably, you know, questions that they're not being answered. 
They're not being helpful, right? 
Why they're not helpful. We can also have like an analysis for you guys to be like, oh, these are thumbs down. This is the questions they asked and this was the answer given. 
That's another analysis that you can also take with you. 
Yes. Okay. 
And this was the mockup right now, like I said, this is not it. This is not what I, I envisioned, but I just kind of wanted to figure out like, it would. When people ask kind of similar questions, it would give you. 
Like the questions. 
I don't know if it'll give you the questions be asked, but I think it's just going to be like a summary. It'd be like, these are questions that are potentially being asked. 
Here's a suggestion of FAQ questions. 
You can see it and then you. 
Can implement it on the knowledge base right here. 
But I can also make this more. 
User friendly as well. 
That would be appreciated. 
Yeah. Okay. Yeah, I think that's all right. Yeah, that should be all for now. 
Okay, awesome. Thank you so much for your timely research. 
No, no, for sure. But thank you also for helping me. 
Give feedbacks as well. I want to definitely make this, you. 
Know, travel just for you guys. 
Yes, of course. Thank you. 
So I appreciate everything. All right. I'm gonna let you get some rest, okay? 
Okay. You, too. Good night. 
Take care. 
