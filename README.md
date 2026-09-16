
# 🏙️ Smart Adama

### AI-Powered Digital Learning Ecosystem for Adama City, Ethiopia

*Bridging municipal smart-city planning with accessible, AI-driven higher education.*

[![Laravel](https://img.shields.io/badge/Laravel-13.23.0-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3.5.41-4FC08D?style=flat-square&logo=vue.js)](https://vuejs.org)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql)](https://www.postgresql.org)
[![pgvector](https://img.shields.io/badge/pgvector-HNSW-6E56CF?style=flat-square)](https://github.com/pgvector/pgvector)
[![TypeScript](https://img.shields.io/badge/TypeScript-7.0.2-3178C6?style=flat-square&logo=typescript)](https://www.typescriptlang.org)
[![License](https://img.shields.io/badge/status-production-success?style=flat-square)]()

[Live Demo](#) · [API Docs](#-api-documentation) · [Architecture](#-system-architecture) · [Report Bug](#)

</div>

---

## 📖 Overview

**Smart Adama** digitizes the 11-chapter *Smart Adama* municipal transformation manuscript into an interactive, AI-tutored learning platform. It is built **API-first**: a decoupled **Vue 3** single-page application talks exclusively over versioned JSON REST and Server-Sent Events to a **Laravel 13** backend, which in turn runs a hybrid **Retrieval-Augmented Generation (RAG)** pipeline to keep every AI answer grounded in the source text — with citations, not hallucinations.

Built for students, urban planners, municipal administrators, and citizens of Adama City — in **English, Amharic, and Afaan Oromoo**.

| | |
|---|---|
| 🎯 **Purpose** | Turn a static municipal PDF into an interactive, gamified learning experience |
| 🧠 **AI Core** | Hybrid semantic + lexical RAG (RRF, k=60) over 1024-dim vectors |
| 🌍 **Audience** | Students, urban planners, municipal staff, citizens |
| 🗣️ **Languages** | English · Amharic (አማርኛ) · Afaan Oromoo |
| 🏛️ **Deployment** | Vercel (frontend) · Render (backend) · Supabase (data) |

---

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/1ae1a9dc-c90d-413d-a41a-8ca85de39963" />


## ✨ Key Features

- **📚 Digital Book & Chapter Reader** — non-linear navigation, markdown rendering, live reading-progress sync (0–100%)
- **🤖 Chapter-Aware Study AI** — hybrid RRF retrieval with a `+0.35` boost for the active chapter, streamed via SSE, every answer footnoted with real citations
- **💬 Global AI Assistant** — floating, platform-wide concierge for navigation and general smart-city questions (non-RAG, rate-limited)
- **📝 Quiz Engine** — single/multiple-choice + true/false, graded server-side, 70% passing threshold
- **🏆 Gamification** — 8 badges, daily streaks, non-linear chapter completion tracking
- **🛠️ Content Authoring Studio** — visual hierarchy editor for books → chapters → sections → subsections
- **📥 Ingestion Pipeline** — PDF → chunks (~700 tokens, 15% overlap) → embeddings, with pause/resume/retry and live SSE progress
- **📊 Admin Diagnostics** — live CPU/memory/Redis/pgvector health, plus a RAG debug console exposing raw semantic vs. lexical scores

---
<img width="1086" height="1448" alt="image" src="https://github.com/user-attachments/assets/ff55a277-59ee-450a-8c7c-c31754822b1c" />

## 🏗️ System Architecture

Strictly decoupled — no server-side rendering, no monolithic coupling. All communication is versioned JSON REST or SSE.

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│   Vue 3 SPA · Vite · TypeScript · Pinia · Tailwind CSS 4     │
│                   Hosted on Vercel Edge Network              │
└──────────────────────────┬────────────────────────────────────┘
                            │ HTTPS · JSON REST + SSE
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                        API GATEWAY                            │
│   Laravel 13.23.0 (PHP 8.3.6) · /api/v1 · Sanctum/JWT guard   │
│                   Hosted on Render (Docker)                   │
└───────────┬───────────────────┬────────────────────┬──────────┘
            ▼                   ▼                    ▼
   ┌────────────────┐  ┌─────────────────┐  ┌───────────────────┐
   │  DATA STORAGE   │  │  CACHE & QUEUE  │  │    AI SERVICES     │
   │ Supabase        │  │ Redis (Predis)  │  │ LLM: Gemini 3.5     │
   │ PostgreSQL 16   │  │ progress cache  │  │   Flash-Lite →      │
   │ + pgvector      │  │ rate limiters   │  │   Groq (fallback)   │
   │ + tsvector GIN  │  │ async jobs      │  │ Embeddings: Voyage  │
   │ + Storage       │  │                 │  │   AI (1024-dim)     │
   └────────────────┘  └─────────────────┘  └───────────────────┘
```

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/317a7a8c-1790-4848-96d4-f547b2bc5c0a" />

**Why it's grounded, not guessing:** every study-AI response is built from a hybrid retrieval pass — **dense semantic search** (Voyage AI embeddings + pgvector HNSW cosine index) fused with **lexical full-text search** (PostgreSQL `tsvector` + GIN) via **Reciprocal Rank Fusion** (`k=60`), with a chapter-relevance boost. The LLM is only ever allowed to answer from what's retrieved.

```sql
rrf_score = (chapter_match ? 0.35 : 0)
          + (1.0 / (60 + semantic_rank))
          + (1.0 / (60 + lexical_rank))
```

---

## 🧰 Tech Stack

<table>
<tr><td><b>Backend</b></td><td>Laravel 13.23.0 · PHP 8.3.6 · PostgreSQL 16 + pgvector · Redis · Apache</td></tr>
<tr><td><b>Frontend</b></td><td>Vue 3.5.41 (Composition API) · Vite 8.2.2 · TypeScript 7.0.2 · Tailwind CSS 4.3.3 · Pinia · Vue Router · Vue I18n</td></tr>
<tr><td><b>AI / LLM</b></td><td>Google Gemini 3.5 Flash-Lite (primary) → Groq Qwen 3.8-27b (fallback) · optional local Ollama</td></tr>
<tr><td><b>Embeddings</b></td><td>Voyage AI <code>voyage-large-2-instruct</code> (1024-dim, HNSW cosine index)</td></tr>
<tr><td><b>Auth</b></td><td>Supabase Auth (SMS OTP / Email / OAuth / Guest) bridged into Laravel Sanctum via custom JWT interceptor</td></tr>
<tr><td><b>Infra</b></td><td>Vercel (frontend) · Render + Docker (backend) · Supabase (Postgres, Auth, Storage)</td></tr>
</table>

---

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/4e3f5bc3-b7ba-4fe3-bd0e-ed88e2c8710c" />


## 🚀 Getting Started

### Prerequisites
`PHP 8.3+` · `Composer 2.x` · `Node.js 20+` · `PostgreSQL 16` with `pgvector` · `Redis`

### Installation

```bash
# 1. Clone
git clone https://github.com/smart-adama-group-2/Smart-Adama.git smart-adama
cd smart-adama

# 2. Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# → set DB_HOST, DB_PASSWORD, GEMINI_API_KEY, VOYAGE_API_KEY in .env
php artisan migrate --seed
php artisan serve --port=8000

# 3. Frontend (new terminal)
cd ../frontend
npm install
cp .env.example .env
# → ensure VITE_API_BASE_URL=http://localhost:8000
npm run dev -- --port=5173
```

### Key Environment Variables

| Variable | Purpose |
|---|---|
| `DB_CONNECTION` | Must be `pgsql` |
| `AI_LLM_PROVIDER` | `gemini` \| `groq` \| `ollama` |
| `AI_EMBEDDING_PROVIDER` | `voyage` \| `gemini` \| `ollama` |
| `VOYAGE_EMBEDDING_DIMENSION` | Enforced at `1024` |
| `RAG_TOP_K` / `RAG_SIMILARITY_THRESHOLD` | Retrieval tuning (default `5` / `0.75`) |
| `VITE_SUPABASE_URL` / `VITE_SUPABASE_ANON_KEY` | Supabase client auth |

Full variable reference lives in `docs/` — no secrets are committed to this repository.

---

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/bfed13ac-c049-4041-81ba-19aa6c6ebe2f" />


## 📡 API Documentation

**107 versioned endpoints** under `/api/v1/`. A few highlights:

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/auth/login` | Public | Rate-limited login (5 attempts / 10 min) |
| `GET` | `/chapters/{chapter}` | Sanctum | Chapter content, section tree, progress |
| `POST` | `/chat/sessions/{session}/messages` | Sanctum | Streams grounded RAG answer via SSE |
| `POST` | `/quizzes/{quiz}/attempts/{attempt}/submit` | Sanctum | Server-side graded submission |
| `GET` | `/users/me/badges` | Sanctum | Earned + locked badges |
| `GET` | `/admin/rag/debug-search` | Admin | Raw cosine/lexical/RRF scores for a query |
| `GET/PUT` | `/admin/settings` | Admin | Runtime LLM/embedding provider config |

See [`docs/API.md`](docs/API.md) for the complete route reference.

---

## 🗄️ Data Model Highlights

- **24 Eloquent models**, **34 sequential migrations**, all primary keys UUID (`gen_random_uuid()`)
- `content_chunks.embedding vector(1024)` with an **HNSW cosine index** for sub-100ms ANN lookups
- `content_chunks.search_vector` — a generated `tsvector` column with a **GIN index** for lexical search
- Strict ownership checks across `chat_sessions`, `chat_messages`, and `quiz_attempts` (no IDOR)

---

## 🎮 Gamification

| Badge | Icon | Criteria |
|---|---|---|
| First Step | 📖 | Complete 1 chapter |
| Committed Learner | 🎯 | Complete 5 chapters |
| Perfectionist | 💯 | Score 100% on any quiz |
| On a Roll | 🔥 | 3-day streak |
| Week Warrior | ⚡ | 7-day streak |
| Unstoppable | 🏆 | 30-day streak |
| Smart Adama Master | 🌟 | Complete the full book |
| Quiz Ace | 🎓 | Pass 10 quizzes |

---

## 📚 The 11 Canonical Chapters

`Introduction` · `Smart Governance` · `Digital Adama` · `Smart Security` · `Smart Urban Design & Land Use` · `Smart Environment & Organic Production` · `Smart Mobility` · `Smart Social Services` · `Smart Tourism & Culture` · `Smart PR, Research & Knowledge Management` · `Smart People`

---


<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/b12af6c0-8b76-4118-90a7-6b5c72003bb7" />


## 🔐 Security

- Dual-layer auth: Supabase JWT (RS256) ↔ Laravel Sanctum bridge with auto-provisioning
- Anonymous sessions are **hard-locked** to `role = 'learner'` — no privilege escalation path
- Master-admin override restricted to a single whitelisted account
- All input validated via Laravel `FormRequest` classes + DOMPurify on the client
- Eloquent ORM / parameterized queries throughout — no raw SQL injection surface

---

## 📱 Responsive & Mobile

Mobile-first with swipe-dismiss chat drawers, shrink-wrapped assistant panels, and a dedicated bottom navigation bar for Home / Reader / Quizzes / Profile.

---

## 🗺️ Roadmap

- [ ] Native multilingual (Amharic / Afaan Oromoo) vector embeddings
- [ ] Voice input + text-to-speech narration
- [ ] Native mobile packaging (Capacitor)
- [ ] Multi-document ingestion for supplementary city-planning whitepapers

---

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/85bdc77a-5d90-4439-8cfc-322fd79b58f6" />


## 👥 Team

| Name | Role |
|---|---|
| **Ashenafi Deresa Feyisa** | Project Manager & Integration Lead |
| Kidus Tilahun | Backend Engineer |
| Nigusu Wario | DevOps & QA Engineer |
| Getamesay Mekcha | Frontend & UI Engineer |
| Abinet Tesfaye | AI & RAG Lead |

---

## 📄 License

This project is developed as part of a university engineering internship in partnership with Adama City administration. See [`LICENSE`](LICENSE) for details.

<div align="center">

**Built with ❤️ for Adama City**

</div>

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/ab30b421-59b8-4ff1-b71a-259dc204d2d8" />

