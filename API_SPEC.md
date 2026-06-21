# Trashfolio API Specification
All endpoints are prefixed with:

/api

Authentication uses **Sanctum tokens**.

---
# Authentication
POST /api/auth/request-otp

Request:

mobile

Response:

success message

---

POST /api/auth/verify-otp

Request:

mobile

code

Response:

user data

access token

---

GET /api/auth/me

Returns authenticated user.

---

POST /api/auth/logout

Revokes current token.

---

# Projects
GET /api/projects

List user projects.

---

POST /api/projects

Create project.

---
GET /api/projects/{id}

View project.

---
PUT /api/projects/{id}

Update project.

---

DELETE /api/projects/{id}

Delete project.

---

# Folders
POST /api/folders

Create folder.

---

PUT /api/folders/{id}

Update folder.

---

DELETE /api/folders/{id}

Delete folder.

---

# Snippets

GET /api/snippets

List snippets.

---

POST /api/snippets

Create snippet.

---

GET /api/snippets/{id}

View snippet.

--- 
PUT /api/snippets/{id}

Update snippet.

---
DELETE /api/snippets/{id}

Delete snippet.

---

# Tags
GET /api/tags

List tags.

---

POST /api/tags

Create tag.

---

PUT /api/tags/{id}

Update tag.

---

DELETE /api/tags/{id}

Delete tag.

# Search
GET /api/search

Search snippets using full‑text search.

Supported filters:

project_id

folder_id

tag_ids

language

Pagination is supported on all list endpoints.