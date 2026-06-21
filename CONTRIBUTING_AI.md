# AI Contribution Guidelines

This document defines the rules AI assistants must follow when contributing to the Trashfolio project.

The goal is to maintain architectural consistency, code quality, and domain integrity.

AI tools such as Claude, ChatGPT, or other coding assistants must follow these guidelines strictly.

---
# Project Overview
Trashfolio is a personal developer snippet manager.

The system allows developers to store, organize, tag, and search code snippets.

The architecture follows a strict separation between:
- backend API
- frontend SPA
The backend is responsible for domain logic and persistence.

The frontend is responsible for UI and user interaction.

---
# Monorepo Structure
backend/

Laravel REST API.

client/

Vue Single Page Application.

AI must not move files between these directories.

---

# Core Architecture Rules

The backend follows a layered architecture.

Controllers should remain thin.

Controllers should only handle:
- request validation
- calling services
- returning responses
Business logic must not live inside controllers.

Domain logic should be placed in:
- Services
- Policies
- Models when appropriate

---
# Authorization Rules
Authorization is based on ownership.

Every resource ultimately belongs to a user.

Ownership chain:

Snippet → Folder → Project → User

or

Snippet → Project → User

AI must never introduce queries that expose resources belonging to other users.

All resource access must be validated through:

Laravel Policies.

---
# Authentication

Authentication uses mobile OTP.

Passwords are not used.

Flow:

request OTP

store OTP

verify OTP

issue Sanctum token

AI must not introduce password authentication unless explicitly requested.

---
# Database Rules
Important principles:

- Use Laravel migrations.
- Do not modify database structure directly.
- Maintain referential integrity.
- Respect existing relationships.

Entities:

Users

Projects

Folders

Snippets

Tags

Tags are unique per user.

---

Folders belong to projects.

Folders can be nested using parent_id.

Rules:
- parent folder must belong to the same project
- folder loops must not be allowed
- deleting a folder must respect nested structure

---
# Snippet Rules
Snippets store developer code.

A snippet can belong to:

- a project root
- a folder

Snippets support:

- tags
- search indexing
- soft deletes
Deleted snippets move to the trash system.

---
# Trash System
Soft delete is used for snippets.

Deleted snippets are stored temporarily before permanent removal.

Cleanup is handled by scheduled jobs.

AI must not permanently delete data without passing through the trash mechanism.

---
# Search
Search uses Meilisearch.

Only snippets are indexed.

Indexed fields:

title

content

tags

project_id

folder_id

user_id

All search queries must enforce user filtering.

Example rule:

Search results must always be scoped to the authenticated user.

# Pagination Rules
Pagination should be used for large datasets.

Required pagination:

- snippets
- search results
- trash

Pagination is not required for:
- projects
- folders
- tags

--- 
# API Design Rules
All endpoints must follow REST conventions.

Rules:
- use proper HTTP verbs
- return JSON responses
- validate requests
- return proper HTTP status codes

Common status codes:

200 OK

201 Created

401 Unauthorized

403 Forbidden

404 Not Found

422 Validation Error

---

# Coding Standards

Backend:

- follow Laravel conventions
- use dependency injection
- prefer service classes for domain logic
- avoid duplicated logic

Frontend:

- use Composition API
- keep components small
- separate UI and business logic

---
# Testing Rules
All backend features must be testable.

Tests use:

Pest PHP

Test scenarios are defined in:

TEST_SCENARIOS.md

AI must not introduce features that cannot be tested.

---
# What AI Should Avoid
AI must not:
- break ownership rules
- bypass policies
- introduce password authentication
- mix frontend and backend logic
- introduce unnecessary abstractions
- modify existing domain rules without explanation

---
# When Making Changes
When implementing a new feature, AI should:
1. follow existing architecture
2. reuse existing patterns
3. ensure authorization is enforced
4. ensure tests can cover the feature

---
# AI Contribution Goal
AI contributions should improve:

- maintainability
- readability
- architectural consistency
- testability
The goal is not only to add features but to preserve the long‑term structure of the system.

