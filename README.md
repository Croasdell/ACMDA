# ACMDA — AI Customer Messaging & Dev Assistant

**Repo:** [https://github.com/Croasdell/ACMDA](https://github.com/Croasdell/ACMDA)
**Goal:** A small, self-hosted system that:

* answers customer WhatsApp messages with safe, on-brand drafts (for you to review/approve), and
* gives you a local **Dev Assistant** (CLI) that can remember context, read your local docs, and optionally pull small web snippets.

No SaaS LLMs required; runs on your PC with Ollama-style local models (e.g., `mistral`, `dolphin-mistral`).

---

## What ACMDA does

### 1) Dev Assistant (CLI: `dolphin`)

* Talk to a local model with **UK time injected**, **persistent memory**, and optional **RAG** over your PDFs/TXTs.
* Quick “web:” fetch: paste a URL; it pulls text (offline friendly—can be disabled).
* Great for drafting replies, code help, or pulling snippets from your docs.

### 2) WhatsApp flow (review-before-send)

1. Customer sends a WhatsApp message → webhook stores it.
2. AI generates a **draft** reply using your services rules (no auto-booking).
3. You review/edit/approve in a tiny admin page.
4. A sender script posts the approved reply to WhatsApp Cloud API.
5. (Optional) Delay/24-hour rule before sending.

**Safety stance:** WA bot is **offline + booking-only disabled** by default. It shares info, asks clarifying questions, and links to booking—not taking payments or final bookings in chat.

---

## Architecture (at a glance)

* **Models (local):** `mistral`, `dolphin-mistral(-dev)`, others via Ollama-style `/api/generate`.
* **Core:** `llm.php` wraps the model call; composes the system prompt + memory + optional context.
* **Memory:** SQLite (disk) via `mem.php`. Namespaced by “who” (e.g., `dolphin-cli`, `wa-bot:+447…`) so CLI and WA are isolated.
* **RAG (optional):** Index `dev_docs/` into text chunks; simple retrieval returns top chunks as context.
* **Web fetch (optional):** Small shell helper to fetch and strip a page into plain text.
* **WhatsApp:** Webhook receiver → store inbound → generate draft → admin approve → sender posts to Cloud API.

---

## File/Folder structure (target)

```
ACMDA/
├─ app/
│  ├─ config.dolphin.php         # LLM endpoint/model, business name, rules (CLI)
│  ├─ config.whatsapp.php        # WA tokens, phone id, verify token, business rules
│  ├─ llm.php                    # llm_answer($userMsg, $context='', $who='...')
│  ├─ mem.php                    # SQLite memory: save/history/clear/context
│  ├─ services.php               # Structured “what we do / don’t” facts for drafts
│  ├─ rag.php                    # (optional) simple doc retrieval for local PDFs/TXTs
│  ├─ web.php                    # (optional) wrapper around web_fetch.sh
│  ├─ wa_db.php                  # SQLite for WhatsApp message states
│  ├─ wa_send.php                # Sends approved drafts to WhatsApp Cloud API
│  ├─ wa_approve.php             # Minimal admin UI to review/edit/approve drafts
│  └─ util.php                   # log_info(), log_err(), helpers
│
├─ public/
│  ├─ index.php                  # Dashboard (filter pending/approved/sent)
│  ├─ wa_webhook.php             # Meta webhook (GET verify + POST inbound)
│  └─ health.php                 # Health check: DB + model ping + versions
│
├─ scripts/
│  ├─ dev_index_folder.sh        # Build plain-text chunks from dev_docs/
│  ├─ web_fetch.sh               # Fetch and strip a URL to text (optional)
│  ├─ wa_send_cron.sh            # Cron entry point for wa_send
│  ├─ install_deps.sh            # php-sqlite3, curl, lynx/html2text, etc.
│  └─ dev_up.sh                  # start local server, tail logs
│
├─ dev_docs/                     # Your PDFs/TXTs (PHP refs, service notes, etc.)
├─ data/
│  ├─ memory.sqlite              # Chat memory (persisted)
│  └─ wa.sqlite                  # WhatsApp message queue/state
│
├─ tests/
│  ├─ MemTest.php
│  ├─ LlmPromptTest.php
│  └─ WaFlowTest.php
│
├─ dolphin_cli.php               # Tiny CLI loop that calls llm_answer()
├─ .env.local                    # (gitignored) local secrets/overrides
├─ .gitignore                    # ignore sqlite, logs, .env.local
└─ README.md                     # you are here
```

> **Already done (based on our work so far):**
>
> * `app/mem.php` (SQLite memory) ✅
> * `app/llm.php` (time injection + prompt + saves both sides) ✅
> * `dolphin_cli.php` (CLI) ✅
> * Basic WhatsApp config & webhook scaffolding ✅
> * Verified tokens and model list on your machine ✅
>
> **Still to finish (the build plan):**
>
> * `services.php` with your **business facts** (clear, concise, no-booking).
> * `wa_db.php` + `wa_send.php` + `wa_approve.php` + minimal `public/index.php`.
> * Optional **RAG** pipeline for `dev_docs/` + simple retrieval.
> * Optional `web_fetch.sh`/`web.php` if you want “web:” context.
> * Tests, health check, and cron/systemd wiring.

---

## Data model

### Memory (SQLite: `data/memory.sqlite`)

```
chat_memory(
  id INTEGER PK,
  who TEXT,              -- namespace (e.g., 'dolphin-cli', 'wa-bot:+447…')
  role TEXT,             -- 'user' | 'assistant'
  content TEXT,
  created_at INTEGER
)
INDEX: idx_chat_memory_who (who)
```

### WhatsApp (SQLite: `data/wa.sqlite`)

```
wa_messages(
  id INTEGER PK,
  from_msisdn TEXT,
  body TEXT,               -- inbound text
  draft TEXT,              -- AI-generated reply
  state TEXT,              -- 'pending' | 'approved' | 'sent'
  not_before INTEGER,      -- optional delay/24h rule
  created_at INTEGER,
  sent_at INTEGER,
  wa_msg_id TEXT           -- Cloud API message id
)
```

---

## Key flows

### A) CLI (Dev Assistant)

1. User types in terminal → `dolphin_cli.php`
2. `llm.php` builds prompt: system rules + UK time + last N lines from `mem.php` + optional context.
3. Call local model (`/api/generate`).
4. Save both sides to `chat_memory` (namespace `dolphin-cli`).

### B) WhatsApp (review before send)

1. Webhook receives inbound message, stores `wa_messages(state='pending')`.
2. Generate **draft** with `llm_answer($userMsg, services_context(), 'wa-bot:+447…')`.
3. Admin page lists pending → you edit/approve.
4. `wa_send.php` pushes approved to Cloud API, marks `sent`.

---

## Environment variables / constants

**LLM / CLI**

* `LLM_ENDPOINT` (e.g., `http://127.0.0.1:11434/api/generate`)
* `LLM_MODEL` (e.g., `mistral`)
* `BUSINESS_NAME`
* `SYSTEM_RULES` (multi-line: no booking, use services facts, ask brief clarifiers, etc.)

**WhatsApp**

* `WA_TOKEN`
* `WA_PHONE_ID` (numeric)
* `WA_VERIFY_TOKEN` (for webhook GET validation)

**Admin**

* `APP_ADMIN_USER`, `APP_ADMIN_PASS` (HTTP basic for `/public` admin pages)

---

## Quick start (local CLI)

```bash
# models already installed via Ollama
php dolphin_cli.php
# talk; memory persists in data/memory.sqlite
```

## Quick start (WhatsApp)

1. Expose `public/wa_webhook.php` (HTTPS).
2. Set webhook in Meta app; verify with `WA_VERIFY_TOKEN`.
3. Send a message to your WA business number.
4. Open admin page → review draft → approve → cron runs `wa_send.php`.

---

## Security & safety

* WA bot is **info-only** (no bookings/payments).
* Services facts are centralized in `services.php`; the prompt forbids making up prices or live browsing.
* Basic auth on admin pages; tokens kept in `.env.local` (gitignored).
* Web fetcher is optional and constrained (https, small size).

---

## Roadmap (build order)

1. **Finish WA queue**: `wa_db.php`, `wa_send.php`, `wa_approve.php`, `public/index.php`.
2. **services.php**: encode what you do/don’t do, areas, hours, warranty, exclusions.
3. **RAG** (optional): index `dev_docs/`, wire `rag.php` into CLI `dolphin` as `RAG:` mode.
4. **Health & tests**: `public/health.php`, basic PHPUnit tests.
5. **Ops**: cron for sender, systemd/nginx, logs & rotation.

---

# Data (enter later?)

No actual `data/` or `app/` directories yet. Current repo is a barebones POC.

