<<<<<<< HEAD
# ACMDA — AI Customer Messaging & Dev Assistant

**Repo:** [https://github.com/Croasdell/ACMDA](https://github.com/Croasdell/ACMDA)
**Goal:** A small, self-hosted system that:

* answers customer WhatsApp messages with safe, on-brand drafts (for you to review/approve), and
* gives you a local **Dev Assistant** (CLI) that can remember context, read your local docs, and optionally pull small web snippets.

No SaaS LLMs required; runs on your PC with Ollama-style local models (e.g., `mistral`, `dolphin-mistral`).


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


## Architecture (at a glance)

* **Models (local):** `mistral`, `dolphin-mistral(-dev)`, others via Ollama-style `/api/generate`.
# ACMDA — AI Customer Messaging & Developer Assistant

ACMDA is a lightweight, self-hosted assistant for Handyman Plus Van. It provides:

- A local Dev Assistant (CLI) that can remember context and consult local docs (optional RAG).
- A WhatsApp message pipeline that generates draft replies for human review (review-before-send).

Repo layout notes

- The active web code lives in `public_html/` (web endpoints, frontend, and `mem.php`).
- The long-term `app/` layout described in earlier notes is a target structure; some components live in `public_html/` today.

Quick goals

- Answer customer queries with safe, on-brand drafts (human review required).
- Provide a local developer assistant for code and documentation help.

Quick start (local)

1. Serve the `public_html/` folder locally:

```bash
cd public_html
php -S 127.0.0.1:8000
```

2. Edit `services.txt` to reflect your current offerings and business rules (this file is now canonical).

3. Use `chat_endpoint.php` / `chat.js` for a local chat demo; use `wa_webhook.php` to accept WhatsApp webhooks.

What I found

- `public_html/` contains web endpoints (`wa_webhook.php`, `wa_send.php`, `wa_approve.php`), a `mem.php`, and a frontend — good starting point.
- `services.txt` has been canonicalized; the file `services.txt` is now the single source of truth for service facts.
- There is no complete `app/` directory yet; consider migrating core logic into `app/` for clearer separation.

Suggested next steps (prioritised)

1. Add a `.env.example` listing required env vars (`WA_TOKEN`, `WA_PHONE_ID`, `WA_VERIFY_TOKEN`, `LLM_ENDPOINT`, `LLM_MODEL`, `APP_ADMIN_USER`, `APP_ADMIN_PASS`).
2. Create an `install_deps.sh` documenting required packages (`php`, `php-sqlite3`, `curl`, `lynx`/`html2text`).
3. Add a small `public_html/health.php` endpoint that checks DB and model connectivity.
4. Add a simple test script to exercise the WhatsApp flow locally (dry run mode).
5. Optionally implement `app/` structure and move core logic from `public_html/` into `app/`.

Security notes

- Keep tokens in `.env.local` (gitignored). Do not commit secrets.
- Protect admin pages with HTTP basic auth or stronger access control.
- Enforce “no bookings/payments” in system prompts and code paths.

Contribution

If you want I can next:

- Create `.env.example` and `install_deps.sh` and push them.
- Add `public_html/health.php` and a simple test harness for the WA flow.

Status

I have resolved the `services.txt` conflict and updated this `README.md`. Changes are ready to be pushed.
