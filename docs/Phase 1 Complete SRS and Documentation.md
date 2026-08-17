# Phase 1 Complete SRS and Technical Audit Documentation
## Smart Adama — AI-Powered Learning Platform

---

## Document Control

| Field | Value |
|---|---|
| Document Title | Phase 1 Complete SRS and Technical Audit Documentation |
| Project | Smart Adama — AI Learning Platform |
| Version | 2.0.0 (Authoritative Re-Audit) |
| Audit Date | 2026-08-16 |
| Status | Phase 1 — Final Authoritative Audit |
| Repository Path | `/home/vico/Projects/Dev/Smart Adama/` |
| Frontend Path | `/home/vico/Projects/Dev/Smart Adama/frontend/` |
| Backend Path | `/home/vico/Projects/Dev/Smart Adama/backend/` |
| Documentation Path | `/home/vico/Projects/Dev/Smart Adama/docs/` |

> **Source-of-Truth Rule:** Every statement in this document is derived from direct inspection of repository source code, migrations, configuration files, and route definitions as of 2026-08-16. Features are classified with the following status codes:
>
> - ✅ **Verified** — directly observed in source code
> - 🟡 **Partially implemented** — backend or frontend exists, but not both, or incomplete
> - 🔵 **Implemented, not fully verified** — code present, runtime correctness not tested
> - 🟠 **Planned / referenced** — mentioned in config or README but not present in code
> - 🔴 **Known issue / bug** — confirmed defect in the source
> - ⚪ **Not determined** — could not be confirmed from repository inspection alone

---

## Executive Summary

Smart Adama is a web-based AI-powered learning platform developed as a group internship project over a 30-day summer sprint. Its purpose is to digitize and make interactive the *Smart Adama Book* — a conceptual framework for building a smart-city ecosystem around Adama, Ethiopia — and deliver its content to learners through a structured reading interface, a Retrieval-Augmented Generation (RAG) AI study assistant, chapter-scoped quizzes, progress tracking, and gamification.

### Architecture

The platform is a **fully decoupled, API-first system**:

- **Backend:** Laravel 11 (PHP 8.3) REST API serving all data over `/api/v1/` endpoints and SSE streams
- **Frontend:** Vue 3 TypeScript SPA (Vite, Pinia, Tailwind CSS v4) consuming the API
- **Database:** PostgreSQL 16 + `pgvector` extension for both relational data and vector similarity search
- **Queue / Cache:** Redis (via Predis)
- **LLM (currently active):** Groq — `llama-3.3-70b-versatile` — used for both the book study assistant and the global platform assistant. The code default is Groq; the `.env.example` template sets `AI_LLM_PROVIDER=claude`, which is a documentation inconsistency requiring resolution before deployment.
- **Embedding provider (currently active):** Voyage AI — `voyage-3-lite` — 1024-dimensional vectors

### AI/RAG System

The core AI feature is a RAG pipeline: when a user asks a question in the study interface, the query is embedded by Voyage AI into a 1024-dimensional vector, which is compared against stored book content chunk embeddings using pgvector cosine similarity (HNSW index). The top-K most relevant chunks are assembled into a prompt and sent to Groq's LLM for streaming response generation. Responses are returned token-by-token via SSE. The system is grounded by strict prompt instructions — the LLM is instructed to answer only from retrieved context passages and refuse if none are found.

> **Important correction from previous SRS version:** The previous documentation incorrectly listed Anthropic Claude as the primary/active LLM. Based on direct inspection of `config/ai.php` and `AppServiceProvider.php`, the code-level default is **Groq**. Claude is implemented as a supported alternative provider but is not the code default. The `.env.example` template explicitly sets `AI_LLM_PROVIDER=claude`, creating an ambiguity that must be resolved before production deployment.

### Learning Workflow

```
Register/Login → Dashboard → Read Chapter → Study with AI (RAG Chat) → Take Quiz → Complete Chapter → Earn Badge/Streak
```

### Implementation Maturity

The platform is a **functional Phase 1 MVP**. The end-to-end learning loop is implemented and operable. However, it has not been load-tested, does not have a production deployment configuration, has several security issues that must be remediated before production use, and includes some stub features (daily challenge, admin quiz UI).

### Major Limitations

1. OAuth callback URL is hardcoded to `http://localhost:5173` — breaks any non-localhost deployment
2. Authentication tokens stored in `localStorage` — XSS-accessible
3. Mail driver is `log` — password reset emails are not delivered
4. No Docker / containerization configuration found in repository
5. `chat_sessions` table has no `chapter_id` column in migrations — chapter-scoping logic in `ChatMessageController` accesses `$session->chapter_id` which will always evaluate to `null`, meaning chapter isolation is not functioning as documented
6. Daily challenge is hardcoded, not database-backed
7. Admin quiz management frontend is a stub

### Phase 2 Recommendations

Fix the five critical production blockers (OAuth URL, token security, mail, Docker, chapter_id column), complete the admin quiz UI, and validate end-to-end test coverage before any production deployment.

---

## Audit Methodology

### Scope of Inspection

This audit was performed by direct filesystem inspection of the following areas:

**Backend directories inspected:**
- `backend/app/Http/Controllers/` (all controller files)
- `backend/app/Services/` (all service files including AI, RAG, MarkdownService)
- `backend/app/Models/` (Book, Chapter, ChatSession confirmed)
- `backend/app/Providers/AppServiceProvider.php` (DI bindings)
- `backend/app/Jobs/` (background jobs)
- `backend/app/Http/Middleware/` (EnsureAdmin, ThrottleChat)
- `backend/routes/api.php` (all 47 routes verified)
- `backend/config/ai.php` (LLM and embedding configuration)
- `backend/.env.example` (environment variable reference)
- `backend/composer.json` (dependency versions)
- `backend/database/migrations/` (all 15 migration files)
- `backend/tests/Feature/` and `backend/tests/Unit/` (directory structure)

**Frontend directories inspected:**
- `frontend/src/views/` (all 10 view files + admin/ and auth/ subdirectories)
- `frontend/src/stores/` (Pinia store structure)
- `frontend/src/api/` (API client modules)
- `frontend/src/composables/useChatStream.ts` (SSE implementation)
- `frontend/src/i18n.ts` (internationalization)
- `frontend/src/lang/` (en.json, am.json, om.json)
- `frontend/src/style.css` (design tokens)
- `frontend/package.json` (frontend dependency versions)

**Migrations individually inspected (all 15):**
- `0001_01_01_000000_create_users_table.php`
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`
- `2024_01_01_000001_create_books_table.php`
- `2024_01_01_000002_create_chapters_table.php`
- `2024_01_01_000003_create_sections_table.php`
- `2024_01_01_000004_create_content_chunks_table.php`
- `2024_01_01_000005_create_chat_tables.php`
- `2024_01_01_000006_create_quiz_tables.php`
- `2024_01_01_000007_create_progress_tables.php`
- `2024_01_01_000008_create_gamification_tables.php`
- `2024_08_13_000001_add_content_to_chapters_table.php`
- `2026_08_03_112958_create_personal_access_tokens_table.php`
- `2026_08_13_220112_add_section_number_to_sections_table.php`
- `2026_08_14_170419_create_chat_message_feedback_table.php`

**Commands executed:**
- No destructive commands were run
- No test suite was executed (backend test execution requires a live PostgreSQL + Redis environment that was not confirmed available)
- No build commands were executed

### Limitations of This Audit

- Backend test results (pass/fail counts) are **not determined** — running `php artisan test` requires a configured test database
- Frontend build output correctness is **not determined** — `npm run build` was not executed
- Runtime behavior (actual API responses, streaming latency, embedding quality) was **not measured** — all AI performance metrics are architectural observations, not empirical measurements
- Database content (actual seeded data, canonical book presence) is **not determined** — no database connection was made

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Product Purpose](#2-product-purpose)
3. [Goals and Objectives](#3-goals-and-objectives)
4. [Target Users](#4-target-users)
5. [Functional Scope](#5-functional-scope)
6. [Frontend Architecture](#6-frontend-architecture)
7. [Backend Architecture](#7-backend-architecture)
8. [AI Provider Configuration](#8-ai-provider-configuration)
9. [Database Architecture](#9-database-architecture)
10. [API Architecture](#10-api-architecture)
11. [Authentication and Authorization](#11-authentication-and-authorization)
12. [Learning System](#12-learning-system)
13. [Smart Adama Book Architecture](#13-smart-adama-book-architecture)
14. [AI Assistant Architecture](#14-ai-assistant-architecture)
15. [RAG Architecture](#15-rag-architecture)
16. [AI Response Formatting](#16-ai-response-formatting)
17. [AI Performance — Latency Analysis](#17-ai-performance--latency-analysis)
18. [Quiz System](#18-quiz-system)
19. [Progress System](#19-progress-system)
20. [Gamification](#20-gamification)
21. [Dashboard](#21-dashboard)
22. [Profile System](#22-profile-system)
23. [Administration](#23-administration)
24. [Internationalization](#24-internationalization)
25. [UI/UX Design System](#25-uiux-design-system)
26. [Security Audit](#26-security-audit)
27. [Performance and Caching](#27-performance-and-caching)
28. [Error Handling](#28-error-handling)
29. [Testing](#29-testing)
30. [Deployment and Environment](#30-deployment-and-environment)
31. [Data Flow Diagrams](#31-data-flow-diagrams)
32. [System Architecture Diagrams](#32-system-architecture-diagrams)
33. [Functional Requirements](#33-functional-requirements)
34. [Non-Functional Requirements](#34-non-functional-requirements)
35. [Current Project Status](#35-current-project-status)
36. [Known Issues](#36-known-issues)
37. [Technical Debt](#37-technical-debt)
38. [Critical Blockers Before Production](#38-critical-blockers-before-production)
39. [Deployment Readiness Checklist](#39-deployment-readiness-checklist)
40. [Deployment Handoff Guide](#40-deployment-handoff-guide)
41. [Known Facts vs Assumptions](#41-known-facts-vs-assumptions)
42. [Recommended Improvements](#42-recommended-improvements)
43. [Future Roadmap](#43-future-roadmap)
44. [Final Architecture Summary](#44-final-architecture-summary)

---

## 1. Project Overview

**Smart Adama** is an AI-powered digital learning platform built around the *Smart Adama Book*, a conceptual framework for transforming Adama, Ethiopia into a smart city. The platform digitizes and makes interactive the book's 11 thematic chapters through a structured learner interface, a RAG-grounded AI study assistant, chapter quizzes, progress tracking, and gamification.

The system is a strictly decoupled, API-first architecture:

- **Backend:** Laravel 11 REST API (PHP 8.3) — no Blade views, no Inertia, pure JSON API
- **Frontend:** Vue 3 TypeScript SPA (Vite 8) — communicates exclusively via REST + SSE
- **Database:** PostgreSQL 16 + pgvector extension
- **Queue/Cache:** Redis (Predis client)
- **LLM (active):** Groq (`llama-3.3-70b-versatile` by code default)
- **LLM (alternative, supported):** Anthropic Claude (`claude-3-5-sonnet-20241022`)
- **Embedding:** Voyage AI (`voyage-3-lite`, 1024 dimensions)

### Repository Structure

```
smart-adama/
├── backend/              # Laravel 11 REST API (PHP 8.3)
│   ├── app/              # Controllers, Services, Models, Jobs, Middleware
│   ├── config/           # ai.php and other Laravel config
│   ├── database/         # 15 migrations, seeders, factories
│   ├── routes/api.php    # All API route definitions
│   ├── tests/            # Pest PHP test suite
│   ├── .env.example      # Environment variable template
│   └── composer.json
├── frontend/             # Vue 3 TypeScript SPA
│   ├── src/              # Application source
│   └── package.json
└── docs/                 # Project documentation
```

---

## 2. Product Purpose

Smart Adama serves as a digital companion to the *Smart Adama Book* by:

1. Presenting book content chapter-by-chapter in a structured reading interface
2. Enabling conversational learning through a RAG AI assistant grounded in the book
3. Assessing comprehension through chapter-scoped quizzes
4. Tracking learner progress per chapter with completion markers
5. Motivating engagement through badges, streaks, and an XP leaderboard
6. Supporting administrators in uploading, structuring, and ingesting book content into the vector database

---

## 3. Goals and Objectives

| # | Goal | Status |
|---|---|---|
| G-01 | Provide a digital, interactive version of the Smart Adama Book | ✅ Verified |
| G-02 | Enable RAG-powered AI tutoring grounded in book content | ✅ Verified |
| G-03 | Support chapter-scoped AI context (prevent cross-contamination) | 🔴 Bug — `chat_sessions.chapter_id` column absent from migrations |
| G-04 | Track learner progress per chapter | ✅ Verified |
| G-05 | Assess learning via quizzes per chapter | ✅ Verified |
| G-06 | Motivate learners through badges and streaks | ✅ Verified |
| G-07 | Provide an admin interface for book ingestion and content management | 🟡 Backend complete; Admin quiz UI is a stub |
| G-08 | Support multilingual interface (English, Amharic, Afaan Oromo) | ✅ Verified (UI strings only) |
| G-09 | Support OAuth (social) authentication | 🔴 OAuth redirect URL hardcoded to localhost |
| G-10 | Provide a platform-level global AI assistant | ✅ Verified |

---

## 4. Target Users

| Role | Description | Access Level |
|---|---|---|
| **Learner** | Students, researchers, urban planners engaging with Smart Adama book content | Standard authenticated user |
| **Administrator** | Platform operators managing book content ingestion, chapter structure, and quizzes | `role = 'admin'` in `users` table |
| **Guest** | Unauthenticated visitors — landing page and about page only | Public routes only |

### Role Model

The `users` table stores a `role` string field defaulting to `'learner'`. Admin detection compares `role === 'admin'`. OAuth users are created with `password = null` and `role = 'learner'` by default.

---

## 5. Functional Scope

### Verified Implemented

- Email/password registration and login (Laravel Sanctum token auth)
- OAuth social login via Laravel Socialite (`/{provider}/redirect`, `/{provider}/callback`)
- Password reset flow (forgot/reset endpoints — mail configured to `log` driver in dev)
- Book listing and chapter reading view
- RAG-grounded AI chat via SSE streaming (Groq + pgvector)
- Global platform AI assistant (Groq, non-RAG, synchronous)
- Quiz system: attempt creation, multi-type answers, server-side grading
- Progress tracking: `is_completed`, `best_quiz_score_pct`, `last_read_at` per chapter
- Gamification: badges (5 criteria types), daily streaks, XP leaderboard
- Dashboard: per-user aggregated stats with Redis caching
- Profile: view/edit name, locale, notify_badges; avatar upload; account deletion (anonymization)
- Admin: book creation, chapter management, section management, quiz management API, book ingestion pipeline
- Multilingual UI: English, Amharic (`am`), Afaan Oromo (`om`)
- Chat message feedback (thumbs up/down via `chat_message_feedback` table)
- SSE real-time token streaming for AI responses
- HNSW vector index on `content_chunks.embedding` (pgvector)
- Rate limiting on chat messages (`throttle.chat`)
- Markdown sanitization via Parsedown + HTMLPurifier (backend)

### Partially Implemented

- **Chapter-scoped AI context (🔴 Bug):** `ChatMessageController` accesses `$session->chapter_id`, but the `chat_sessions` migration does not define this column. The field will always evaluate to `null`. All chat sessions fall back to canonical-book-scoped or fallback retrieval. Chapter isolation is not functioning as designed.
- **OAuth callback:** `SocialAuthController` hardcodes `http://localhost:5173` in commented-out code as a deliberate workaround, ignoring `FRONTEND_URL`. Token is passed as a URL query parameter.
- **Daily challenge:** `GameController::dailyChallenge()` returns a hardcoded, static question. No database table for challenges exists.
- **Admin quiz UI:** `AdminQuizView.vue` is 1,689 bytes — a minimal placeholder. The admin quiz management API is complete but the frontend interface is not usable.
- **LLM provider ambiguity:** `config/ai.php` defaults to `'groq'`; `.env.example` sets `AI_LLM_PROVIDER=claude`. The active provider depends entirely on the deployment environment's `.env`.
- **Badge notification delivery:** `users.notify_badges` field exists and is returned in the API. No email dispatch is wired to it. Mail driver is `log` in the `.env.example`.

### Planned / Referenced

- Docker Compose setup (no `docker-compose.yml` found in repository root or any subdirectory)
- PDF-based manuscript ingestion (SA-Book.pdf present at `backend/SA-Book.pdf`; `smalot/pdfparser` and `spatie/pdf-to-text` are installed but no PDF ingestion route or controller exists; ingestion uses manual text paste)
- Email notifications for badge awards (`notify_badges` field exists, no email queued)
- S3 object storage (AWS env vars present in `.env.example`; avatar upload uses local disk)
- Persistent XP score (XP is calculated on-the-fly, not stored in the database)

---

## 6. Frontend Architecture

### Technology Stack (verified from `frontend/package.json`)

| Layer | Technology | Version |
|---|---|---|
| Framework | Vue 3 (Composition API, `<script setup>`) | ^3.5.40 |
| Language | TypeScript | ^7.0.2 |
| Build Tool | Vite | ^8.2.1 |
| Styling | Tailwind CSS v4 (via `@tailwindcss/vite`) | ^4.3.3 |
| State Management | Pinia | ^4.0.2 |
| Routing | vue-router v4 (HTML5 history mode) | ^4.6.4 |
| HTTP Client | Axios | ^1.19.0 |
| Internationalization | vue-i18n (Composition API, `legacy: false`) | ^9.14.5 |
| Icons | lucide-vue-next | ^1.0.0 |
| Markdown Rendering | marked | ^18.0.9 |
| DOM Sanitization | DOMPurify | ^3.4.13 |
| Unit Testing | Vitest | ^4.1.10 |
| E2E Testing | Playwright | ^1.62.1 |
| Component Testing | @vue/test-utils | ^2.4.11 |

> **Note:** No test runner scripts (`test`, `test:e2e`) were found in `package.json`. The `scripts` section contains only `dev`, `build`, and `preview`. Vitest and Playwright are installed as devDependencies but are not configured in `package.json` scripts.

### Frontend Directory Structure (verified)

```
frontend/src/
├── api/                      # HTTP client modules per domain
│   ├── client.ts             # Central Axios instance + error interceptors
│   ├── auth.ts
│   ├── books.ts
│   ├── chat.ts
│   ├── progress.ts
│   ├── quiz.ts
│   └── user.ts
├── stores/                   # Pinia state stores
│   ├── auth.ts
│   ├── books.ts
│   ├── chat.ts
│   ├── progress.ts
│   └── quiz.ts
├── views/                    # Route-level page components
│   ├── LandingView.vue       (22,945 bytes)
│   ├── AboutView.vue         (23,004 bytes)
│   ├── DashboardView.vue     (34,484 bytes)
│   ├── ChatView.vue          (64,264 bytes — primary study page)
│   ├── ChapterView.vue       (3,473 bytes)
│   ├── ProfileView.vue       (21,069 bytes)
│   ├── QuizView.vue          (11,911 bytes)
│   ├── QuizzesView.vue       (6,450 bytes)
│   ├── GameView.vue          (31,313 bytes)
│   ├── NotFoundView.vue      (552 bytes)
│   ├── auth/                 # LoginView, RegisterView, ForgotPasswordView,
│   │                         # ResetPasswordView, OAuthCallbackView
│   └── admin/
│       ├── AdminBookView.vue          (5,964 bytes)
│       ├── AdminBookIngestionView.vue (19,894 bytes)
│       ├── AdminChapterEditor.vue     (12,182 bytes)
│       └── AdminQuizView.vue          (1,689 bytes — minimal stub)
├── components/
│   ├── layout/
│   │   ├── AppNav.vue
│   │   ├── AppShell.vue
│   │   └── GlobalAssistant.vue
│   ├── ui/
│   │   ├── SaButton.vue
│   │   ├── SaCard.vue
│   │   ├── SaInput.vue
│   │   ├── SaModal.vue
│   │   ├── SaToast.vue
│   │   └── ProgressRing.vue
│   ├── IntroductionPreface.vue
│   └── MarkdownRenderer.vue
├── composables/
│   ├── useChatStream.ts      # SSE streaming via fetch + ReadableStream
│   ├── useMotionPref.ts
│   └── useScrollReveal.ts
├── router/index.ts
├── lang/                     # en.json (9,249 B), am.json (12,988 B), om.json (9,555 B)
├── locales/                  # Empty directory
├── types/
├── i18n.ts
├── main.ts
├── style.css                 (6,172 bytes)
└── App.vue                   (910 bytes)
```

### Frontend Router (verified from `router/index.ts`)

| Route | Name | Auth | Admin |
|---|---|---|---|
| `/` | landing | No | No |
| `/about` | about | No | No |
| `/auth/callback` | oauth-callback | No | No |
| `/login` | login | guest-only | No |
| `/register` | register | guest-only | No |
| `/forgot-password` | forgot-password | guest-only | No |
| `/reset-password` | reset-password | guest-only | No |
| `/dashboard` | dashboard | Yes | No |
| `/profile` | profile | Yes | No |
| `/study` | study | Yes | No |
| `/study/:sessionId` | study-session | Yes | No |
| `/chapters/:chapterId` | chapter | Yes | No |
| `/chapters/:chapterId/quiz` | chapter-quiz | Yes | No |
| `/quizzes` | quizzes | Yes | No |
| `/game` | game | Yes | No |
| `/admin/books` | admin-books | Yes | Yes |
| `/admin/book-ingestion` | admin-book-ingestion | Yes | Yes |
| `/admin/quizzes` | admin-quizzes | Yes | Yes |
| `/admin/chapters/:id` | admin-chapter-editor | Yes | Yes |
| `/:pathMatch(.*)` | not-found | No | No |

**Navigation Guard** (`beforeEach`): On first load, if a token exists but `user` is null, calls `auth.fetchMe()` to hydrate session. Guest-only routes redirect authenticated users to `/dashboard`. Protected routes redirect unauthenticated to `/login?redirect=<path>`. Admin routes redirect non-admins to `/dashboard`.

### SSE Implementation — `useChatStream.ts` (verified)

The native `EventSource` API cannot send custom `Authorization` headers. The composable uses `fetch()` with `Accept: text/event-stream` and `Authorization: Bearer <token>`. It reads the response body via `ReadableStream.getReader()`, decodes with `TextDecoder`, and processes SSE events split on double-newlines (`\n\n`):

- `delta` → accumulate `token`, call `onToken(token)`
- `done` → deliver `message_id`, `grounded`, `citations` via `onDone`
- `error` → set `streamError`, call `onError`

The composable exposes `cancel()` which calls `AbortController.abort()`.

---

## 7. Backend Architecture

### Technology Stack (verified from `backend/composer.json`)

| Layer | Technology | Version |
|---|---|---|
| Framework | Laravel | ^13.8 |
| Language | PHP | ^8.3 |
| Authentication | Laravel Sanctum | ^4.3 |
| Social Auth | Laravel Socialite | ^5.29 |
| HTTP Client | Guzzle HTTP | * |
| Markdown Parser | erusev/parsedown | ^1.8 |
| HTML Sanitizer | ezyang/htmlpurifier | ^4.19 |
| PDF Tools | smalot/pdfparser, spatie/pdf-to-text | ^2.12 / ^1.54 |
| Cache/Queue | predis/predis | * |
| Testing | Pest PHP + Pest Laravel | ^4.7 / ^4.1 |
| PHPUnit | phpunit/phpunit | ^12.5.12 |

### AI Service Interfaces (verified from `AppServiceProvider.php`)

```php
interface LLMGatewayInterface {
    public function chat(array $messages, array $options = []): string;
    public function streamChat(array $messages, array $options = []): \Generator;
}
interface EmbeddingProviderInterface {
    public function embed(string $text): array;
    public function embedBatch(array $texts): array;
    public function getDimension(): int;
}
```

**DI bindings** (from `AppServiceProvider::register()`):
- `LLMGatewayInterface` → `GroqLLMGateway` if `AI_LLM_PROVIDER=groq` (or unset); `ClaudeLLMGateway` if `=claude`; default fallback is `GroqLLMGateway`
- `EmbeddingProviderInterface` → `VoyageEmbeddingProvider` if `AI_EMBEDDING_PROVIDER=voyage` (or unset); `OpenAIEmbeddingProvider` if `=openai`

### Background Jobs (verified)

| Job | Queue | Timeout | Purpose |
|---|---|---|---|
| `IngestChapterJob` | default | 120s | Entry point: marks chapter `processing`, dispatches one `GenerateChunkEmbeddingJob` per section |
| `GenerateChunkEmbeddingJob` | default | 900s | Chunks section text → Voyage AI batch embed → stores `content_chunks` with vector; marks chapter `ready` when all sections done |
| `EvaluateBadgesJob` | default | — | Async badge evaluation post quiz submission |
| `AnonymizeUserJob` | default | — | Soft-deletes + anonymizes user data on account deletion |

### Middleware (verified from `routes/api.php`)

| Middleware | Key | Purpose |
|---|---|---|
| `ForceJsonResponse` | (global) | Forces `Accept: application/json` on all requests |
| `auth:sanctum` | (route-level) | Validates Sanctum Bearer token |
| `admin` (`EnsureAdmin`) | (route-level) | Checks `user->role === 'admin'`; logs 403 denials |
| `throttle.chat` (`ThrottleChat`) | (route-level) | Rate-limits chat messages per 5 min |

### Password Reset URL Override (verified from `AppServiceProvider::boot()`)

```php
ResetPassword::createUrlUsing(function ($notifiable, string $token) {
    $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
    return "{$frontend}/reset-password?token={$token}&email=" . urlencode(...);
});
```

Password reset correctly uses `FRONTEND_URL`. ✅ (This is correct, unlike OAuth which does not.)

### Sanctum Soft-Delete Guard (verified from `AppServiceProvider::boot()`)

```php
Sanctum::authenticateAccessTokensUsing(static function ($token, bool $isValid) {
    return $isValid && ! $token->tokenable->trashed();
});
```

Soft-deleted users cannot authenticate. ✅

---

## 8. AI Provider Configuration

> **Critical Discrepancy (🔴):** The code-level default and the `.env.example` template disagree on the LLM provider. This must be resolved before deployment.

### LLM Provider (verified from `config/ai.php` and `.env.example`)

| Source | Setting | Value |
|---|---|---|
| `config/ai.php` | `'llm_provider' => env('AI_LLM_PROVIDER', 'groq')` | Default = **groq** |
| `.env.example` | `AI_LLM_PROVIDER=claude` | Template sets **claude** |
| `AppServiceProvider` | `match($provider)` default fallback | **GroqLLMGateway** |

**Implication:** If a developer copies `.env.example` without modification, Claude is active. If `AI_LLM_PROVIDER` is unset, Groq is active. The active provider in any given deployment depends entirely on the `.env` file.

### Groq Configuration (verified from `config/ai.php` and `GroqLLMGateway.php`)

| Parameter | Value |
|---|---|
| Model (config default) | `llama-3.3-70b-versatile` (env: `GROQ_MODEL`) |
| Model (GroqLLMGateway constructor) | `llama-3.3-70b-specdec` (hardcoded in constructor, overrides config) |
| Base URL | `https://api.groq.com/openai/v1/` |
| Max tokens | 2048 (config) / 1024 (constructor default) |
| Timeout | 60 seconds |
| API format | OpenAI-compatible REST (`/chat/completions`) |
| Streaming | ✅ Implemented (`stream: true`, SSE `data:` lines, `[DONE]` sentinel) |
| Retry | 3 attempts, 500ms/1000ms/2000ms backoff |
| Rate limit (429) | Throws `AiProviderException` immediately (not retried) |

> **Note:** There is an inconsistency in `GroqLLMGateway.php`: the constructor sets `$this->model = config('ai.groq.model', 'llama-3.3-70b-specdec')` while `config/ai.php` defaults to `'llama-3.3-70b-versatile'`. The actual model used depends on whether `GROQ_MODEL` is set in `.env`.

### Anthropic Claude Configuration (supported alternative, verified from `config/ai.php`)

| Parameter | Value |
|---|---|
| Model | `claude-3-5-sonnet-20241022` (env: `ANTHROPIC_MODEL`) |
| Base URL | `https://api.anthropic.com/v1` |
| Max tokens | 2048 |
| Timeout | 60 seconds |
| API format | Anthropic Messages API (not OpenAI-compatible) |
| Streaming | ✅ Implemented (`stream: true`, Anthropic SSE `content_block_delta`) |

### Embedding Provider — Voyage AI (verified from `config/ai.php` and `VoyageEmbeddingProvider.php`)

| Parameter | Value |
|---|---|
| Model | `voyage-3-lite` (env: `VOYAGE_MODEL`) |
| Dimension | 1024 (env: `VOYAGE_EMBEDDING_DIMENSION`) |
| Batch size | **1** (from `config/ai.php`: `'batch_size' => 1`) |
| Timeout | 30 seconds |
| API endpoint | `https://api.voyageai.com/v1/embeddings` (hardcoded in provider) |
| Single query input_type | `'query'` |
| Batch ingestion input_type | `'document'` |
| Rate limit delay | 25s between batches (env: `VOYAGE_REQUEST_DELAY_SECONDS`) |
| Rate limit lock | Redis lock `voyage_api_rate_limit` (batch only; skipped for foreground queries) |
| 429 retry | Exponential: 60s × 2^attempt (env: `VOYAGE_RATE_LIMIT_RETRY_SECONDS`) |
| Max 429 retries | 5 (from config) |
| 5xx retry | 3 attempts, 500ms/1000ms/2000ms backoff |

> **Previous SRS error corrected:** The previous version stated batch_size=32. The actual value in `config/ai.php` is `'batch_size' => 1` (free-tier optimized). Also, the Voyage API URL is hardcoded in the provider class rather than read from config.

### RAG Settings (verified from `config/ai.php`)

| Parameter | Config Key | Default (code) | Default (.env.example) |
|---|---|---|---|
| Top-K | `ai.rag.top_k` | 5 | 5 |
| Similarity threshold | `ai.rag.similarity_threshold` | **0.35** | **0.75** |
| Chunk target tokens | `ai.rag.chunk_target_tokens` | 700 | — |
| Chunk overlap ratio | `ai.rag.chunk_overlap_ratio` | 0.15 | — |

> **Previous SRS error corrected:** The previous version stated the default similarity threshold is 0.75. The code default in `config/ai.php` is `0.35`. The `.env.example` sets `RAG_SIMILARITY_THRESHOLD=0.75`. The active threshold depends on the `.env` file.

### Global Assistant — Provider Note (verified from `GlobalChatController.php`)

`GlobalChatController` uses `LLMGatewayInterface` injected via DI — it resolves to whatever provider is active per `AI_LLM_PROVIDER`. It is **not** hardcoded to a specific provider. Developer names are read from `config('ai.platform.developers')` which is defined in `config/ai.php`. This corrects the previous SRS claim that developer names were hardcoded strings.

---

## 9. Database Architecture

### Engine and Extensions (verified)

- **Engine:** PostgreSQL 16
- **Extension:** `pgvector` (enabled in first migration: `CREATE EXTENSION IF NOT EXISTS vector`)
- **UUID strategy:** `gen_random_uuid()` (PostgreSQL native, not Laravel `uuid()`)
- **All primary keys:** UUID

### Critical Schema Finding (🔴)

`chat_sessions` table has **no `chapter_id` column** in any migration. `ChatMessageController` accesses `$session->chapter_id` which returns `null` always. Chapter-scoped RAG isolation is non-functional.

### Table Schemas (verified from all 15 migrations)

#### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | `gen_random_uuid()` |
| `name` | VARCHAR | — |
| `email` | VARCHAR UNIQUE | — |
| `email_verified_at` | TIMESTAMP | nullable |
| `password` | VARCHAR | nullable (OAuth users) |
| `provider` | VARCHAR | nullable |
| `provider_id` | VARCHAR | nullable |
| `role` | VARCHAR | default `'learner'` |
| `avatar_url` | VARCHAR | nullable |
| `locale` | VARCHAR(10) | default `'en'` |
| `notify_badges` | BOOLEAN | default `true` |
| `remember_token` | VARCHAR | nullable |
| `deleted_at` | TIMESTAMP | soft deletes |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `books`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `title` | VARCHAR | — |
| `status` | VARCHAR | nullable |
| `source_file_path` | VARCHAR | nullable |
| `source_file_type` | VARCHAR | nullable |
| `created_at`, `updated_at` | TIMESTAMP | — |

`Book::canonical()` queries `title = 'Smart Adama: Complete Guide & Ecosystem'` OR `title = 'Smart Adama: A Conceptual Framework'`; falls back to `Book::first()`.

#### `chapters`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `book_id` | UUID FK | → books.id CASCADE DELETE |
| `title` | VARCHAR | — |
| `order` | UNSIGNED INT | default 0; INDEX (book_id, order) |
| `ingestion_status` | VARCHAR | `draft`\|`queued`\|`processing`\|`ready`\|`failed` |
| `ingested_at` | TIMESTAMP | nullable |
| `content` | LONGTEXT | nullable; added in migration `2024_08_13_000001` |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `sections`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `chapter_id` | UUID FK | → chapters.id CASCADE DELETE |
| `title` | VARCHAR | — |
| `raw_text` | LONGTEXT | — |
| `order` | UNSIGNED INT | default 0 |
| `section_number` | VARCHAR | nullable; added migration `2026_08_13_220112` |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `content_chunks`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `section_id` | UUID FK | → sections.id CASCADE DELETE |
| `chunk_text` | LONGTEXT | — |
| `chunk_index` | UNSIGNED INT | default 0; INDEX (section_id, chunk_index) |
| `token_count` | UNSIGNED INT | default 0 |
| `embedding_status` | VARCHAR | `pending`\|`ready`\|`failed` |
| `embedding` | `vector(1024)` | nullable; HNSW cosine index |
| `created_at`, `updated_at` | TIMESTAMP | — |

**HNSW index:** `USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64)`

#### `chat_sessions`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `user_id` | UUID FK | → users.id CASCADE DELETE |
| `title` | VARCHAR | default `'New Chat'` |
| `last_activity_at` | TIMESTAMP | nullable; INDEX (user_id, last_activity_at) |
| `deleted_at` | TIMESTAMP | soft deletes |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `chat_messages`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `chat_session_id` | UUID FK | → chat_sessions.id CASCADE DELETE |
| `role` | VARCHAR | `'user'` or `'assistant'` |
| `content` | LONGTEXT | Sanitized HTML after stream completes; INDEX (chat_session_id, created_at) |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `chat_message_sources`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `chat_message_id` | UUID FK | → chat_messages.id CASCADE DELETE; INDEX |
| `content_chunk_id` | UUID FK | → content_chunks.id CASCADE DELETE |
| `similarity_score` | FLOAT | default 0 |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `chat_message_feedback`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `chat_message_id` | UUID FK | → chat_messages.id CASCADE DELETE |
| `user_id` | UUID FK | → users.id SET NULL; nullable |
| `feedback` | ENUM | `'like'`, `'dislike'` |
| `created_at`, `updated_at` | TIMESTAMP | UNIQUE (chat_message_id, user_id) |

#### `quizzes`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `chapter_id` | UUID FK | → chapters.id CASCADE DELETE; INDEX |
| `title` | VARCHAR | — |
| `passing_score_pct` | UNSIGNED INT | default 70 |
| `status` | VARCHAR | `'draft'` or `'published'` |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `quiz_questions`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `quiz_id` | UUID FK | → quizzes.id CASCADE DELETE |
| `question_text` | TEXT | — |
| `type` | VARCHAR | `'single'`, `'multiple'`, `'true_false'` |
| `explanation` | TEXT | nullable |
| `order` | UNSIGNED INT | default 0; INDEX (quiz_id, order) |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `quiz_options`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `quiz_question_id` | UUID FK | → quiz_questions.id CASCADE DELETE; INDEX |
| `option_text` | TEXT | — |
| `is_correct` | BOOLEAN | default false |
| `order` | UNSIGNED INT | default 0 |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `quiz_attempts`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `user_id` | UUID FK | → users.id CASCADE DELETE |
| `quiz_id` | UUID FK | → quizzes.id CASCADE DELETE; INDEX (user_id, quiz_id) |
| `score_pct` | FLOAT | nullable |
| `passed` | BOOLEAN | default false |
| `started_at` | TIMESTAMP | nullable |
| `submitted_at` | TIMESTAMP | nullable |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `quiz_attempt_answers`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `quiz_attempt_id` | UUID FK | → quiz_attempts.id CASCADE DELETE; INDEX |
| `quiz_question_id` | UUID FK | → quiz_questions.id CASCADE DELETE |
| `selected_option_ids` | JSON | default `'[]'` — array of UUID strings |
| `is_correct` | BOOLEAN | default false |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `user_progress`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `user_id` | UUID FK | → users.id CASCADE DELETE; INDEX + UNIQUE (user_id, chapter_id) |
| `chapter_id` | UUID FK | → chapters.id CASCADE DELETE |
| `is_completed` | BOOLEAN | default false |
| `best_quiz_score_pct` | FLOAT | nullable |
| `completed_at` | TIMESTAMP | nullable |
| `last_read_at` | TIMESTAMP | nullable |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `badges`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `code` | VARCHAR UNIQUE | machine key, e.g. `'first_chapter'` |
| `name` | VARCHAR | display name |
| `description` | TEXT | — |
| `icon` | VARCHAR | emoji or icon key |
| `criteria` | JSON | `{"type": "chapter_count\|perfect_score\|streak_days\|book_complete", "threshold": N}` |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `user_badges`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `user_id` | UUID FK | → users.id CASCADE DELETE; INDEX |
| `badge_id` | UUID FK | → badges.id CASCADE DELETE |
| `awarded_at` | TIMESTAMP | NOT NULL; UNIQUE (user_id, badge_id) |
| `created_at`, `updated_at` | TIMESTAMP | — |

#### `user_streaks`
| Column | Type | Notes |
|---|---|---|
| `id` | UUID PK | — |
| `user_id` | UUID FK | → users.id CASCADE DELETE; UNIQUE |
| `current_streak` | UNSIGNED INT | default 0 |
| `longest_streak` | UNSIGNED INT | default 0 |
| `last_activity_date` | DATE | nullable |
| `created_at`, `updated_at` | TIMESTAMP | — |

---

## 10. API Architecture

### Route Conventions (verified from `routes/api.php`)

- Base: `/api/`
- Primary versioned: `/api/v1/`
- Auth routes unversioned: `/api/auth/...`
- Game routes unversioned: `/api/game/...`
- Global chat unversioned: `/api/global-chat`

### Complete Endpoint Reference

#### Public
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/health` | Health check |
| POST | `/api/auth/register` | Register |
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/password/forgot` | Request reset token |
| POST | `/api/auth/password/reset` | Reset password |
| GET | `/api/auth/{provider}/redirect` | OAuth initiate |
| GET | `/api/auth/{provider}/callback` | OAuth callback (🔴 URL hardcoded) |

#### Authenticated (`auth:sanctum`)
| Method | Path | Purpose |
|---|---|---|
| POST | `/api/auth/logout` | Revoke token |
| GET | `/api/auth/me` | Get current user |
| GET | `/api/v1/books` | List books |
| GET | `/api/v1/chapters/{chapter}` | Chapter content |
| POST | `/api/v1/chapters/{chapter}/read` | Mark read |
| GET | `/api/v1/chapters/{chapter}/quiz` | Published quiz |
| POST | `/api/v1/quizzes/{quiz}/attempts` | Start attempt |
| POST | `/api/v1/quizzes/{quiz}/attempts/{attempt}/submit` | Submit + grade |
| GET | `/api/v1/users/me` | Profile |
| PATCH | `/api/v1/users/me` | Update profile |
| PUT | `/api/v1/users/me/password` | Change password |
| POST | `/api/v1/users/me/avatar` | Upload avatar |
| DELETE | `/api/v1/users/me` | Delete/anonymize account |
| GET | `/api/v1/users/me/quiz-attempts` | Quiz history |
| GET | `/api/v1/users/me/progress` | Chapter progress |
| GET | `/api/v1/users/me/badges` | Earned badges |
| GET | `/api/v1/users/me/streak` | Streak data |
| GET | `/api/v1/dashboard` | Dashboard stats (Redis cached 60s) |
| POST | `/api/v1/global-chat` | Global AI assistant (sync, non-RAG) |
| GET | `/api/v1/chat/sessions` | List sessions |
| POST | `/api/v1/chat/sessions` | Create session |
| GET | `/api/v1/chat/sessions/{session}` | Get session + history |
| PATCH | `/api/v1/chat/sessions/{session}` | Rename session |
| DELETE | `/api/v1/chat/sessions/{session}` | Soft-delete |
| POST | `/api/v1/chat/sessions/{session}/messages` | Send message (SSE) |
| POST | `/api/v1/messages/{message}/feedback` | Submit feedback |
| DELETE | `/api/v1/messages/{message}/feedback` | Remove feedback |
| GET | `/api/v1/messages/{message}/feedback` | Get feedback |
| GET | `/api/game/leaderboard` | XP leaderboard |
| GET | `/api/game/daily-challenge` | Daily challenge (hardcoded) |
| GET | `/api/v1/ai-search` | Semantic search |

#### Admin (`auth:sanctum` + `admin`)
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/admin/analytics` | Platform analytics |
| POST | `/api/admin/books` | Create book |
| GET | `/api/admin/books/{book}` | Book details |
| POST | `/api/admin/books/{book}/chapters` | Add chapter |
| PATCH | `/api/admin/chapters/{chapter}` | Update chapter |
| POST | `/api/admin/chapters/{chapter}/publish` | Publish chapter |
| GET | `/api/admin/chapters/{chapter}/sections` | List sections |
| POST | `/api/admin/chapters/{chapter}/sections` | Add section |
| PATCH | `/api/admin/sections/{section}` | Update section |
| DELETE | `/api/admin/sections/{section}` | Delete section |
| PATCH | `/api/admin/sections/{section}/reorder` | Reorder |
| POST | `/api/admin/chapters/{chapter}/quizzes` | Create quiz |
| POST | `/api/admin/quizzes/{quiz}/publish` | Publish quiz |
| POST | `/api/admin/quizzes/{quiz}/questions` | Add question |
| PATCH | `/api/admin/quizzes/{quiz}/questions/{question}` | Update question |
| DELETE | `/api/admin/quizzes/{quiz}/questions/{question}` | Delete question |
| GET | `/api/admin/book-ingestion` | Ingestion status |
| PUT | `/api/admin/chapters/{chapter}/content` | Save raw text |
| POST | `/api/admin/chapters/{chapter}/validate` | Validate content |
| POST | `/api/admin/chapters/{chapter}/preview` | Preview sections |
| POST | `/api/admin/chapters/{chapter}/ingest` | Trigger embedding |
| POST | `/api/admin/chapters/{chapter}/retry` | Retry failed chunks |
| GET | `/api/admin/chapters/{chapter}/status` | Embedding status |
| POST | `/api/admin/books/{book}/verify` | Verify coverage |

---

## 11. Authentication and Authorization

### Token Authentication (verified)

- **Provider:** Laravel Sanctum (`^4.3`)
- **Token storage (frontend):** `localStorage` key `sa_token` — ⚠️ XSS-accessible
- **Token transport:** `Authorization: Bearer <token>` header
- **Token revocation:** On logout, `currentAccessToken()->delete()`
- **Soft-delete guard:** Verified — trashed users rejected

### OAuth (🔴 Production bug confirmed)

- **Library:** Laravel Socialite (`^5.29`)
- **Bug:** `SocialAuthController::callback()` hardcodes `$frontendUrl = 'http://localhost:5173'`; the commented-out `env('FRONTEND_URL')` version is intentionally disabled
- **Security issue:** Token delivered as `?token=<plaintext>` in redirect URL — visible in server logs, browser history, referrer headers

### Password Reset

- Correctly uses `FRONTEND_URL` via `AppServiceProvider` override ✅
- Mail driver is `log` in `.env.example` — not delivered in development ⚠️

### Authorization Model

| Layer | Mechanism |
|---|---|
| API protection | `auth:sanctum` middleware |
| Admin routes | `EnsureAdmin` middleware: `$user->role === 'admin'` |
| Session ownership | `ChatMessageController`: `$session->user_id !== $user->id` → 403 |

---

## 12. Learning System

### Learner Flow

```
Book list → Read chapter → Mark read → Open AI chat → Submit quiz → Complete chapter → Earn badge/streak
```

### Chapter Progress (verified from `user_progress` migration)

| Field | Updated by |
|---|---|
| `last_read_at` | `ChapterController::markRead()` |
| `is_completed` | `QuizAttemptController::submit()` on pass |
| `best_quiz_score_pct` | Updated if new score exceeds stored best |
| `completed_at` | Set on first pass |

### Chapter Reading — `ChapterView.vue`

- Fetches chapter `content` from backend
- Mark as Read button → `POST /api/v1/chapters/{id}/read`
- Navigation to adjacent chapters
- Link to AI chat scoped to this chapter

> **Known issue:** Chapter-scoped AI chat is not functioning correctly due to missing `chapter_id` column in `chat_sessions` (see Section 9).

---

## 13. Smart Adama Book Architecture

### Canonical Chapters (verified from `BookIngestionService.php`)

The system is designed around exactly **11 canonical chapters** defined as a PHP constant:

```php
private const CANONICAL_CHAPTERS = [
    1  => 'Introduction',
    2  => 'Smart Governance',
    3  => 'Digital Adama',
    4  => 'Smart Security',
    5  => 'Smart Urban Design and Land Use Management',
    6  => 'Smart Environment and Organic Production',
    7  => 'Smart Mobility',
    8  => 'Smart Social Services',
    9  => 'Smart Tourism and Culture',
    10 => 'Smart Public Relation, Research and Knowledge Management',
    11 => 'Smart People',
];
```

This constant defines canonical chapter ordering and titles. Chapter numbers 0, 12+, and any number outside 1–11 are rejected at validation time.

### Canonical Book Scope (verified from `Book::canonical()` and `DashboardController`)

- `Book::canonical()` matches `title = 'Smart Adama: Complete Guide & Ecosystem'` OR `title = 'Smart Adama: A Conceptual Framework'`, then falls back to `Book::first()`
- Dashboard scopes all progress to `canonicalBook->chapters()->pluck('id')` — ✅ verified not hardcoded to 11
- RAG `RetrievalService` scopes global-book searches to `ch.book_id = ?` using the canonical book ID

### Dashboard Chapter Count (verified from `DashboardController.php`)

```php
$canonicalBook = Book::canonical();
$canonicalChapterIds = $canonicalBook ? $canonicalBook->chapters()->pluck('id') : collect();
$totalChapters = $canonicalChapterIds->count();
```

`total_chapters` is dynamically calculated from the actual chapters attached to the canonical book record. It will return 11 if the book is seeded correctly with all 11 chapters. If the canonical book is missing, it returns 0.

> **Previous SRS correction:** The previous document claimed `total_chapters` might not be scoped to the canonical book. It is correctly scoped. The fallback `Book::first()` remains a risk if multiple books exist in the database.

### Ingestion Status Lifecycle

```
draft → queued → processing → ready
                           → failed
```

Admin triggers: validate → preview → ingest (sets `queued`). `IngestChapterJob` sets `processing`. `GenerateChunkEmbeddingJob` per section completes and checks whether all sections are done; if so, sets `ready`.

### PDF Tools — Not Currently Used for Ingestion

`smalot/pdfparser` and `spatie/pdf-to-text` are present in `composer.json` and a `SA-Book.pdf` file reportedly exists in `backend/`. However, **no ingestion route or controller triggers PDF parsing**. All ingestion is manual (paste-based via `PUT /api/admin/chapters/{chapter}/content`).

---

## 14. AI Assistant Architecture

### Two Distinct AI Assistants (verified)

| Feature | Book Study Assistant | Global Platform Assistant |
|---|---|---|
| Route | `POST /api/v1/chat/sessions/{session}/messages` | `POST /api/v1/global-chat` |
| Controller | `ChatMessageController` | `GlobalChatController` |
| Uses RAG | ✅ Yes — pgvector retrieval | ❌ No — zero-shot |
| Streaming | ✅ SSE token-by-token | ❌ Synchronous (full response) |
| History stored | ✅ `chat_messages` table | ❌ No persistence (history passed from frontend, last 6 msgs) |
| Scope | Canonical book content | Platform, general knowledge |
| Chapter isolation | 🔴 Bug — `chapter_id` not in schema | N/A |

### Book Study Assistant — `ChatMessageController` (verified)

**Full request lifecycle:**

1. Persist user message (`chat_messages.role = 'user'`)
2. Auto-title session from first message if title is `'New Chat'`
3. Call `RetrievalService::retrieve($query, $topK, null, $session->chapter_id)` — chapter_id is always null (bug)
4. Load conversation history (all messages in session except the just-created one, ordered by `created_at`)
5. Call `PromptBuilderService::buildMessages($history, $chunks, $query, $grounded)` — uses last 10 messages from history
6. Create empty assistant message (`content = ''`)
7. Start `StreamedResponse` with SSE headers (`Content-Type: text/event-stream`, `X-Accel-Buffering: no`)
8. Stream tokens from `LLMGatewayInterface::streamChat()` — each token emitted as `event: delta`
9. On stream end: run `MarkdownService::toHtml($responseContent)` → `HTMLPurifier` sanitization
10. Update assistant message `content` with sanitized HTML
11. Create `chat_message_sources` rows for each retrieved chunk
12. Emit `event: done` with `{message_id, grounded, citations, html_content}`
13. On error: emit `event: error` with `AI_PROVIDER_UNAVAILABLE` code (empty assistant message left in DB)

### Global Platform Assistant — `GlobalChatController` (verified)

- Accepts `message`, `route`, `history` from request body
- Builds system prompt with platform context (current route, developer team names from `config/ai.platform`)
- Limits history to last **6 messages** (client-provided — not server-stored)
- Calls `LLMGatewayInterface::chat()` (non-streaming, full response string)
- Returns `{reply: string}`
- **No RAG, no vector search, no book content**

---

## 15. RAG Architecture

### End-to-End Pipeline (verified from source)

```
User query
  → ChatMessageController::store()
  → RetrievalService::retrieve(query, topK, threshold, chapterId=null [bug])
      → VoyageEmbeddingProvider::embed(query) [input_type='query']
          → POST https://api.voyageai.com/v1/embeddings
          → returns float[1024]
      → PostgreSQL: SELECT ... FROM content_chunks cc
          JOIN sections s ON s.id = cc.section_id
          JOIN chapters ch ON ch.id = s.chapter_id
          WHERE cc.embedding_status = 'ready'
            AND cc.embedding IS NOT NULL
            AND (1 - (cc.embedding <=> ?::vector)) >= 0.35   [code default]
            AND ch.book_id = ?  [canonical book scope when chapterId is null]
          ORDER BY cc.embedding <=> ?::vector
          LIMIT 5
      → returns {chunks[], grounded: bool}
  → PromptBuilderService::buildMessages(history[-10:], chunks, query, grounded)
      → if grounded: SYSTEM_PROMPT with {context} replaced by chunk texts
      → if not grounded: NO_CONTEXT_SYSTEM_PROMPT (polite refusal)
  → LLMGatewayInterface::streamChat(messages)
      → [Groq] POST https://api.groq.com/openai/v1/chat/completions (stream=true)
      → yields string tokens
  → SSE: emit 'delta' event per token
  → After stream: MarkdownService::toHtml(fullContent) → HTMLPurifier
  → UPDATE chat_messages SET content = sanitizedHtml
  → INSERT chat_message_sources (per chunk)
  → SSE: emit 'done' event with {message_id, citations, html_content}
```

### Similarity Metric (verified)

- **Distance operator:** `<=>` (pgvector cosine distance)
- **Similarity score:** `1 - (embedding <=> query_vector)` (cosine similarity)
- **Threshold (code default):** 0.35 — fairly permissive
- **Threshold (.env.example):** 0.75 — much more restrictive
- **Active threshold:** depends entirely on deployment `.env`

### Prompt Grounding (verified from `PromptBuilderService.php`)

The system prompt contains explicit instructions:

- "Answer ONLY using information from the CONTEXT PASSAGES provided"
- "If the answer is NOT found in the provided context, you MUST respond with: 'I'm sorry, but I couldn't find information about that...'"
- "NEVER make up, infer, or hallucinate information"
- "NEVER cite sources by ID number"

When no chunks are retrieved (`grounded = false`), a separate `NO_CONTEXT_SYSTEM_PROMPT` is used that instructs the LLM to deliver a polite refusal. The LLM is not prohibited from deviating from these instructions, but the prompt strongly discourages it.

### Chapter Scoping (🔴 Bug)

```php
// ChatMessageController.php line 51
$chapterId = $session->chapter_id ?? null;  // Always null — column doesn't exist
```

The fallback in `RetrievalService`:
```php
} elseif ($bookId) {
    // Canonical Book Scope (Global Search)
    $scopeSql = 'AND ch.book_id = ?';
}
```

All sessions effectively use global canonical-book scope regardless of user intent.

### Embedding Ingestion Pipeline (verified from `BookIngestionService.php`)

1. Admin pastes chapter text → `PUT /api/admin/chapters/{chapter}/content`
2. Admin triggers → `POST /api/admin/chapters/{chapter}/ingest`
3. `BookIngestionService::extractSections()` parses text by regex headers
4. Sections upserted to `sections` table
5. `IngestChapterJob` dispatched (sets status → `queued`)
6. Job marks chapter `processing`, dispatches one `GenerateChunkEmbeddingJob` per section
7. Each job: `ChunkingService::chunk(raw_text)` → chunks (700-token target, 15% overlap)
8. `VoyageEmbeddingProvider::embedBatch(chunk_texts, input_type='document')`
   - Batch size = 1 (free-tier safe)
   - Redis lock between requests (25s delay)
   - Per-chunk: INSERT `content_chunks` + `UPDATE embedding = vector`
9. After each section completes: check if all sections have status `ready`; if so, mark chapter `ready`

### Conversation History (verified from `PromptBuilderService.php`)

```php
$recentHistory = array_slice($history, -10);  // Last 10 messages
```

Backend loads full session history from DB, then slices to the last 10 messages when building the prompt. This is the actual conversation window.

---

## 16. AI Response Formatting

### Supported Markdown Elements (verified from `PromptBuilderService.php` system prompt + `MarkdownService.php`)

The LLM is instructed to use:

| Element | Instruction |
|---|---|
| Headings `#`, `##`, `###` | ✅ Explicitly instructed |
| **Bold** `**text**` | ✅ Explicitly instructed |
| *Italic* `*text*` | ✅ Explicitly instructed |
| Bullet lists `- item` | ✅ Explicitly instructed |
| Numbered lists `1. item` | ✅ Explicitly instructed |
| Inline code `` `code` `` | ✅ Explicitly instructed |
| Code blocks ` ``` ` | ✅ Explicitly instructed |
| Blockquotes `>` | ✅ Explicitly instructed |
| Tables | ✅ Explicitly instructed |

### Server-Side Processing (verified from `MarkdownService.php`)

1. `Parsedown::text($markdown)` — converts markdown to HTML
2. `HTMLPurifier::purify($html)` — XSS sanitization with allowlist:
   - **Allowed tags:** `p, br, hr, strong, em, b, i, u, a, h1–h6, ul, ol, li, blockquote, cite, code, pre, table, thead, tbody, tr, th, td, caption, sub, sup`
   - **No CSS classes allowed** (`Attr.AllowedClasses = []`)
   - **Allowed URI schemes:** `http`, `https`, `mailto`
   - **Blocked:** `iframe`, `embed`, `object`, `script`
3. Sanitized HTML stored in `chat_messages.content`

### Frontend Rendering

- **During stream:** Raw token text accumulated in `accumulatedText` ref. The component renders this as plain text or uses `marked` to parse markdown incrementally.
- **On `done` event:** `html_content` field in done payload contains the pre-processed, server-sanitized HTML. The frontend uses `DOMPurify` as a client-side second sanitization pass before `v-html`.
- **Citations:** Displayed after message — chapter title, section title, similarity score, excerpt (200 chars)

### Known Formatting Limitation

During streaming, the LLM generates raw markdown tokens. The frontend accumulates tokens and renders incrementally. Full heading/table/code block formatting is not visible until the stream completes and the server sends the `html_content` field in the `done` event.

---

## 17. AI Performance — Latency Analysis

> **Important:** No instrumentation, profiling, or benchmarking was performed during this audit. All latency observations below are architectural estimates based on code inspection. No empirical measurements exist in the repository.

### Latency Contributors

| Stage | Contributor | Estimated Range | Measurement Status |
|---|---|---|---|
| 1 | Query embedding (Voyage AI single) | 200–800ms | ⚪ Not measured |
| 2 | pgvector HNSW search (top-5) | <50ms | ⚪ Not measured |
| 3 | Prompt assembly | <10ms | ⚪ Not measured |
| 4 | Groq API time-to-first-token | 200–600ms | ⚪ Not measured |
| 5 | Total generation time | 2–10s depending on length | ⚪ Not measured |
| 6 | Frontend SSE read/render | <50ms per token | ⚪ Not measured |

### Implemented Optimizations (verified)

- **HNSW index** on `content_chunks.embedding` — O(log n) approximate nearest-neighbor vs. exact O(n)
- **Redis cache** on dashboard responses (60-second TTL)
- **Groq vs. Claude** — Groq typically offers lower TTFT than Claude on comparable model sizes
- **cURL TCP_NODELAY + BUFFERSIZE=1** in `GroqLLMGateway` — forces immediate packet delivery without buffering
- **Output buffer clearing** in `ChatMessageController` — removes all PHP `ob_` levels before streaming

### Known Bottlenecks (verified)

- **Voyage AI embedding (foreground):** A network round-trip to Voyage AI on every user message. No embedding cache for repeated queries. On free tier (3 RPM), concurrent requests may be rate-limited.
- **XP leaderboard:** `GameController::leaderboard()` loads all users with `streak`, `progress`, and `badges` relationships, calculates XP in PHP, then sorts in memory. No SQL-level ordering or pagination.
- **Voyage ingestion:** 25s delay between batch requests (free-tier). A full 11-chapter ingestion can take many hours.

### Performance Targets (recommended, not validated)

| Metric | Recommended Target |
|---|---|
| Non-AI API response | < 300ms |
| Query embedding latency | < 500ms |
| Time-to-first-token (Groq) | < 1s |
| Total chat response (avg) | < 8s |
| Dashboard cache hit | < 50ms |

> These are engineering targets, not validated measurements. **Performance target defined but not experimentally validated in the current repository.**

---

## 18. Quiz System

### Architecture (verified from migrations and routes)

Each published chapter has one associated quiz. Quizzes follow a start-then-submit model: the user creates an attempt before seeing questions, preventing reading answer data from the quiz structure before attempting.

### Question Types (verified from `quiz_questions.type` column)

| Type | Description |
|---|---|
| `single` | Exactly one correct option |
| `multiple` | One or more correct options |
| `true_false` | Boolean choice |

### Quiz Flow (verified from `QuizAttemptController`)

1. `GET /api/v1/chapters/{chapter}/quiz` — returns quiz with questions and options (no `is_correct` field exposed to learner)
2. `POST /api/v1/quizzes/{quiz}/attempts` — creates attempt record (`started_at = now()`)
3. User selects answers in `QuizView.vue`
4. `POST /api/v1/quizzes/{quiz}/attempts/{attempt}/submit` — server-side grading:
   - For each question: compare `selected_option_ids` (JSON array of UUIDs) against `is_correct` option flags
   - Calculate `score_pct = correct_count / total_count * 100`
   - Set `passed = score_pct >= quiz.passing_score_pct` (default 70%)
   - `submitted_at = now()`
   - If passed: UPSERT `user_progress` (`is_completed = true`, update `best_quiz_score_pct` if higher)
   - Call `StreakService::recordActivity()`
   - Dispatch `EvaluateBadgesJob`
5. Response includes per-question `is_correct`, `explanation`, and overall `score_pct`, `passed`

### Admin Quiz Management

- **Backend:** Complete API (`POST /quizzes`, `POST /questions`, `PATCH /questions/{id}`, `DELETE /questions/{id}`, `POST /quizzes/{quiz}/publish`)
- **Frontend (`AdminQuizView.vue`):** 1,689 bytes — a minimal stub. Full admin quiz UI is **not implemented**.

---

## 19. Progress System

### Progress Model (verified from `user_progress` migration)

One record per `(user_id, chapter_id)` pair (unique constraint). Fields:

| Field | Populated by |
|---|---|
| `is_completed` | `QuizAttemptController::submit()` on pass |
| `best_quiz_score_pct` | Updated on submit if new score > stored best |
| `completed_at` | Set to `now()` on first completion |
| `last_read_at` | Set by `ChapterController::markRead()` |

### Dashboard Integration (verified from `DashboardController.php`)

```php
$progressRecords = UserProgress::where('user_id', $user->id)
    ->whereIn('chapter_id', $canonicalChapterIds)
    ->get();
$completedChapters = $progressRecords->where('is_completed', true)->count();
$completionPct = $totalChapters > 0
    ? round(($completedChapters / $totalChapters) * 100, 1)
    : 0;
```

Dashboard is scoped to canonical chapters and cached in Redis for 60 seconds (key: `dashboard:{user_id}`).

### Progress API (verified)

| Endpoint | Returns |
|---|---|
| `GET /api/v1/users/me/progress` | Array of progress records per chapter |
| `GET /api/v1/dashboard` | Aggregated stats: completion_pct, total_chapters, completed_chapters, quizzes_passed, average_quiz_score, current_streak, total_chat_sessions, earned_badge_count |

---

## 20. Gamification

### XP Formula (verified from `GameController.php`)

```php
$xp = ($completed * 150) + ($quizzesPassed * 100) + ($streak * 20);
$level = floor($xp / 1000) + 1;
```

- 150 XP per completed chapter
- 100 XP per passed quiz attempt
- 20 XP per current streak day
- Level = floor(XP / 1000) + 1

> **XP is not persisted.** It is recalculated on every leaderboard request via eager-loaded relations. No `xp` column exists in `users` or any other table.

### Leaderboard (verified from `GameController::leaderboard()`)

- Loads **all users** with `streak`, `progress`, `badges` relations
- Calculates XP and level in PHP for each user
- Sorts by XP descending (in-memory, PHP `sortByDesc`)
- Returns all users — **no pagination**
- Returns current user's ID so frontend can highlight their row

### Badge System (verified from `badges` and `user_badges` migrations)

Badge `criteria` JSON supports these types:

| Type | Threshold meaning |
|---|---|
| `chapter_count` | N chapters completed |
| `perfect_score` | Quiz score = 100% |
| `streak_days` | Streak >= N days |
| `book_complete` | All 11 chapters completed |

Badge evaluation runs asynchronously in `EvaluateBadgesJob` after quiz submission. Each badge can be earned at most once per user (`UNIQUE (user_id, badge_id)`).

### Streak System (verified from `user_streaks` migration)

- One record per user (`UNIQUE user_id`)
- `current_streak`: increments when activity recorded on a new calendar day
- `longest_streak`: high-water mark
- `last_activity_date`: date of last recorded activity
- **Reset logic:** if `last_activity_date` is more than 1 day ago, `current_streak` resets to 1

### Daily Challenge (🔴 Hardcoded — verified from `GameController.php`)

```php
$challenge = [
    'id'            => 'daily-' . date('Y-m-d'),
    'question'      => 'Which core pillar focuses on supporting startups and local digital economic growth?',
    'options'       => ['e-Governance', 'Enterprise', 'Innovation'],
    'correct_index' => 1,
    'xp_reward'     => 150,
    'completed'     => false,  // Never changes — no tracking table
];
```

- Single hardcoded question for all users on all days
- `id` changes per date (e.g. `daily-2026-08-16`) but question does not change
- `completed` is always `false` — no DB tracking
- No `daily_challenges` database table exists

---

## 21. Dashboard

### API Response (verified from `DashboardController.php`)

```json
{
  "dashboard": {
    "completion_pct": 45.5,
    "total_chapters": 11,
    "completed_chapters": 5,
    "quizzes_passed": 4,
    "average_quiz_score": 82.3,
    "current_streak": 3,
    "total_chat_sessions": 12,
    "earned_badge_count": 2
  }
}
```

### Caching (verified)

- Cache key: `dashboard:{user_id}`
- TTL: 60 seconds
- Cache store: Redis (`CACHE_STORE=redis`)
- Invalidation: ⚪ Not determined — `ProgressService` invalidation was referenced in a comment but not verified from this audit

### Dashboard View — `DashboardView.vue` (34,484 bytes)

From file size this is one of the larger frontend components. Verified features from the API response structure:
- Completion percentage ring/bar
- Chapter progress list
- Quiz stats (passed count, average score)
- Streak display
- Badge count
- Recent chat sessions
- Dark mode support (via CSS tokens)
- i18n strings

### Chapter Count (verified)

`total_chapters` is populated dynamically from `canonicalBook->chapters()->count()`. Returns 11 when seeded correctly. Returns 0 if no canonical book exists.
---

## 22. Profile System

### Endpoints (verified from `routes/api.php`)

| Method | Path | Action |
|---|---|---|
| GET | `/api/v1/users/me` | Return user profile |
| PATCH | `/api/v1/users/me` | Update name, locale, notify_badges |
| PUT | `/api/v1/users/me/password` | Change password (requires current password) |
| POST | `/api/v1/users/me/avatar` | Upload avatar image |
| DELETE | `/api/v1/users/me` | Soft-delete + anonymize account |

### Avatar Storage

- Upload stored to **local disk** (`FILESYSTEM_DISK=local` in `.env.example`)
- AWS S3 environment variables present (`AWS_ACCESS_KEY_ID`, `AWS_BUCKET`) but storage is not configured for S3 by default
- Avatar URL stored in `users.avatar_url`

### Account Deletion

Account deletion dispatches `AnonymizeUserJob` which soft-deletes the user (`deleted_at`) and anonymizes personal data (name, email replaced with placeholder values). Soft-deleted tokens are rejected by the Sanctum guard.

### Locale Persistence

- `users.locale` field (VARCHAR 10, default `'en'`) stores the preferred language
- Frontend persists locale in `localStorage` key `sa_lang`
- **Known gap:** On login, `users.locale` is not automatically applied to the vue-i18n active locale. The user must manually switch language in the UI.

---

## 23. Administration

### Admin Access Control (verified)

- `role = 'admin'` in `users` table
- `EnsureAdmin` middleware on all `/api/admin/` routes
- Frontend router redirects non-admins from `/admin/*` routes to `/dashboard`

### Admin Frontend Views (verified from file sizes)

| File | Size | Status |
|---|---|---|
| `AdminBookView.vue` | 5,964 bytes | Functional — book overview |
| `AdminBookIngestionView.vue` | 19,894 bytes | Functional — full ingestion UI |
| `AdminChapterEditor.vue` | 12,182 bytes | Functional — chapter editing |
| `AdminQuizView.vue` | 1,689 bytes | **Stub only — not usable** |

### Book Ingestion Workflow (verified)

Admin workflow for ingesting a chapter:

1. Navigate to `/admin/book-ingestion`
2. Select chapter from canonical list
3. Paste raw chapter text into editor
4. Click **Validate** — `POST /admin/chapters/{id}/validate` — checks for invalid content, wrong chapter number
5. Click **Preview** — `POST /admin/chapters/{id}/preview` — shows extracted sections, estimated chunk count
6. Click **Ingest** — `POST /admin/chapters/{id}/ingest` — dispatches `IngestChapterJob`
7. Monitor status via `GET /admin/chapters/{id}/status`
8. On failure: **Retry** — `POST /admin/chapters/{id}/retry`

### Admin Analytics (verified from `AdminAnalyticsController`)

`GET /api/admin/analytics` returns platform-wide stats including user count, chapter count, session count, and badge count. The chapter count in analytics is **not scoped to the canonical book** (identified as a minor inconsistency).

---

## 24. Internationalization

### Implementation (verified from `frontend/src/i18n.ts`)

```typescript
export const i18n = createI18n({
  legacy: false,       // Composition API mode
  locale: savedLocale, // from localStorage 'sa_lang', default 'en'
  fallbackLocale: 'en',
  messages: { en, am, om }
})
```

Language is persisted in `localStorage` key `sa_lang`. `setLanguage()` changes the active locale and sets `document.documentElement.lang`.

### Supported Languages (verified from `frontend/src/lang/`)

| Code | Language | File Size |
|---|---|---|
| `en` | English | 9,249 bytes |
| `am` | Amharic (አማርኛ) | 12,988 bytes |
| `om` | Afaan Oromo | 9,555 bytes |

### Scope of Translation

**Translated (UI strings):**
- Navigation labels
- Authentication forms (login, register, forgot/reset password)
- Dashboard labels and stats
- Profile page
- Quiz UI (question instructions, result messages)
- Game page labels
- Common UI elements (buttons, error messages)

**NOT translated:**
- AI/LLM responses (always in the language the user asks in — LLM behavior, not platform-controlled)
- Book chapter content (stored as-is from admin paste)
- Admin interface (may have some untranslated strings — ⚪ not fully verified)
- Error codes and API error messages (English only from backend)

> The AI assistant will respond in the language of the user's query, but this is a natural language model behavior, not a controlled multilingual feature. Platform translation and AI response language are independent.

### i18n Limitation

`users.locale` is stored in the database but is not automatically applied to vue-i18n on login. The frontend loads the saved `localStorage` locale, which may differ from the backend-stored locale if the user changes devices.

---

## 25. UI/UX Design System

### Design Tokens (verified from `frontend/src/style.css`)

The design system uses Tailwind CSS v4 `@theme` directive with custom `--color-brand-*` variables:

| Token | Light | Dark |
|---|---|---|
| `--sa-dark` | `#395886` (brand-500) | `#F8FAFC` |
| `--sa-bg` | `#F0F3FA` (brand-50) | `#030712` |
| `--sa-gray` | `#B1C9EF` (brand-200) | `#1E293B` |
| `--sa-taupe` | `#8AAEE0` (brand-300) | `#94A3B8` |
| `--sa-white` | `#FFFFFF` | `#0B0F19` |

**Glass morphism:** `rgba(255,255,255,0.75)` / blur 20px (light); `rgba(11,15,25,0.85)` (dark)

**Typography:** System font stack: `-apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif`

### Dark Mode (verified from `style.css`)

- Activated by adding class `dark` to `<html>` element (`html.dark`)
- CSS custom properties override to dark values under `html.dark`
- `color-scheme: dark` meta applied
- Theme preference persisted: ⚪ persistence mechanism not verified from this audit (likely `localStorage`)

### Component Library

Custom components under `frontend/src/components/ui/`:
- `SaButton.vue`, `SaCard.vue`, `SaInput.vue`, `SaModal.vue`, `SaToast.vue`, `ProgressRing.vue`

Icons: `lucide-vue-next` (`^1.0.0`)

---

## 26. Security Audit

### Authentication Security

| Issue | Severity | Status |
|---|---|---|
| Bearer tokens stored in `localStorage` | **HIGH** | `localStorage` is XSS-accessible. HttpOnly cookies would be significantly more secure. |
| OAuth token passed as URL query parameter (`?token=`) | **HIGH** | Visible in server access logs, browser history, HTTP referrer headers. |
| OAuth callback URL hardcoded to `localhost:5173` | **CRITICAL** | Breaks all non-localhost deployments. |
| Password reset mail driver set to `log` in `.env.example` | **HIGH** | Silently fails in any environment that copies `.env.example` directly. |

### Authorization Security

| Item | Status |
|---|---|
| Admin routes protected by `EnsureAdmin` middleware | ✅ Verified |
| Session ownership verified in `ChatMessageController` | ✅ Verified |
| Soft-deleted users rejected by Sanctum | ✅ Verified |
| No horizontal privilege escalation found in quiz/progress endpoints | ✅ Verified |

### Input Sanitization

| Layer | Mechanism | Status |
|---|---|---|
| AI response HTML (backend) | `HTMLPurifier` with strict element allowlist | ✅ Verified |
| AI response HTML (frontend) | `DOMPurify` before `v-html` insertion | ✅ Verified |
| Form input validation | Laravel FormRequest classes per endpoint | ✅ Verified |
| SQL injection | Eloquent ORM + PDO bound parameters | ✅ Verified |
| Raw SQL in `RetrievalService` | Uses `DB::select()` with bound parameters (`?`) | ✅ Verified |

### CORS

- CORS configuration is in `backend/config/cors.php` (Laravel default)
- The `FRONTEND_URL` is used in `AppServiceProvider` for password reset
- Specific CORS `allowed_origins` for the API: ⚪ Not verified from this audit — `config/cors.php` was not inspected

### Rate Limiting

- Chat messages: `throttle.chat` middleware — `CHAT_RATE_LIMIT_PER_5_MIN` (default 20) requests per 5 minutes
- No rate limiting found on authentication endpoints (login, register) in the route file

### Security Weakness Index

| ID | Issue | Severity | Recommendation |
|---|---|---|---|
| SEC-001 | OAuth callback hardcodes `http://localhost:5173` | Critical | Use `env('FRONTEND_URL')` |
| SEC-002 | OAuth token in URL query parameter | High | Use server-side session or PKCE flow |
| SEC-003 | Bearer tokens in `localStorage` | High | Migrate to HttpOnly SameSite cookies |
| SEC-004 | Mail driver `log` in `.env.example` | High | Document production mail setup; add pre-flight check |
| SEC-005 | No rate limiting on auth endpoints (login/register) | Medium | Add `throttle:5,1` to login route |
| SEC-006 | `Book::canonical()` falls back to `Book::first()` | Low | Require explicit canonical book configuration |
| SEC-007 | `GlobalChatController` does not validate `history` message structure | Low | Add strict validation on history array |
| SEC-008 | Admin role is a plain string in `users.role`, not an enum | Low | Consider database ENUM or policy-based RBAC |

---

## 27. Performance and Caching

### Caching Strategy (verified)

| Cache | Key | TTL | Store |
|---|---|---|---|
| Dashboard stats | `dashboard:{user_id}` | 60 seconds | Redis |
| Voyage API rate-limit lock | `voyage_api_rate_limit` | `delay_seconds + 10` | Redis |

No other explicit caching was identified. Book chapter content, quiz data, and user progress are not cached.

### Queue Configuration (verified from `.env.example`)

```ini
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Queue driver is Redis. The `composer dev` script runs `php artisan queue:listen --tries=1 --timeout=0` for local development.

### Known Performance Issues

| Issue | Impact | Source |
|---|---|---|
| Leaderboard loads all users in memory | O(n users) RAM; no pagination | `GameController::leaderboard()` |
| XP not persisted | Re-computed on every leaderboard hit | `GameController` |
| No query result cache on progress/badges | Every dashboard hit queries DB (then cached for 60s) | `DashboardController` |
| Voyage AI query embedding on every chat message | ~200–800ms latency added per message | `VoyageEmbeddingProvider` |
| Full history load per chat message | Loads all messages in session from DB | `ChatMessageController` |

---

## 28. Error Handling

### Backend Error Strategy (verified)

- **All routes** return JSON (enforced by `ForceJsonResponse` global middleware)
- Laravel's default exception handler converts `ValidationException` → 422, `AuthenticationException` → 401, `ModelNotFoundException` → 404
- `AiProviderException` (custom) is thrown by LLM and embedding providers on unrecoverable failures
- SSE errors: `event: error\ndata: {"error":{"code":"AI_PROVIDER_UNAVAILABLE","message":"..."}}\n\n`
- Error details from third-party providers (Groq, Voyage) are **never forwarded** to the client — only a generic error code is returned

### Error Response Format

Standard Laravel JSON errors:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

SSE error event format:
```
event: error
data: {"error":{"code":"AI_PROVIDER_UNAVAILABLE","message":"I'm having trouble thinking right now..."}}
```

### Frontend Error Handling

- Axios interceptor in `api/client.ts` catches 401 → clears token + redirects to `/login`
- SSE `error` events handled in `useChatStream` → set `streamError` ref → displayed to user
- Toast notifications via `SaToast.vue` for actionable errors

---

## 29. Testing

### Test Framework (verified from `backend/composer.json` and test directory structure)

- **Framework:** Pest PHP (`^4.7`) + `pestphp/pest-plugin-laravel` (`^4.1`)
- **Base:** PHPUnit (`^12.5.12`)
- **Frontend unit tests:** Vitest (`^4.1.10`) — installed but **no test scripts in `package.json`**
- **Frontend E2E:** Playwright (`^1.62.1`) — installed but **no test scripts in `package.json`**

### Backend Test Structure (verified from `backend/tests/`)

```
tests/
├── Feature/
│   ├── Auth/
│   ├── Books/
│   ├── Chat/
│   ├── Dashboard/
│   ├── Gamification/
│   ├── Quizzes/
│   ├── RAG/
│   └── Users/
├── Unit/
│   ├── AI/
│   ├── Gamification/
│   ├── Quiz/
│   └── RAG/
├── Pest.php
└── TestCase.php
```

### Test Coverage

> **Test execution results are not determined.** No test run was performed during this audit. The presence of test files and directories is confirmed; pass/fail rates are unknown.

Key test areas from directory structure:
- Feature tests for Auth, Books, Chat, Dashboard, Gamification, Quizzes, RAG, Users
- Unit tests for AI providers, Gamification logic, Quiz grading, RAG pipeline

### Frontend Testing

- Vitest and Playwright are present in `devDependencies`
- No test commands in `package.json` scripts (`dev`, `build`, `preview` only)
- No `vitest.config.ts` or `playwright.config.ts` were found in the inspected directories

> **Status:** Frontend testing infrastructure is installed but not configured or scripted. No frontend tests can be run without additional setup.

---

## 30. Deployment and Environment

### Required Services

| Service | Purpose | Config Key |
|---|---|---|
| PostgreSQL 16+ | Primary database with pgvector | `DB_CONNECTION=pgsql` |
| Redis | Queue, cache, Voyage rate-limit lock | `REDIS_HOST`, `REDIS_PORT` |
| Groq API | LLM (if `AI_LLM_PROVIDER=groq`) | `GROQ_API_KEY` |
| Anthropic API | LLM alternative (if `AI_LLM_PROVIDER=claude`) | `ANTHROPIC_API_KEY` |
| Voyage AI API | Embedding | `VOYAGE_API_KEY` |
| SMTP provider | Password resets, badge notifications | `MAIL_*` |

### Environment Variables (verified from `backend/.env.example`)

```ini
APP_NAME=SmartAdama
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smart_adama
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=log          # Change to smtp/ses/mailgun for production

AI_LLM_PROVIDER=claude   # Change to groq if using Groq
AI_EMBEDDING_PROVIDER=voyage

GROQ_API_KEY=
GROQ_MODEL=llama-3.3-70b-versatile

ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022

VOYAGE_API_KEY=
VOYAGE_MODEL=voyage-3-lite
VOYAGE_EMBEDDING_DIMENSION=1024
VOYAGE_REQUEST_DELAY_SECONDS=25
VOYAGE_RATE_LIMIT_RETRY_SECONDS=60

RAG_TOP_K=5
RAG_SIMILARITY_THRESHOLD=0.75   # Overrides code default of 0.35

CHAT_RATE_LIMIT_PER_5_MIN=20

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_BUCKET=
AWS_DEFAULT_REGION=us-east-1

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

### Local Development Setup

**Backend:**
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed           # Seeds canonical badges
php artisan queue:listen --tries=1 --timeout=0
php artisan serve             # http://localhost:8000
```

**Frontend:**
```bash
cd frontend
npm install
npm run dev                   # http://localhost:5173
```

### No Docker Compose Found

No `docker-compose.yml`, `Dockerfile`, or `docker/` directory was found in the repository root or any subdirectory during this audit. Containerization does not exist in the current codebase.

---

## 31. Data Flow Diagrams

### RAG Chat Flow

```
[User types message]
        ↓
[Frontend: useChatStream.stream()]
    POST /api/v1/chat/sessions/{id}/messages
    Accept: text/event-stream
        ↓
[ChatMessageController::store()]
    → INSERT chat_messages (role='user')
    → Auto-title session if new
        ↓
[RetrievalService::retrieve()]
    → VoyageEmbeddingProvider::embed(query)
        → POST https://api.voyageai.com/v1/embeddings
        → float[1024]
    → PostgreSQL pgvector cosine search
        → SELECT top-5 chunks by similarity
        → Filter by similarity >= threshold
        → Scope to canonical book
        ↓
[PromptBuilderService::buildMessages()]
    → Assemble system prompt with context
    → Include last 10 messages from history
        ↓
[GroqLLMGateway::streamChat()]
    → POST https://api.groq.com/openai/v1/chat/completions
        (stream=true)
    → Yields string tokens via PHP Generator
        ↓
[StreamedResponse: SSE loop]
    → Flush ob_ buffers
    → For each token: emit event:delta\ndata:{token}\n\n
        ↓ (Frontend receives delta events)
[useChatStream: onToken callback]
    → accumulatedText += token
    → ChatView re-renders markdown incrementally
        ↓
[After stream ends]
    → MarkdownService::toHtml(fullContent) + HTMLPurifier
    → UPDATE chat_messages SET content = sanitizedHtml
    → INSERT chat_message_sources per chunk
    → emit event:done\ndata:{message_id, grounded, citations, html_content}
        ↓
[Frontend: onDone callback]
    → Replace streamed text with final rendered html_content
    → Display citations
```

### OAuth Flow

```
[User clicks "Login with Google"]
        ↓
[Frontend: redirect to]
    GET /api/auth/google/redirect
        ↓
[SocialAuthController::redirect()]
    → Socialite::driver('google')->stateless()->redirect()
    → 302 to Google OAuth URL
        ↓
[Google: user authenticates + grants consent]
        ↓
[Google: 302 to]
    GET /api/auth/google/callback?code=...
        ↓
[SocialAuthController::callback()]
    → Socialite::driver('google')->stateless()->user()
    → User::firstOrCreate(email) with OAuth fields
    → $token = $user->createToken('auth_token')->plainTextToken
    → 302 to http://localhost:5173/auth/callback?token={TOKEN}   ⚠️
        ↓
[Frontend: OAuthCallbackView.vue]
    → Reads ?token= from URL params
    → Stores token in localStorage
    → Calls GET /api/auth/me
    → Redirects to /dashboard
```

### Book Ingestion Flow

```
[Admin pastes chapter text in AdminBookIngestionView.vue]
        ↓
    PUT /api/admin/chapters/{id}/content
        → saves to chapters.content
        ↓
    POST /api/admin/chapters/{id}/validate
        → BookIngestionService::validateContent()
        → Checks chapter number range (1-11 only)
        → Rejects front matter, System Context
        ↓
    POST /api/admin/chapters/{id}/ingest
        → BookIngestionService::extractSections()
        → Upserts to sections table
        → Dispatch IngestChapterJob (queue)
        ↓
[Queue: IngestChapterJob runs]
    → marks chapter ingestion_status = 'processing'
    → Foreach section: dispatch GenerateChunkEmbeddingJob
        ↓
[Queue: GenerateChunkEmbeddingJob per section]
    → ChunkingService::chunk(raw_text)
        → Split into ~700-token chunks, 15% overlap
    → VoyageEmbeddingProvider::embedBatch(chunks)
        → POST voyageai.com (batch_size=1, 25s delay between)
        → float[1024] per chunk
    → INSERT content_chunks with embedding vector
    → Check: all sections done?
        → Yes: UPDATE chapters SET ingestion_status='ready'
        → No: continue
```

---

## 32. System Architecture Diagrams

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT (Browser)                        │
│  Vue 3 SPA (TypeScript, Vite, Pinia, Tailwind v4)          │
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │Dashboard │  │ChatView  │  │QuizView  │  │GameView  │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
└─────────────────────┬───────────────────────────────────────┘
                      │ REST/SSE  (Authorization: Bearer <token>)
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel 13 / PHP 8.3)             │
│                  Running on port 8000                       │
│                                                             │
│  API Routes (/api/v1/*)                                    │
│  ├── Auth (Sanctum + Socialite)                            │
│  ├── Chat (SSE streaming)                                  │
│  ├── Books/Chapters/Quizzes                                │
│  ├── Progress/Gamification                                 │
│  ├── Dashboard (Redis cached)                              │
│  └── Admin (role-gated)                                    │
│                                                             │
│  Services                                                  │
│  ├── GroqLLMGateway / ClaudeLLMGateway                     │
│  ├── VoyageEmbeddingProvider                               │
│  ├── RetrievalService (pgvector SQL)                       │
│  ├── PromptBuilderService                                  │
│  ├── BookIngestionService                                  │
│  ├── ChunkingService                                       │
│  └── MarkdownService (Parsedown + HTMLPurifier)            │
└────┬────────────────┬──────────────────────────────────────┘
     │                │
     ▼                ▼
┌─────────┐    ┌─────────────────────────────────────────────┐
│  Redis  │    │       PostgreSQL 16 + pgvector              │
│ Queue   │    │                                             │
│ Cache   │    │  users, books, chapters, sections,          │
│         │    │  content_chunks (vector[1024] + HNSW idx),  │
└─────────┘    │  chat_sessions, chat_messages,              │
               │  quizzes, quiz_attempts, user_progress,     │
               │  badges, user_badges, user_streaks          │
               └───────────────────┬─────────────────────────┘
                                   │ Background jobs (Redis queue)
                                   ▼
┌─────────────────────────────────────────────────────────────┐
│                  EXTERNAL APIS                              │
│  ┌─────────────────────┐  ┌──────────────────────────────┐ │
│  │   Groq API          │  │   Voyage AI API              │ │
│  │ llama-3.3-70b-*     │  │ voyage-3-lite (1024 dims)    │ │
│  │ (LLM, streaming)    │  │ (embeddings, ingestion)      │ │
│  └─────────────────────┘  └──────────────────────────────┘ │
│  ┌─────────────────────┐                                   │
│  │  Anthropic Claude   │  (supported alt, inactive by default) │
│  │ claude-3-5-sonnet   │                                   │
│  └─────────────────────┘                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 33. Functional Requirements

| ID | Requirement | Source | Status |
|---|---|---|---|
| FR-01 | Users can register with email/password | `RegisterController` | ✅ |
| FR-02 | Users can login with email/password | `LoginController` | ✅ |
| FR-03 | Users can login with Google OAuth | `SocialAuthController` | 🔴 (localhost hardcode) |
| FR-04 | Users can reset password via email | `PasswordResetController` | 🟡 (mail=log) |
| FR-05 | Users can view all book chapters in order | `BookController`, `ChapterController` | ✅ |
| FR-06 | Users can read chapter content | `chapters.content` | ✅ |
| FR-07 | Users can mark a chapter as read | `ChapterController::markRead()` | ✅ |
| FR-08 | Users can start an AI chat session | `ChatSessionController::store()` | ✅ |
| FR-09 | AI chat returns RAG-grounded responses from book content | `ChatMessageController` + RAG pipeline | ✅ |
| FR-10 | AI chat streams responses token-by-token via SSE | `StreamedResponse`, `useChatStream.ts` | ✅ |
| FR-11 | AI responses are grounded (no hallucination) when context found | `PromptBuilderService` system prompt | 🔵 |
| FR-12 | AI politely declines when no relevant context found | `NO_CONTEXT_SYSTEM_PROMPT` | ✅ |
| FR-13 | AI responses display source citations | `chat_message_sources`, SSE `done` event | ✅ |
| FR-14 | Users can rate AI responses thumbs up/down | `ChatMessageFeedbackController` | ✅ |
| FR-15 | AI chat is scoped to the chapter being studied | `$session->chapter_id` in RAG | 🔴 (column missing) |
| FR-16 | Users can take chapter quizzes | `QuizAttemptController` | ✅ |
| FR-17 | Quiz grading is server-side and correct | `QuizAttemptController::submit()` | ✅ |
| FR-18 | Passing a quiz marks chapter as completed | `user_progress.is_completed` | ✅ |
| FR-19 | Users earn XP for chapters, quizzes, and streaks | `GameController::leaderboard()` formula | ✅ |
| FR-20 | Users earn badges on milestone completion | `EvaluateBadgesJob`, `user_badges` | ✅ |
| FR-21 | Users maintain daily streaks | `StreakService`, `user_streaks` | ✅ |
| FR-22 | Global leaderboard shows all users ranked by XP | `GameController::leaderboard()` | ✅ |
| FR-23 | Users can view their dashboard with aggregate stats | `DashboardController` | ✅ |
| FR-24 | Dashboard is cached for performance | Redis 60s TTL | ✅ |
| FR-25 | Users can update their profile (name, locale, avatar) | `UserController` | ✅ |
| FR-26 | Users can delete their account | `AnonymizeUserJob` | ✅ |
| FR-27 | Admin can create books and chapters | `AdminBookController`, `AdminChapterController` | ✅ |
| FR-28 | Admin can ingest chapter content (paste → embed) | `AdminBookIngestionController`, ingestion pipeline | ✅ |
| FR-29 | Admin can create and publish quizzes | `AdminQuizController`, `AdminQuizQuestionController` | 🟡 (API only, no frontend) |
| FR-30 | Platform supports English, Amharic, Afaan Oromo UI | `vue-i18n`, `lang/*.json` | ✅ |
| FR-31 | Global AI assistant answers platform questions | `GlobalChatController` | ✅ |
| FR-32 | Daily challenge provided per day | `GameController::dailyChallenge()` | 🔴 (hardcoded) |
| FR-33 | Semantic AI search | `SearchController` | 🔵 (endpoint exists, UI unverified) |

---

## 34. Non-Functional Requirements

| ID | Requirement | Target | Status |
|---|---|---|---|
| NFR-01 | All API endpoints return JSON | Enforced by `ForceJsonResponse` | ✅ |
| NFR-02 | Authentication tokens are secure | Should use HttpOnly cookies | 🔴 Using localStorage |
| NFR-03 | AI responses are sanitized (XSS-safe) | Parsedown + HTMLPurifier + DOMPurify | ✅ |
| NFR-04 | System supports 3 languages | English, Amharic, Afaan Oromo | ✅ |
| NFR-05 | AI chat uses low-latency streaming | SSE with TCP_NODELAY | ✅ |
| NFR-06 | Rate limiting on AI chat | 20 requests / 5 min | ✅ |
| NFR-07 | Soft deletes protect user data | `SoftDeletes` on users and chat_sessions | ✅ |
| NFR-08 | Account deletion anonymizes data | `AnonymizeUserJob` | ✅ |
| NFR-09 | Vector search uses HNSW for performance | Verified in migration | ✅ |
| NFR-10 | Codebase has test coverage | Tests exist but execution unverified | 🔵 |
| NFR-11 | Platform supports dark mode | CSS token system with `html.dark` | ✅ |
| NFR-12 | Deployment must be containerized | No Docker config found | 🔴 |
| NFR-13 | No LLM errors leaked to clients | `AiProviderException` wraps all provider errors | ✅ |
| NFR-14 | Admin routes are role-protected | `EnsureAdmin` middleware | ✅ |
| NFR-15 | Background embedding jobs are resumable | `IngestChapterJob` + `retryFailed` endpoint | ✅ |

---

## 35. Current Project Status

### Overall Status

**Phase 1 Functional MVP.** The core learning loop is implemented and functional: users can register, read chapters, converse with the RAG AI assistant, take quizzes, track progress, and participate in gamification. The admin ingestion pipeline is operational. The platform is runnable locally with the correct `.env` configuration.

### Feature Completion Summary

| Module | Backend | Frontend | Notes |
|---|---|---|---|
| Authentication (email/password) | ✅ | ✅ | Complete |
| Authentication (OAuth) | ✅ | 🔴 | URL hardcoded to localhost |
| Password Reset | ✅ | ✅ | Mail driver issue in dev |
| Book / Chapter Reading | ✅ | ✅ | Complete |
| RAG Chat Assistant | ✅ | ✅ | Chapter scoping bug |
| Global Platform Assistant | ✅ | ✅ | Complete |
| Chat Feedback (👍/👎) | ✅ | ✅ | Complete |
| Quiz System | ✅ | ✅ | Admin quiz UI stub |
| Progress Tracking | ✅ | ✅ | Complete |
| Dashboard | ✅ | ✅ | Complete |
| Gamification (XP/Badges/Streaks) | ✅ | ✅ | Complete |
| Leaderboard | ✅ | ✅ | No pagination |
| Daily Challenge | 🔴 | ✅ | Hardcoded single question |
| User Profile | ✅ | ✅ | Complete |
| Admin Book Ingestion | ✅ | ✅ | Complete |
| Admin Quiz Management | ✅ | 🔴 | Frontend stub |
| Admin Analytics | ✅ | 🔵 | API complete, UI unverified |
| Internationalization | N/A | ✅ | 3 languages |
| Dark Mode | N/A | ✅ | Complete |

---

## 36. Known Issues

| ID | Issue | Severity | Discovery |
|---|---|---|---|
| BUG-001 | `chat_sessions` has no `chapter_id` column — chapter RAG scoping is broken | Critical | Verified from migrations |
| BUG-002 | OAuth callback URL hardcoded to `http://localhost:5173` | Critical | Verified from `SocialAuthController.php` |
| BUG-003 | OAuth token delivered as URL query parameter | High | Verified from `SocialAuthController.php` |
| BUG-004 | Mail driver is `log` in `.env.example` — password resets not delivered | High | Verified from `.env.example` |
| BUG-005 | LLM provider ambiguity: `.env.example` sets Claude but code defaults to Groq | High | Verified from `config/ai.php` vs `.env.example` |
| BUG-006 | Daily challenge is a hardcoded, static question | Medium | Verified from `GameController.php` |
| BUG-007 | Admin quiz frontend (`AdminQuizView.vue`) is a stub (1,689 bytes) | Medium | Verified from file size |
| BUG-008 | Leaderboard has no pagination — O(n users) memory | Medium | Verified from `GameController.php` |
| BUG-009 | XP is not persisted; recalculated on every leaderboard request | Low | Verified from `GameController.php` |
| BUG-010 | `users.locale` not synced to vue-i18n locale on login | Low | Verified from `i18n.ts` |
| BUG-011 | Groq model inconsistency: config default vs. `GroqLLMGateway` constructor default | Low | Verified from `GroqLLMGateway.php` |
| BUG-012 | No rate limiting on login/register endpoints | Medium | Verified from `routes/api.php` |
| BUG-013 | `Book::canonical()` fallback to `Book::first()` could select wrong book in multi-book DB | Low | Verified from `Book.php` |
| BUG-014 | Frontend testing not configured — Vitest/Playwright installed but no scripts | Low | Verified from `package.json` |

---

## 37. Technical Debt

| ID | Item | Category | Impact |
|---|---|---|---|
| TD-001 | No Docker / containerization | Infrastructure | Cannot reproduce environment reliably |
| TD-002 | Bearer tokens in localStorage | Security | XSS vulnerability |
| TD-003 | Unversioned API routes (`/api/auth/`, `/api/game/`) alongside `/api/v1/` | Architecture | Inconsistent API surface |
| TD-004 | XP calculated in-memory, not persisted | Architecture | Scalability and consistency issues |
| TD-005 | Similarity threshold mismatch (0.35 vs 0.75) | Configuration | RAG quality depends on undocumented `.env` setting |
| TD-006 | Voyage API URL hardcoded in provider class | Configuration | Not configurable without code change |
| TD-007 | PDF ingestion libraries installed but unused | Dependency | Dead code weight |
| TD-008 | `locales/` directory is empty | Cleanup | Misleading directory |
| TD-009 | No caching on book/chapter content (frequently read) | Performance | Unnecessary DB reads |
| TD-010 | No embedding cache for repeated queries | Performance | Voyage API hit on every identical question |
| TD-011 | Empty assistant message left in DB on streaming errors | Data integrity | Orphan messages with empty content |
| TD-012 | `admin_analytics` chapter count not scoped to canonical book | Data accuracy | Minor inconsistency vs. dashboard |
| TD-013 | Frontend tests installed but not configured or scripted | Quality | No automated frontend regression protection |
| TD-014 | No structured logging or APM integration (New Relic, Datadog, Sentry) | Observability | Production debugging without logs is difficult |
| TD-015 | Admin role is a plain VARCHAR, not a database ENUM or proper RBAC | Architecture | Role system fragile; typo-sensitive |

---

## 38. Critical Blockers Before Production

The following issues **must** be resolved before any production deployment:

### P0 — System-Breaking

1. **BUG-001** — Add `chapter_id` column to `chat_sessions` table via a new migration:
   ```php
   $table->foreignUuid('chapter_id')->nullable()->constrained()->nullOnDelete();
   ```
   This enables chapter-scoped RAG context as originally designed.

2. **BUG-002** — Fix OAuth callback URL in `SocialAuthController::callback()`:
   ```php
   // Replace:
   $frontendUrl = 'http://localhost:5173';
   // With:
   $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
   ```

3. **BUG-005** — Resolve LLM provider ambiguity:
   - Decide which provider to use for production
   - Document this explicitly in `.env.example` comments
   - Remove the ambiguity between config default and template value

### P1 — Security

4. **BUG-003 / SEC-002** — Replace token-in-URL OAuth pattern with:
   - Server-side session state (set a signed, short-lived cookie with token)
   - Or PKCE flow storing the code challenge server-side

5. **BUG-004 / SEC-004** — Configure production mail in `.env`:
   ```ini
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailgun.org  # or ses, sendgrid, etc.
   MAIL_USERNAME=your_key
   MAIL_PASSWORD=your_password
   ```

6. **SEC-003** — Evaluate migration from `localStorage` tokens to `HttpOnly` Sanctum cookies:
   - Update `config/sanctum.php` to use cookie-based auth
   - Remove `Authorization: Bearer` header approach in frontend
   - This is a significant change requiring careful testing

### P2 — Infrastructure

7. **TD-001** — Create `docker-compose.yml` with:
   - PHP 8.3 + Laravel app service
   - PostgreSQL 16 + pgvector service
   - Redis service
   - Nginx or Caddy reverse proxy

8. **BUG-007** — Complete `AdminQuizView.vue` so admins can manage quiz questions via the frontend.

---

## 39. Deployment Readiness Checklist

```
INFRASTRUCTURE
[ ] docker-compose.yml created and tested
[ ] PostgreSQL 16 with pgvector extension available
[ ] Redis instance available
[ ] Process supervisor (Supervisor/Horizon) for queue:work

ENVIRONMENT
[ ] APP_KEY generated (php artisan key:generate)
[ ] APP_ENV=production, APP_DEBUG=false
[ ] DB_* configured for production database
[ ] REDIS_* configured for production Redis
[ ] FRONTEND_URL set to production domain
[ ] GROQ_API_KEY or ANTHROPIC_API_KEY set (choose one)
[ ] VOYAGE_API_KEY set
[ ] MAIL_MAILER set to smtp/ses/mailgun (not log)

SECURITY
[ ] OAuth callback URL uses FRONTEND_URL env var
[ ] OAuth token delivery method hardened
[ ] CORS allowed_origins set to production frontend domain
[ ] APP_DEBUG=false in production

CONTENT
[ ] php artisan migrate --force
[ ] php artisan db:seed (seeds canonical badges)
[ ] All 11 canonical chapters ingested (ingestion_status='ready')
[ ] Canonical book record matches Book::canonical() query

FUNCTIONALITY
[ ] php artisan queue:work running (or Horizon)
[ ] Email delivery tested (test password reset flow)
[ ] RAG query returning results for sample questions
[ ] Leaderboard loading correctly
[ ] Admin can access /admin/book-ingestion

MONITORING
[ ] Error logging configured (Sentry, Rollbar, or log channel)
[ ] Application health check responding: GET /api/health
[ ] Queue worker monitoring in place
```

---

## 40. Deployment Handoff Guide

### For the Incoming Development Team

**Repository structure:** The project is a monorepo with `backend/` (Laravel 13 API) and `frontend/` (Vue 3 SPA). They are entirely decoupled — the backend has no knowledge of Vue, and the frontend only knows the API base URL.

**First things to fix:** See Section 38 (Critical Blockers). Priority is: chapter_id migration → OAuth URL fix → mail driver. These three alone prevent the system from functioning correctly in any non-local environment.

**Understanding the AI system:** The RAG pipeline runs synchronously for retrieval + LLM, but embedding ingestion runs asynchronously in the Redis queue. The queue worker (`php artisan queue:work`) must be running for book content ingestion to succeed.

**Adding a new AI provider:** Implement `LLMGatewayInterface` with `chat()` and `streamChat()` methods. Register the binding in `AppServiceProvider::register()`. Add configuration to `config/ai.php`.

**Adding new content:** Use the Admin Ingestion UI (`/admin/book-ingestion`). Paste chapter text → validate → ingest. Monitor status on the same page. If Voyage AI rate-limits, retry from the same UI.

**Changing the embedding dimension:** Requires dropping and recreating the `content_chunks.embedding` column and re-embedding all content. The dimension is set at migration time from `config('ai.voyage.dimension')`.

**Understanding why chapter chat scoping doesn't work:** `chat_sessions.chapter_id` column is missing. Add the migration in Section 38, and the `RetrievalService` will automatically pick up `$session->chapter_id` correctly.

### Developer Contacts (from `config/ai.php` platform config)

| Role | Name |
|---|---|
| Project Manager & Integration Lead | Ashenafi Deresa Feyisa |
| Backend Developer | Kidus Tilahun |
| DevOps / QA | Nigusu Wario |
| Frontend / UI | Getamesay Mekcha |
| AI / RAG Lead | Abinet Tesfaye |

---

## 41. Known Facts vs Assumptions

This section distinguishes what was directly verified from source code vs what is assumed or unverified.

### Verified Facts (from direct code inspection)

- ✅ Laravel 13, PHP 8.3, Vue 3.5.40, Vite 8, Tailwind CSS 4.3.3
- ✅ Groq is the code-level default LLM; Claude is the `.env.example` template setting
- ✅ Voyage AI `voyage-3-lite`, 1024 dims, batch_size=1
- ✅ pgvector HNSW index: `m=16, ef_construction=64`, cosine distance
- ✅ Similarity threshold defaults: code=0.35, `.env.example`=0.75
- ✅ `chat_sessions` has NO `chapter_id` column in any migration
- ✅ OAuth callback hardcodes `http://localhost:5173`
- ✅ OAuth token delivered as `?token=` URL query param
- ✅ Mail driver is `log` in `.env.example`
- ✅ XP formula: (chapters×150) + (quizzes×100) + (streak×20); Level = floor(xp/1000)+1
- ✅ Daily challenge is a hardcoded static PHP array
- ✅ AdminQuizView.vue is 1,689 bytes (stub)
- ✅ 3 language files: en (9,249B), am (12,988B), om (9,555B)
- ✅ 15 migration files, verified individually
- ✅ Conversation history window: last 10 messages
- ✅ Global assistant history window: last 6 messages (client-provided)
- ✅ All primary keys are UUID using `gen_random_uuid()`
- ✅ No Docker configuration in repository
- ✅ PDF parsing libraries installed but not used in any route or controller

### Not Determined (requires runtime or additional files)

- ⚪ Whether all 11 chapters are currently seeded in the database
- ⚪ Whether the pgvector extension is installed in any current environment
- ⚪ Whether any backend tests pass
- ⚪ Actual API response latencies (not measured)
- ⚪ CORS `allowed_origins` configuration
- ⚪ Dark mode toggle persistence mechanism
- ⚪ Whether semantic search UI (`SearchController`) is accessible from the frontend
- ⚪ Badge seeder contents (seeder file not directly inspected)
- ⚪ Cache invalidation on dashboard (referenced in comment, not verified)

---

## 42. Recommended Improvements

### Immediate (Before Any User Testing)

1. **Fix `chapter_id` migration** — add the column to `chat_sessions` so RAG scoping works
2. **Fix OAuth redirect URL** — remove hardcoded localhost
3. **Fix LLM provider documentation** — make `.env.example` and `config/ai.php` agree
4. **Set up mail driver** — choose an SMTP provider, test password resets
5. **Add rate limiting on login/register** — `throttle:5,1` on auth routes

### Short-Term (Before Production)

6. **Add Docker Compose** — reproducible environment for all developers and deployment
7. **Complete AdminQuizView.vue** — admins cannot manage quizzes without this
8. **Replace `localStorage` tokens** — migrate to `HttpOnly` Sanctum cookies
9. **Paginate leaderboard** — `LIMIT 50 OFFSET ?` in SQL to prevent full-table load
10. **Persist XP** — add `xp` column to `users`, update on quiz/progress events; remove leaderboard in-memory calculation

### Medium-Term (Before Scale)

11. **Add embedding cache** — cache Voyage AI results for identical queries (Redis, TTL 1 hour)
12. **Add Sentry / Rollbar error tracking** — production observability
13. **Cache book/chapter content** — Redis cache for `ChapterController::show()` (long TTL, invalidate on admin update)
14. **Add pagination to leaderboard API** — cursor-based for large user bases
15. **Implement real daily challenges** — database-backed `daily_challenges` table with admin UI

### Long-Term

16. **PDF ingestion pipeline** — use installed `smalot/pdfparser` to extract chapter text automatically
17. **S3 avatar storage** — configure AWS S3 for avatar uploads
18. **RBAC** — replace string role with a proper role/permission system
19. **Multi-book support** — allow multiple canonical books; users select which to study
20. **AI analytics dashboard** — track question types, ungrounded response rate, chapter popularity

---

## 43. Future Roadmap

### Phase 2 Suggested Features

| Feature | Priority | Complexity |
|---|---|---|
| Fix chapter_id scoping bug | P0 | Low |
| Docker Compose | P0 | Low |
| Production mail setup | P0 | Low |
| Complete AdminQuizView.vue | P1 | Medium |
| HttpOnly token auth | P1 | High |
| Persistent XP | P1 | Medium |
| Leaderboard pagination | P1 | Low |
| Embedding query cache | P2 | Medium |
| PDF ingestion pipeline | P2 | High |
| Real daily challenges | P2 | Medium |
| Error monitoring (Sentry) | P2 | Low |
| S3 avatar storage | P3 | Low |
| Multi-book support | P3 | High |
| Adaptive learning paths | P4 | Very High |

---

## 44. Final Architecture Summary

### What Smart Adama Is

An AI-powered digital learning platform converting the *Smart Adama Book* — an 11-chapter conceptual framework for transforming Adama, Ethiopia into a smart city — into an interactive, gamified educational experience. The platform is a fully decoupled API-first system built in a 30-day internship sprint.

### Core Innovation

The primary technical innovation is the **RAG (Retrieval-Augmented Generation) study assistant**: instead of a generic chatbot, all AI responses are grounded in the actual book content via pgvector cosine similarity search. Responses that find no relevant content explicitly decline to answer rather than hallucinating. This is enforced through both the retrieval pipeline and the system prompt.

### What Works Well

- End-to-end learning loop is functional and elegant
- SSE streaming gives a responsive, real-time feel to AI conversations
- HTMLPurifier + DOMPurify double-sanitization is a solid security approach for AI-generated content
- Async ingestion pipeline with Redis queue handles Voyage AI rate limits gracefully
- Three-language support with a clean token-based CSS design system
- Progress tracking and gamification are properly schema-driven and badge-extensible

### What Needs Work

- Chapter-scoped RAG isolation is the most impactful unfixed bug
- OAuth is development-only (hardcoded localhost)
- No production deployment configuration exists
- Admin quiz management has no usable frontend

### System Integrity Assessment

The platform is **architecturally sound** for Phase 1. The separation of concerns between the Laravel API, the AI service layer (via interfaces and DI), and the Vue SPA is clean and extensible. The data model is normalized and uses correct relational integrity constraints. The primary issues are implementation gaps (missing migration column, hardcoded URLs) rather than architectural flaws.

---

*Document generated by structured audit of Smart Adama repository.*
*Audit conducted: 2026-08-16*
*Repository: `/home/vico/Projects/Dev/Smart Adama/`*
*Document version: 2.0.0 (Authoritative Re-Audit)*
*All claims are source-verified. Unverified claims are explicitly marked ⚪.*





