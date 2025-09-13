# ACMDA
AI – Customer Messaging & Developer Assistant (ACMDA)

## 🔑 Key Components

### 1. **AI Core (Dolphin)**
* Models: `dolphin-mistral-dev`, `dolphin-mistral`, `dolphin-phi`, `mistral`, `codellama:7b-instruct`.
* Runs via CLI (`dolphin` command).
* Connected to:
  * **Memory system**: SQLite `acmda.sqlite` for persistent conversation history, managed by `acmda.php`.
  * **RAG system**: `dev_docs` folder indexed with `dev_index_folder.sh` for external PDFs, txt, PHP docs, etc.
  * **Optional web fetcher**: `web_fetch.sh` to grab online docs and index them.

### 2. **WhatsApp Integration**

* Uses Meta API with:
  * `wa_webhook.php` → receives inbound messages (callback URL).
  * `wa_approve.php` → admin review interface for drafts.
  * `wa_send.php` → cron job that sends approved messages.
* Messages stored in SQLite table `wa_messages` with states:
  * `pending → approved/rejected → sent`.
* **Flow**:
  1. Customer sends WhatsApp message.
  2. Stored in DB, AI drafts reply.
  3. Ian reviews reply via dashboard.
  4. Once approved, cron job sends it back to WhatsApp API.

### 3. **Business Knowledge (Services File)**

* AI needs structured text (local `services.txt` in the project root) with:
  * What services Ian offers (carpentry, plumbing, damp proofing, flat roofing, painting, kitchen fittings, bathroom installations, tiling, refurbishments, hanging mirrors & TVs, fencing, window mechanism repairs, changing locks).
  * What services Ian **does not** offer (carpet fitting, electrical rewiring).
  * Pricing / availability policy (please use the online booking system for prices and availability).
* `services.txt` is indexed into the RAG system so Dolphin always replies **on-brand and accurate**.

### Usage

Place `acmda.php` and `wa_webhook.php` in the project root on the machine running the AI.

* `acmda.php` embeds service definitions, manages long-term memory, and creates `acmda.sqlite` for the WhatsApp message pipeline. Use commands like `php acmda.php receive <user> <message>` to interact with the system.
* `wa_webhook.php` handles Meta's webhook callbacks. Set `WA_VERIFY_TOKEN` and point the callback URL at this script to store inbound messages.

### 4. **Networking & Security**

* Server IP: `192.168.1.142` (fixed via router DHCP reservation).
* SSH locked to LAN + key authentication.
* UFW firewall in place.
* All AI + WhatsApp processing **stays local**.

---

## 🚀 Next Steps (Build Plan)

1. **Finalize memory integration**
   * Verify `acmda.php` stores and recalls conversations so Dolphin remembers "Ian" and chats.
   * Extend memory to include **business context** (services, FAQs).

2. **Complete WhatsApp pipeline**
   * Finish `wa_messages` DB table migration.
   * Ensure `wa_webhook.php` is Meta-verified (correct callback URL + verify token).
   * Test full flow with sandbox WhatsApp number.

3. **Train Dolphin with business data**
   * Copy website service descriptions into `services.txt`.
   * Index with `dev_index_folder.sh`.
   * Test Dolphin’s answers to customer questions (e.g., “Do you fit carpets?”).

4. **Set up approval dashboard**
   * Simple PHP web interface to review/rewrite AI replies.
   * Add “Approve”, “Reject”, “Regenerate” buttons.
   * Replies only leave server once **Ian clicks Approve**.

5. **Enable developer helper mode**
   * Keep Dolphin accessible for coding help.
   * RAG system pointed at PHP docs, Ian’s dev notes, and local projects.

6. **Optional: Add web research mode**
   * Allow Dolphin (dev mode only) to fetch + index external docs.
   * Keep **customer mode offline** for privacy and reliability.

## Running Tests

Execute the PHPUnit test suite to verify message handling logic:

```
./vendor/bin/phpunit
```
