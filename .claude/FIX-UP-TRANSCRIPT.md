Meeting with Anson: First Chatbot Prototype

Generated AI Summary:
Chatbot Design: Flexible chatbot with easy CSV uploads for FAQs, enhancing user experience and simplifying updates.
Feedback Loop: The feedback system uses ratings to improve content relevance and quality over time.
Session Management: Chatbot sessions will pause after inactivity instead of clearing, improving user resumption.
API Research: Exploring Google Gemini's costs and limitations; investigating API keys for OpenAI’s ChatGPT.
Learning Database: A separate database will capture user interactions for enhanced chatbot learning and accuracy over time.
Project Timeline: Status update expected Wednesday, focusing on core functionality before adding enhancements.

Notes
Chatbot Functionality and Knowledge Base Management
The team agreed to focus on building a flexible chatbot with an easy-to-manage knowledge base that supports continuous updates and user feedback (00:03).

Kelvin Saldana designed the chatbot to accept CSV uploads with three columns: question, answer, and category, allowing the team to add or update FAQs easily (03:29).

The chatbot shows the top four preset categories for quick navigation, improving user experience.
Kelvin plans to remove unnecessary UI elements like avatars and appearance settings to focus on core functionality.
The CSV system can either add new entries or replace the whole dataset, but the team prefers downloading and updating the full CSV before re-uploading.
This approach simplifies ongoing knowledge base maintenance and minimizes duplication risks.
The chatbot includes a feedback system with thumbs up/down ratings, which tracks helpfulness and captures prior questions and answers to help refine the knowledge base (11:22).

This feedback loop is intended to improve content relevance and quality over time.
Kelvin emphasized the importance of this feature as a key driver for continuous chatbot improvement.
Security and Access Controls
Security concerns around the chatbot’s session management and access were acknowledged, with plans to improve session handling and environment access (00:47).

The current chatbot session times out after two minutes of inactivity and clears the conversation history, but Anson Dai suggested pausing sessions instead of clearing to allow users to resume easily (00:59).
Kelvin agreed to adjust the timeout behavior to reflect this user-friendly approach.
Kelvin will explore ways to bypass security restrictions on the staging environment to allow easier developer access without compromising overall security (23:28).
He plans to provide updates on this by the end of the day or shortly after, enabling faster iteration and testing.
API Integration and Cost Considerations
The team discussed API usage options, weighing free tiers and premium services for optimal chatbot performance (12:11).

Kelvin is currently using the Google Gemini free API but is uncertain about its scalability or cost at higher volumes (12:49).
He will research potential costs and limitations for Gemini and other APIs, including OpenAI’s ChatGPT, by Wednesday to guide future decisions.
The chatbot supports switching between API keys for Google Gemini and OpenAI’s ChatGPT (including Plus tier), enabling flexible backend service choices (14:39).
Anson will investigate obtaining the necessary API keys for ChatGPT from the billing section.
Kelvin will deliver a detailed cost analysis covering database storage, dynamic learning feedback, and API usage by Wednesday to help the team budget appropriately (22:02).
Dynamic Learning and Database Strategy
The team is moving toward a chatbot that learns and improves over time through user interactions stored in a dedicated database (17:40).

Kelvin proposed creating a separate learning database to capture keywords from user questions and responses, which will enhance the chatbot’s memory and response quality (21:07).
He noted there might be additional costs associated with database storage and operations.
This database will support a feedback loop for dynamic learning, improving chatbot accuracy and relevance.
Anson confirmed this feature is not a must-have initially but valued for long-term improvement and agreed to the experimental approach.
Kelvin committed to providing a prototype or progress update by Wednesday or Friday, depending on workload and holidays, to demonstrate functionality (19:40).
Timeline and Delivery Commitments
The project is progressing steadily, with Kelvin dedicating full days to development this week and offering flexible delivery options (19:54).

Kelvin will provide a status update on Wednesday, with the possibility of extending to Friday if more time is needed (20:09).
Anson emphasized not rushing the work but staying on track with these milestones.
Kelvin plans to communicate proactively with Anson if delays occur, ensuring transparency and alignment.
The focus remains on delivering a well-functioning chatbot with core features before expanding further enhancements.
User Experience and Interface Refinement
The chatbot UI is functional but needs minor tweaks to improve usability and appearance (13:33).

Anson acknowledged the current design is acceptable but open to light styling improvements.
Kelvin will remove unnecessary features like avatars to streamline the interface and focus on essential chatbot functions.
The planned UI changes aim to enhance clarity and ease of use without overcomplicating the design.

Action items
Kelvin Saldana
Research and report on website security setup and potential security improvements (02:05)
Enable simplified knowledge base management within plugin and remove unnecessary features (avatars, appearance) (10:47)
Implement feedback mechanism within chatbot to rate answers and track improvements (11:23)
Investigate Google Gemini API limits and alternatives including OpenAI ChatGPT API; prepare API key setup instructions (12:49)
Create and test a dynamic learning database prototype for chatbot feedback and question learning (16:50)
Provide detailed cost analysis on dynamic learning database, feedback loop, and API usages (18:26)
Research and share options to bypass security for staging environment access to support development (23:28)
Communicate progress updates, including potential schedule changes for prototype completion (19:54)
Anson Dai
Research and obtain API key for ChatGPT or confirm existing access; follow up with Kelvin on implementation details (15:10)
Coordinate internally to check for existing website database availability or confirm lack thereof and communicate findings to Kelvin (20:43)
Support security investigation by inquiring with internal team or boss for relevant credentials or insights (23:45)


Transcript
Anson Dai
All right. Because I'm not exactly sure like, how we would. Well, frankly, I don't think my boss really knows too much. Like, he doesn't really work with the website. I'm not sure how he got it running, to be honest. Maybe he hired somebody. 
K
Kelvin Saldana
00:26
Okay. Okay. So, yeah, it's my fault as well. I should have had that ready. Like what. What credential? I mean, not more credentials, but what ex. What page I would need to log into. 
A
Anson Dai
00:42
I mean, who could have foreseen this? 
K
Kelvin Saldana
00:47
But okay, so. Yeah, so that's what is working on right now. Also, it's. It all has. Has a two minute. 
A
Anson Dai
00:57
I like that. 
K
Kelvin Saldana
00:59
Yeah. I. Yeah. So as a closing session for two minutes, and then if there's still no response two minutes, it'll close the whole thing and then everything gets cleared out. 
K
Kelvin Saldana
01:08
And our new history will start again. 
A
Anson Dai
01:11
Okay. 
K
Kelvin Saldana
01:12
And. Yeah, and this. Anything that's in this window should have. 
K
Kelvin Saldana
01:17
Should have what you call it. 
K
Kelvin Saldana
01:22
Context from previous questions. 
A
Anson Dai
01:24
Okay. Okay. Although just one thing, like you have a times out. I think we don't necessarily want it to like, just erase everything. I feel like it should be like, oh, we paused the session for now. If you want to continue, we can just type in chat, like in the continuity left off. 
K
Kelvin Saldana
01:52
Okay. 
A
Anson Dai
01:53
Okay. 
K
Kelvin Saldana
01:55
No problem. 
K
Kelvin Saldana
01:57
Yes, I appreciate that. Anything else? 
K
Kelvin Saldana
02:04
Oh, what did you think about it? 
A
Anson Dai
02:05
Oh, no. I was just saying. And then we kind of need to figure out the security part of it. Right. 
K
Kelvin Saldana
02:10
Okay. 
K
Kelvin Saldana
02:11
Yes. 
A
Anson Dai
02:14
Do you have an idea of how we would do that? 
K
Kelvin Saldana
02:42
Yes, actually, give me a second. Let me just look on my history. 
A
Anson Dai
02:46
Of course, of course. 
K
Kelvin Saldana
03:03
And also, is this. Is this something that. That you envision as well? 
A
Anson Dai
03:11
What do you mean? Like the chatbot so far? 
K
Kelvin Saldana
03:14
Yes, yeah. 
K
Kelvin Saldana
03:15
The chatbot so far? 
K
Kelvin Saldana
03:16
Yes. 
A
Anson Dai
03:16
Yeah, no, I. I absolutely like the idea. 
K
Kelvin Saldana
03:20
Okay. 
A
Anson Dai
03:20
Yes. 
K
Kelvin Saldana
03:21
Okay. 
A
Anson Dai
03:22
I think, of course it's very preliminary. We do need to obviously add more to its knowledge base. 
K
Kelvin Saldana
03:29
Of course. Oh, I'm sorry. I'm gonna also show you how easy it is for you guys if. If there is more questions you want. 
K
Kelvin Saldana
03:37
To put into it's CSV folder. 
A
Anson Dai
03:42
Oh, okay. 
K
Kelvin Saldana
03:43
Yeah, yeah. So instead of me always putting it in for you guys, I. I made it so where you can just have a CSV folder and it'll. It's three columns, so question, answer, and category. And so the category. So remember we also had preset bubbles. 
A
Anson Dai
04:01
Yes. 
K
Kelvin Saldana
04:01
If you put them in correct categories, it will show you top four categories in the beginning. 
A
Anson Dai
04:08
Okay. 
K
Kelvin Saldana
04:08
But also they could just, you know, ask a question and it would just. 
K
Kelvin Saldana
04:13
Come up as well. 
A
Anson Dai
04:14
Okay, understood. 
K
Kelvin Saldana
04:20
Okay, Let me just look at my history. 
K
Kelvin Saldana
05:20
Okay, so after the meeting, I can. 
K
Kelvin Saldana
05:24
I can check again and I'll tell you. 
A
Anson Dai
05:28
Okay, okay, Wait. Check on what exactly? 
K
Kelvin Saldana
05:33
Oh, check to see what site or what exactly? 
A
Anson Dai
05:40
The security thing. 
K
Kelvin Saldana
05:41
Right, for the security, exactly. 
A
Anson Dai
05:42
Okay. Okay. Yeah, okay, that's fine. Yeah. Okay. 
K
Kelvin Saldana
05:45
But it's just to go back to that knowledge base. 
K
Kelvin Saldana
05:49
So over here at the end. 
A
Anson Dai
05:53
Okay, so this is. 
K
Kelvin Saldana
05:54
So this is where the plugin is. 
K
Kelvin Saldana
05:56
Right, Right here. 
K
Kelvin Saldana
06:00
It's. It was a skeleton, it was a chatbot created already, but I build most of the functionality. So the CVS API tools, I mean, API keys you want to use, most of them were created by me. 
K
Kelvin Saldana
06:18
And. 
K
Kelvin Saldana
06:21
And also there's what they had was more appearance. So like customization of how you want it to look, which is not something we want, is more of the functionality. 
A
Anson Dai
06:35
Functionality, yes. 
K
Kelvin Saldana
06:36
Yeah. Okay. So this is. It was called this spot. It'll be here. Okay. And Knowledge Navigator, what we're going to look at all the way here at the bottom will be FAQs, imports. Right. 
K
Kelvin Saldana
06:59
There's a template you guys can use if you don't have one yet, but it's just three columns. 
K
Kelvin Saldana
07:05
Question, answer, category. 
A
Anson Dai
07:07
Oh, very convenient. 
K
Kelvin Saldana
07:09
Yes. 
K
Kelvin Saldana
07:10
So be here. And this is how. 
K
Kelvin Saldana
07:14
I made hours, remember? So the question, answer, and then the. 
K
Kelvin Saldana
07:18
Categories here you upload will show you exactly what they have in here. And as simple as that, you just. 
K
Kelvin Saldana
07:29
Put upload, import, and you'll see it create here and then just save settings and then that knowledge will go straight to the chatbot. 
A
Anson Dai
07:37
Okay, so just quick question. So how. I guess my question is, so if we upload a CSV right, in that format, it'll just continue adding on to it. 
K
Kelvin Saldana
07:53
Or you can also. Yeah, let's say you. It could add on, but sometimes, you know, there could be duplicates. I always assume we can always have like your own CSV and just add on to it and then. 
K
Kelvin Saldana
08:10
Just replace it right here. Replace all existing if it can. 
A
Anson Dai
08:14
Okay, okay. 
K
Kelvin Saldana
08:15
And then, and then you can upload the updated one. If it's confusing, I can always be. 
K
Kelvin Saldana
08:20
Like, replace whatever we currently have to. 
K
Kelvin Saldana
08:25
Just have your new one. So it's up to you. 
A
Anson Dai
08:30
Instead of replacing existing. Instead, if we had, just to make it easier for everyone, if we could just download what we have and then just edit it like there and then upload it once, like we're done with it. 
K
Kelvin Saldana
08:47
Okay, say. Say that one more time. 
A
Anson Dai
08:50
So like whatever we have. Right. Obviously we first can upload one after we made it and I guess if there isn't one, just. It could maybe give us a template or whatever. But I guess that's not really needed because we're gonna start from somewhere. Right. So we would just download a CSV of all the entries we already have and then we can obviously update it or edit it. 
K
Kelvin Saldana
09:22
Oh, an updated version. 
A
Anson Dai
09:24
And then we can upload that and then just rewrite everything in one go. 
K
Kelvin Saldana
09:29
Okay, so you prefer to just update it here? 
A
Anson Dai
09:35
I don't quite understand what you mean. 
K
Kelvin Saldana
09:38
Oh, let's say. Actually that makes sense. So like once I guess we could have a foundation instead of a C. A CSV. We can have this already here. But let's say you can have like. 
K
Kelvin Saldana
09:54
A plus sign here and you can just continue adding. Is that easier or. 
A
Anson Dai
09:58
That probably would be easier too. 
K
Kelvin Saldana
10:00
Yeah, yeah. 
K
Kelvin Saldana
10:01
Right. Just everything inside the plugin. 
K
Kelvin Saldana
10:02
Yeah, yeah. Sorry. 
K
Kelvin Saldana
10:05
Thus, I had it like this because that was my way of trying to manually put information. But I think if. If you guys are. Are efficient and just working with the plugin itself, I can just have like all you add. 
A
Anson Dai
10:21
Yeah, that'll be perfect. Honestly didn't cross my mind until you mentioned it. 
K
Kelvin Saldana
10:25
Yeah, yeah, no, me too, literally. That's why I was like, I could. We could do that too. Okay. 
A
Anson Dai
10:29
Okay, that would be perfect. 
K
Kelvin Saldana
10:33
Do you. Do you want me to just remove the CSV? 
A
Anson Dai
10:38
Yeah, I mean, if. If it's just easier to just straight up add it. Yeah, like, why not? 
K
Kelvin Saldana
10:43
Okay. 
A
Anson Dai
10:46
Okay. 
K
Kelvin Saldana
10:47
And like I said, I took the skeleton of it. There's a lot of things here that we're not going to need. And I could remove them for you as well. 
K
Kelvin Saldana
10:55
Right. 
K
Kelvin Saldana
10:55
I like avatars. These are things, I don't know you guys really want, like appearance. But I was gonna say. Let me finish. 
A
Anson Dai
11:10
Like the actual technical portion of it. 
K
Kelvin Saldana
11:12
Yeah. 
K
Kelvin Saldana
11:13
All the core functionalities. And then we can go one by one and be like, oh, and I can tell you which one, what they do. But most likely we won't have. We won't need avatar appearance. 
A
Anson Dai
11:22
We will need that. 
K
Kelvin Saldana
11:23
Yeah, we're gonna. Oh, hey, I'm sorry. Another, the reporting page. So if you saw, when they answer a question, you saw a little plus, I mean a little thumbs up and. 
A
Anson Dai
11:39
Thumbs down to rate it. 
K
Kelvin Saldana
11:42
Yeah. So this can also check if it was helpful or not. And if it wasn't helpful, I was gonna have the previous question they. They asked and the answer give is given. And maybe that helps you maybe rewrite your. Your knowledge base. 
A
Anson Dai
12:03
Okay. 
K
Kelvin Saldana
12:03
Or, or if this is even useful. That's another thing. 
A
Anson Dai
12:07
Yeah, I think this is great. Like, I always. Feedback is always great. 
K
Kelvin Saldana
12:11
Exactly. Yeah. Okay. Okay. 
K
Kelvin Saldana
12:17
Yeah. 
K
Kelvin Saldana
12:19
Other than that's what I have been doing for. 
K
Kelvin Saldana
12:25
Was it the past week? Yeah, yeah. 
K
Kelvin Saldana
12:29
And also. Oh, the API keys. So right now I'm using Gemini, which is. I'm using it for the free version. It's. I believe anybody can use it. They could just sign up for Google Gemini and they should get a free API. 
A
Anson Dai
12:49
Right. 
K
Kelvin Saldana
12:51
But at scale, I don't know how, how much usage that will be for Gemini API. So right now I can do a little bit more research on that, actually. But over here also, I'm going to have to change where things are. In order for the API to work, it needs to give you a success message. 
K
Kelvin Saldana
13:19
It'll show up here on messages. 
K
Kelvin Saldana
13:21
But this will change because I don't. 
K
Kelvin Saldana
13:23
Think this is a good UI design. 
A
Anson Dai
13:27
Okay. 
K
Kelvin Saldana
13:29
That was just a little ticket. 
A
Anson Dai
13:33
Okay. 
K
Kelvin Saldana
13:33
But other than that. Well, any other, any additions that you want to add or styling is something that I was also trying to look at as well. If that's a thing. 
A
Anson Dai
13:48
I mean, it doesn't look too bad already. 
K
Kelvin Saldana
13:51
Yeah. 
K
Kelvin Saldana
13:52
Okay. 
A
Anson Dai
13:52
Yeah. 
K
Kelvin Saldana
13:54
Okay. 
A
Anson Dai
13:56
It could use a little bit of tweaking, but, you know, it's not too bad. 
K
Kelvin Saldana
14:00
Yeah, exactly. 
K
Kelvin Saldana
14:02
Okay. 
A
Anson Dai
14:02
Okay. So I guess one thing I would want to ask. I suppose you said something about, like, limitations maybe with like, the free version of Gemini. 
K
Kelvin Saldana
14:19
Exactly. 
A
Anson Dai
14:20
Okay. We do. I think my boss does have. I forgot what it's called. Chat GPT. Like plus or premium. Whatever. It's like the second tier, the one above. 
K
Kelvin Saldana
14:39
Okay, yeah. And we have that as well here. So if you go to general. 
K
Kelvin Saldana
14:45
Oh, yeah, I forgot to also mention this. 
K
Kelvin Saldana
14:47
We have these options. Open AI will be CHAT GPT. 
A
Anson Dai
14:52
Okay. Okay. 
K
Kelvin Saldana
14:53
Yeah, yeah. 
K
Kelvin Saldana
14:54
And then when you save it, this API key will be changed to ChatGPT. 
K
Kelvin Saldana
15:01
And then you got GPT API keys. 
K
Kelvin Saldana
15:04
Because they're all different in the code, you have to make separate ones for each one. 
A
Anson Dai
15:10
Do you mind if I asked, like, how we would get the API key? Would we just, like, prompt it, like, oh, can you provide me your API. 
K
Kelvin Saldana
15:18
Key for Open for Chat gbt? 
A
Anson Dai
15:21
Yeah. 
K
Kelvin Saldana
15:21
For example, I. Let me see. 
K
Kelvin Saldana
15:25
Let's say I always do this. 
K
Kelvin Saldana
15:31
So I believe you just log in. 
A
Anson Dai
15:35
Okay. 
K
Kelvin Saldana
15:36
Usually in your billing section. 
A
Anson Dai
15:38
Okay. Oh, okay. Okay. I'll have a look. Have a look. 
K
Kelvin Saldana
15:42
But, but yeah, I could do. 
K
Kelvin Saldana
15:44
Yeah. 
K
Kelvin Saldana
15:44
Because I tried a new jbt, but I don't have. 
K
Kelvin Saldana
15:49
What you call It. 
K
Kelvin Saldana
15:52
My free tier was over, so. So like right here. 
A
Anson Dai
15:55
Oh yeah. 
K
Kelvin Saldana
15:57
Settings, I believe and. 
K
Kelvin Saldana
16:14
Actually. 
A
Anson Dai
16:28
It's okay, no worries. I could just do the research layer on my own. Okay, no big deal. The second question, I guess I had. I know we discussed the. I forgot the term for it. Was it like dynamic learning chatbot? Yes. 
K
Kelvin Saldana
16:50
So like. Okay, so if it goes to another page or. Well, right now you only have a homepage, but it should. It should take. It shouldn't understand the home page and get that context and help any. 
K
Kelvin Saldana
17:07
User understand the website as well. Or is. 
K
Kelvin Saldana
17:13
Or Sorry, what was it? 
A
Anson Dai
17:15
So I think we discussed like maybe a meeting or two ago mentioned a chat bot that would. Based on like what we feed it would be able to like in a sense learn what type of questions are asked and then build responses based on that. 
K
Kelvin Saldana
17:37
Yes. Okay, perfect. 
A
Anson Dai
17:38
I was wondering if you had an update on that. 
K
Kelvin Saldana
17:40
Yes. 
K
Kelvin Saldana
17:42
I wanted to make sure if. Good thing you asked. I want to make sure if you wanted to use a different database or. Or if you guys have a database inside WordPress. That's why I also wanted access. A full access to see if. What exactly am I working with? But I can always create a new database and that database will be, I guess where it learns on its. It will learn through the users or. 
K
Kelvin Saldana
18:18
Even questions being asked. 
K
Kelvin Saldana
18:19
So. Yes, that's also a possibility. 
A
Anson Dai
18:22
Okay. I know you mentioned there might be a cost to. It is. 
K
Kelvin Saldana
18:26
Yes. 
A
Anson Dai
18:27
Okay. Do you know how much I. 
K
Kelvin Saldana
18:31
No, no, I. Again, I think I didn't get much to the cause and that's totally my fault. 
A
Anson Dai
18:37
I don't know. No, no worries, no worries. I. I'm not gonna blame you. 
K
Kelvin Saldana
18:41
No, no. 
K
Kelvin Saldana
18:41
Yes. 
A
Anson Dai
18:42
But honestly, I just remembered we've had this conversation. No, it's been a busy few weeks. 
K
Kelvin Saldana
18:48
No, no worries. Yes. That was also totally on my mind having like a feedback loop for. 
K
Kelvin Saldana
18:54
For the chatbot for sure. 
K
Kelvin Saldana
18:56
But if. For now, would you want me to create a database of my own and see how like to test it out? Basically. 
A
Anson Dai
19:09
Yeah. Sorry, Continue. 
K
Kelvin Saldana
19:11
No, no, I was gonna say just to test it out and. And show you that progress and from there. Because remember, that's also. It's not a must have. But I want. I want to make sure it's done properly. 
A
Anson Dai
19:28
Yes. 
K
Kelvin Saldana
19:29
So I can give you that by. Oh, today's thing, Thanksgiving weekend. I don't know if you guys are. 
A
Anson Dai
19:40
Still busy Friday, like this upcoming Friday. 
K
Kelvin Saldana
19:43
Right, Upcoming Friday. 
K
Kelvin Saldana
19:46
I. 
K
Kelvin Saldana
19:48
Or even Wednesday. I think Wednesday. Will. 
K
Kelvin Saldana
19:51
I. I probably have. 
A
Anson Dai
19:54
I mean, I don't Want to rush because that's only two days. 
K
Kelvin Saldana
19:57
Right, Right. 
K
Kelvin Saldana
19:58
But mind you, I'm also. I've been working on this all day today, tomorrow, and Wednesday, either way. So. Okay, how about this? I'll give you an update for Wednesday. 
A
Anson Dai
20:09
Okay. Because I don't want to rush you. Right. Because. 
K
Kelvin Saldana
20:11
Yeah, no. No worries. 
K
Kelvin Saldana
20:12
No worries. 
K
Kelvin Saldana
20:15
I. I do. 
A
Anson Dai
20:18
Wednesday's fine if you're able to, but Friday is also fine if you're able to as well. 
K
Kelvin Saldana
20:23
Okay. 
A
Anson Dai
20:24
Yeah. 
K
Kelvin Saldana
20:24
Okay. 
K
Kelvin Saldana
20:26
So I'll just write, but I can always text you. I'll be like, oh, listen, Anson, I think Wednesday. I think I'll still need a couple more times, a couple more days to finish it. 
K
Kelvin Saldana
20:38
And Friday will be the new day. 
A
Anson Dai
20:41
Okay. 
K
Kelvin Saldana
20:42
Yeah, sounds good. 
A
Anson Dai
20:43
Yes, absolutely. So I guess because I'm not exactly sure if we have data, I really doubt we have a database for this. So do you mind if I ask, like. Like, how do I word this? Like, how that database will be used? Just to like, to store the questions and answers. Right. 
K
Kelvin Saldana
21:07
Yeah. So how the database is going to be used is it. Takes. So let's say there's questions. I actually still need to do a little bit more research. Both, at least from a general aspect. Whatever question it asks, it's going to take keywords and basically it'll be its own memory inside that database and that information will be stored there and it will better your chatbot. 
A
Anson Dai
21:43
If you can do create database. I'm assuming there's no extra cost, so. 
K
Kelvin Saldana
21:51
So I think that's where the cost will also be as well. 
A
Anson Dai
21:53
Okay. 
K
Kelvin Saldana
21:55
So. So, so for how much it is that as well? No, no. 
A
Anson Dai
22:01
Okay. 
K
Kelvin Saldana
22:02
But I would. I would definitely give all the information the cost for. For a learning feedback for the chatbot. Oh, and also if you want an. 
K
Kelvin Saldana
22:15
API costs as well because there's, you. 
K
Kelvin Saldana
22:19
Know, different APIs are, you know, either smarter or does. Does better than other API keys. 
K
Kelvin Saldana
22:28
Yeah. 
K
Kelvin Saldana
22:29
Yeah. So I'll give you that. Wednesday. 
A
Anson Dai
22:34
Sure. Yeah. I mean, like whenever it's convenient for you, I guess. Yeah, that'd be perfect. Like a database cost, the learning thing and then like APIs and I guess what they do. Yeah, that'd be perfect. I'd appreciate that. 
K
Kelvin Saldana
22:50
Yeah. Yeah. And. Yeah, that's pretty much it. Is it? I just wanted to know, like, this is. 
K
Kelvin Saldana
22:57
We're. 
K
Kelvin Saldana
22:57
We're in a good pace. 
A
Anson Dai
22:59
Absolutely. Yeah. 
K
Kelvin Saldana
23:00
Okay. 
K
Kelvin Saldana
23:00
Okay. Perfect. 
K
Kelvin Saldana
23:01
Yeah. 
A
Anson Dai
23:03
Yeah. I really like the work so far. Thank you. 
K
Kelvin Saldana
23:08
Is there anything. Any other questions or. 
A
Anson Dai
23:12
I don't believe. Not that I can think of. 
K
Kelvin Saldana
23:15
No. Or. Well, I can't really. Well, did you take any pictures or. So I can. So you can show your boss or anything? 
A
Anson Dai
23:25
No worries. I can explain it. He. Yeah, yeah, he'll be fine. 
K
Kelvin Saldana
23:28
Yeah. Okay. Oh, and also, I'll probably text you either by tonight or most likely tonight to show you if there is a way to bypass that security that way, even for me. Like if I want to work on. 
K
Kelvin Saldana
23:42
The stage environment, I can. 
A
Anson Dai
23:43
Right? 
K
Kelvin Saldana
23:44
I can do that. 
A
Anson Dai
23:45
Right. And if I. If I can help in any way, I will try my best to ask them. Maybe they know. I really doubt it, but, you know. 
K
Kelvin Saldana
23:54
Okay. Perfect. 
A
Anson Dai
23:56
Yeah. 
K
Kelvin Saldana
23:56
All right, Anson, then that'll be all for today, right? 
A
Anson Dai
23:59
Yes. Yes. Thank you. 
K
Kelvin Saldana
24:01
Yeah, you have a good. Have a good night. 
A
Anson Dai
24:03
You too. Have a good night. Bye. 
K
Kelvin Saldana
24:05
Bye. 
