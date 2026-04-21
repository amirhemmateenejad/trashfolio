# Trashfolio

Trashfolio is a developer-focused snippet manager designed to organize, tag, and quickly retrieve code snippets.

It provides a clean interface, structured organization, and fast search for developers who collect useful code fragments during daily work.

---

## Features

- OTP based authentication
- Project-based snippet organization
- Nested folders
- Tagging system
- Full-text search
- Dark mode interface
- REST API backend
- Test-driven development

---

## Tech Stack

Backend
- Laravel
- Laravel Sanctum
- MySQL / PostgreSQL

Frontend
- Vue 3
- TypeScript
- Vite
- Pinia
- TailwindCSS
- PrimeVue
- Monaco Editor

---

## Architecture

Trashfolio is structured as a monorepo:
backend/ Laravel API

client/ Vue SPA


The backend exposes a REST API while the frontend is a Single Page Application.

---

## Authentication

Authentication is based on mobile OTP.

Flow:

1. Request OTP
2. Verify OTP
3. User account created automatically
4. Sanctum token issued

No passwords are used.

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

The backend follows a strict Test Driven Development workflow using **Pest**.

All feature and integration scenarios are documented in: TEST_SCENARIOS.md


---

## Roadmap

- advanced snippet search
- snippet sharing
- import/export
- command palette
- AI powered snippet suggestions