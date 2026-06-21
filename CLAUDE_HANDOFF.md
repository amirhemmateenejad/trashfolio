# Trashfolio — Claude Handoff
This document provides the essential context required for AI assistants (such as Claude) to work on the Trashfolio project.

---

# Project Summary
Trashfolio is a personal developer snippet manager.

The application allows developers to store, organize, tag, and search code snippets collected during everyday development work.

The focus of the product is fast retrieval and structured organization of code snippets.

---

# Project Status
The project is currently in MVP development stage.

Core architecture and domain models are defined.

The focus is on completing the backend API and test coverage.

---

# Tech Stack

Backend

Laravel

Sanctum

MySQL

Meilisearch

Frontend

Vue 3

TypeScript

Vite

Pinia

TailwindCSS

PrimeVue

Monaco Editor

---

# Architecture
The project is a monorepo.

backend/

Laravel REST API

client/

Vue SPA

The backend handles domain logic, persistence, and search.

The frontend handles user interaction.

---
# Authentication
Authentication uses mobile OTP.

Flow:
1. request OTP
2. store OTP
3. verify OTP
4. create or login user
5. issue Sanctum token
Passwords are not used.

---
# Domain Model
Core entities:

User

Project

Folder

Snippet

Tag

Relationships:

User → Projects

Project → Folders

Folder → Subfolders

Project/Folder → Snippets

Snippet → Tags

---

# Authorization
The system uses ownership-based authorization.

Users can only access resources that belong to them.

Ownership for snippets is resolved through project or folder chains.

Laravel Policies enforce access control.

---
# Search
Full‑text search is implemented using Meilisearch.

Only snippets are indexed.

Search results are filtered by user_id to prevent cross‑user access.

---

# Testing Strategy
The backend follows Test Driven Development.

Tests are written using Pest.

All scenarios are documented in:

TEST_SCENARIOS.md

---

# Constraints
- strict multi‑tenancy
- no password authentication
- mobile OTP only
- user data isolation required
- API-first architecture

---
# Implementation Priorities
1. authentication
2. projects
3. folders
4. snippets
5. tags
6. search
7. testing

---

# Future Features 
Future features are documented in:

ROADMAP.md

These features are not part of the MVP scope.