**freeCodeCamp** - Information Security and Quality Assurance Project
------

**Anonymous Message Board**

### User Stories:

1. I can **POST** a thread to a specific message board by passing form data `text` and `delete_password` to _/api/threads/{board}_.\
	(Recommended: redirect to board page /b/{board})\
	Saved will be `id`, `text`, `created_on` (date & time), `bumped_on` (date & time, starts same as `created_on`), `reported` (boolean), `delete_password`, & `replies` (array).
2. I can **POST** a reply to a thread on a specific board by passing form data `text`, `delete_password`, & `thread_id` to _/api/replies/{board}_ and it will also update the `bumped_on` date to the comment's date.\
	(Recommended: redirect to thread page /b/{board}/{thread\_id})\
	In the thread's 'replies' array will be saved `id`, `text`, `created_on`, `delete_password`, & `reported`.
3. I can **GET** an array of the most recent 10 bumped threads on the board with only the most recent 3 replies from _/api/threads/{board}_. The `reported` and `delete_password` fields will not be sent. Also include `replycount` (total number of replies).
4. I can **GET** an entire thread with all its replies from _/api/replies/{board}?thread\_id={thread\_id}_. Also hiding the same fields (`reported` and `delete_password`).
5. I can delete a thread completely if I send a **DELETE** request to _/api/threads/{board}_ and pass along the `thread_id` & `delete_password`. (Text response will be 'Successfully deleted' or 'Could not delete')
6. I can delete a post (just changing the text to '\[deleted\]') if I send a **DELETE** request to _/api/replies/{board}_ and pass along the `thread_id`, `reply_id`, & `delete_password`. (Text response will be 'Successfully deleted' or 'Could not delete')
7. I can report a thread and change its reported value to true by sending a **PUT** request to _/api/threads/{board}_ and pass along the `thread_id`. (Text response will be 'Reported' or 'Could not report')
8. I can report a reply and change its reported value to true by sending a **PUT** request to _/api/replies/{board}_ and pass along the `thread_id` & `reply_id`. (Text response will be 'Reported' or 'Could not report')
9. All 11 tests are complete and passing.