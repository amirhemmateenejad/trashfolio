# Trashfolio Architecture
Trashfolio follows a monorepo architecture.

backend/

Laravel REST API

client/

Vue Single Page Application

The backend is responsible for domain logic and data persistence.

The frontend is responsible for user interface and user interaction.

---

## Backend Responsibilities
- authentication
- authorization
- API endpoints
- database access
- search integration
- validation
- business logic

The backend does not render views.

---

## Frontend Responsibilities
- UI rendering
- routing
- state management
- API communication
- snippet editing

---

## Authentication
Authentication is based on mobile OTP.

Process:
1. user requests OTP
2. OTP stored in database
3. user submits OTP
4. OTP validated
5. user created or authenticated
6. Sanctum token issued

Passwords are not used.

---

## Authorization Model
The system uses **ownership-based** authorization.

Each resource belongs to a user either directly or indirectly.

User

→ Projects

→ Folders

→ Snippets

→ Tags

Users can only access resources they own.

Authorization is implemented using Laravel Policies.

---

## Search System

Full‑text search is implemented using Meilisearch.

Searchable entity:

Snippets

Indexed fields:

- title
- content
- tags
- project_id
- folder_id
- user_id

Filtering rules ensure that users can only search their own snippets.

---

## Pagination

Pagination is applied to large collections:

- snippets
- search results
- trash
  
Projects, folders, and tags typically do not require pagination due to expected small counts.

---
## Background Tasks
Scheduled jobs handle cleanup tasks:
- remove expired OTP codes
- clean expired tokens
- clean trash items

These tasks are scheduled using Laravel scheduler.



