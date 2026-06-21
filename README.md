# Trashfolio

Trashfolio is a personal developer snippet manager designed to organize, store, and retrieve code snippets efficiently.

It focuses on fast access, structured organization, and a clean developer‑friendly interface.

The project is built as a monorepo containing a Laravel backend API and a Vue SPA frontend.

---

## Core Features

- Mobile OTP authentication
- Personal projects
- Nested folders
- Code snippets
- Tagging system
- Full‑text search
- Dark mode interface
- REST API backend
- Test‑driven development workflow

---

## Tech Stack

Backend
- Laravel
- Laravel Sanctum
- MySQL
- Meilisearch

Frontend
- Vue 3
- TypeScript
- Vite
- Pinia
- TailwindCSS
- PrimeVue
- Monaco Editor

---

## Monorepo Structure

backend/

Laravel REST API

client/

Vue Single Page Application

The backend exposes an API and does not render server-side views.

---

## Authentication

Authentication uses mobile OTP.

Flow:

1. User requests OTP
2. OTP is generated and stored
3. User submits OTP
4. OTP is verified
5. User is created or logged in
6. Sanctum token is issued

The system does not use passwords.

---

## Development

### Backend:
```bash
composer install

php artisan migrate

php artisan serve
```

Frontend:
```bash
pnpm install

pnpm dev
```


---

## Testing

The backend follows a Test Driven Development (TDD) workflow using Pest.

All testing scenarios are documented in:

TEST_SCENARIOS.md

---

## Documentation

Additional project documentation:

ARCHITECTURE.md

DOMAIN_MODEL.md

API_SPEC.md

ROADMAP.md

CLAUDE_HANDOFF.md

## Project Status

Trashfolio is currently in MVP development stage.

The focus is building a stable personal snippet management system before adding sharing or collaboration features.