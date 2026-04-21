# Trashfolio

Trashfolio is a personal developer snippet manager designed to organize, search, and store code snippets efficiently.

It focuses on fast retrieval, structured organization, and a clean developer-friendly interface.

---

# Project Goals

Trashfolio aims to help developers:

- store useful code snippets
- organize snippets using projects and folders
- tag snippets for quick classification
- search snippets quickly
- access everything through a fast SPA interface

---

# Core Features (MVP)

- OTP based authentication
- Personal projects
- Nested folders
- Code snippets
- Tagging system
- Full-text search
- Dark mode UI
- REST API backend
- TDD testing strategy

---

# Monorepo Structure
/backend
Laravel API
/client
Vue SPA

Backend and frontend are separated but maintained in a single repository.

---

# Backend Architecture

The backend is a Laravel REST API.

Responsibilities:

- authentication
- authorization
- API endpoints
- database operations
- search functionality

The backend does not render views.

---

# Authentication System

Authentication is based on mobile OTP.

Flow:

1. User requests OTP
2. OTP stored in otp_codes table
3. User submits OTP
4. OTP validated
5. User created or logged in
6. Sanctum token issued

There is no traditional password system.

---

# OTP Storage

OTP codes are stored in a dedicated table.

Fields:

- id
- mobile
- code
- expires_at
- used_at

Benefits:

- audit capability
- expiration control
- security isolation

---

# Domain Models

Main entities:

Users  
Projects  
Folders  
Snippets  
Tags

Relationships:

User → Projects  
Project → Folders  
Folder → Subfolders  
Project/Folder → Snippets  
Snippet → Tags

---

# Database Design

Important tables:

users  
projects  
folders  
snippets  
tags  
snippet_tag  
otp_codes

Each resource belongs to a user ensuring strict multi-tenancy.

---

# API Design

Authentication:

POST /api/auth/request-otp  
POST /api/auth/verify-otp  
GET /api/auth/me  
POST /api/auth/logout

Projects:

GET /api/projects  
POST /api/projects  
GET /api/projects/{id}  
PUT /api/projects/{id}  
DELETE /api/projects/{id}

Folders:

POST /api/folders  
PUT /api/folders/{id}  
DELETE /api/folders/{id}

Snippets:

GET /api/snippets  
POST /api/snippets  
GET /api/snippets/{id}  
PUT /api/snippets/{id}  
DELETE /api/snippets/{id}

Tags:

GET /api/tags  
POST /api/tags  
PUT /api/tags/{id}  
DELETE /api/tags/{id}

Search:

GET /api/search

---

# Frontend Architecture

Frontend is a Vue 3 SPA.

Tech stack:

Vue 3  
TypeScript  
Vite  
Pinia  
Vue Router  
PrimeVue  
Tailwind CSS  
Monaco Editor

---

# UI Design

Key UI components:

Sidebar navigation  
Project tree  
Snippet editor  
Tag selector  
Global search bar

Dark mode is the default theme.

---

# Testing Strategy

The backend follows Test Driven Development using Pest.

Workflow:

Red → Write failing test  
Green → Implement feature  
Refactor → Improve code

Tests cover:

- authentication
- CRUD operations
- permissions
- validation
- search
- performance

---

# Docker Setup

Development environment uses Docker.

Services:

PHP-FPM  
Nginx  
Node  
Database

Registry mirrors are used for faster dependency installation.

---

# Running the Project

Backend:

composer install  
php artisan migrate  
php artisan serve

Frontend:

pnpm install  
pnpm dev
---

# Performance Considerations

- eager loading to prevent N+1 queries
- indexed search fields
- pagination on all lists
- efficient tag relationships

---

# Test Scenarios

All testing scenarios are defined in:

TEST_SCENARIOS.md

---

# Future Roadmap

Possible future features:

- snippet content search
- import/export snippets
- public snippet sharing
- AI snippet suggestions
- keyboard command palette