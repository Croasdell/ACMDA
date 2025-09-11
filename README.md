# ACMDA
AI – Customer Messaging &amp; Developer Assistan ACMDA
## 🔑 Key Components

### 1. **AI Core (Dolphin)**

* Models: `dolphin-mistral-dev`, `dolphin-mistral`, `dolphin-phi`, `mistral`, `codellama:7b-instruct`.
* Runs via CLI (`dolphin` command).
* Connected to:

  * **Memory system**: SQLite `memory.sqlite` for persistent conversation history (`mem.php`).
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

* AI needs structured text (local `services.txt` or DB table) with:

  * What services Ian offers (assembly, doors, locks, tiling, plumbing repairs, etc.).
  * What services Ian **does not** offer.
  * Pricing / availability policy (refer to booking system, don’t book directly).
* This is indexed into the RAG system so Dolphin always replies **on-brand and accurate**.

### 4. **Networking & Security**

* Server IP: `192.168.1.142` (fixed via router DHCP reservation).
* SSH locked to LAN + key authentication.
* UFW firewall in place.
* All AI + WhatsApp processing **stays local**.

---

## 🚀 Next Steps (Build Plan)

1. **Finalize memory integration**

   * Fix `mem.php` → now working, Dolphin remembers “Ian” and conversations.
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
