# ACMDA

**AI Customer Messaging and Developer Assistant**

ACMDA is an early-stage, self-hosted assistant for small service businesses. It
combines a customer-facing website, a review-before-send WhatsApp workflow, and
local business memory. The current reference content is configured for
Handyman Plus Van.

> **Prototype status:** ACMDA is not ready for unattended production use.
> Message delivery is simulated, the administration screen needs authentication,
> and deployment hardening remains outstanding.

## What is implemented

- A PHP website and browser chat interface.
- A SQLite-backed inbound-message and draft queue.
- Deterministic draft generation from approved business facts.
- Human approve, reject, and regenerate states.
- A webhook verification and inbound-message handler.
- A CLI sender simulation for approved messages.
- Namespaced SQLite conversation memory.
- A Python bridge and basic PHP regression tests.

ACMDA does not currently send live WhatsApp messages or autonomously accept
bookings or payments.

## Workflow

```text
Customer message
      |
      v
Webhook validation -> draft generated from business rules
                              |
                              v
                    human review required
                       |             |
                    approve       reject
                       |
                       v
                 sender simulation
```

## Repository layout

```text
public_html/       active PHP website and web endpoints
website/           standalone static website variant
mem.php            shared SQLite memory helpers
acmda_bridge.py    Python bridge for local integrations
tests/             lightweight PHP regression tests
services.txt       canonical business-service guidance
```

Some root-level WhatsApp scripts are integration prototypes. The active web
counterparts live in `public_html/`.

## Local setup

Requirements:

- PHP 8.0 or later with PDO SQLite
- Python 3.10 or later for the optional bridge
- A local web browser

Start the PHP development server:

```bash
cd public_html
php -S 127.0.0.1:8000
```

Then open `http://127.0.0.1:8000/`.

The browser chat endpoint also expects an OpenAI API configuration in
`public_html/chat_config.php`. Do not commit credentials. The deterministic
WhatsApp workflow can be tested without enabling that endpoint.

## Command-line workflow

From `public_html/`:

```bash
php acmda.php receive customer "Can you help with a door?"
php acmda.php approve 1
php acmda.php send
php acmda.php memory customer
```

The send command currently prints the approved draft and updates its state; it
does not contact the WhatsApp Cloud API.

## Tests

Run PHP syntax checks and the same in-memory workflow smoke test used by CI:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php -r '
require "public_html/acmda.php";
$db = initDb(":memory:");
saveBusinessData($db, defaultServices());
$id = receiveMessage($db, "test", "Need tiling");
exit($id > 0 ? 0 : 1);
'
```

The `tests/` directory contains PHPUnit test cases, but the repository does not
yet include Composer/PHPUnit configuration to run them locally. Adding that
harness is an outstanding task.

## Security before deployment

- Put secrets in environment variables or a secret manager.
- Replace the development webhook token fallback.
- Add authentication, authorization, and CSRF protection to review actions.
- Verify Meta webhook signatures, not only the verification token.
- Add rate limits, structured logs, retention rules, and encrypted backups.
- Keep outbound sending disabled until end-to-end tests pass.
- Complete a privacy review before storing real customer conversations.

## Roadmap

1. Consolidate duplicate prototype entry points.
2. Add authenticated administration and CSRF protection.
3. Implement verified WhatsApp Cloud API delivery behind an explicit feature
   flag.
4. Add integration tests around webhook signatures and message-state changes.
5. Package configuration and deployment health checks.

## License

No licence file is currently included. Until one is added, normal copyright
restrictions apply.
