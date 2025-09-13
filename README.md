# ACMDA

All-in-one AI Customer Messaging & Developer Assistant (ACMDA).

This PHP project provides:
- SQLite-backed memory storage for conversational context.
- Rule-based drafting of replies for customer messages.
- A WhatsApp message pipeline with pending/approved/sent states.
- Persistent business context (services offered, excluded, policy) stored in SQLite.

## Usage

```
php acmda.php [command]
```

Commands:
- `receive <sender> <message>`: store a customer message and draft a reply.
- `approve <id>`: mark a drafted message as approved.
- `reject <id>`: mark a drafted message as rejected.
- `send`: output and mark all approved messages as sent.
- `memory <user>`: show stored conversation history for a user.

## Testing

Install dependencies and run the tests:

```
composer install
./vendor/bin/phpunit
```
