---
"wp-content-connect": minor
---

pr: 95
author: @s3rgiosan

Add REST API v2 endpoints `GET|POST|PUT|DELETE /content-connect/v2/post/{post_id}/related` to retrieve (with pagination and ordering, limited to posts the current user can read), replace, add, and remove related entities for a post, preserving sort order for sortable relationships. The read limit was added in #143.
