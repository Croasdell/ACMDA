#!/usr/bin/env python3
"""Bridge WhatsApp webhook with ACMDA and host environment.

This helper polls a Hostinger webhook for inbound WhatsApp messages,
forwards them to the local ACMDA service, and can notify Telegram when
approved replies are dispatched. It keeps all processing local while
demonstrating how ACMDA integrates with external platforms.

Configuration is done via environment variables:
  ACMDA_API_URL    -> endpoint for local ACMDA (default http://localhost/acmda.php)
  ACMDA_API_KEY    -> key for authenticating with ACMDA
  HOSTINGER_WH_URL -> webhook URL on Hostinger to poll/send messages
  TELEGRAM_TOKEN   -> bot token for notifications (optional)
  TELEGRAM_CHAT_ID -> chat ID to receive notifications (optional)
  ACMDA_BRIDGE_LOOP -> set to any value to enable polling loop
  ACMDA_BRIDGE_DEMO -> set to run a single demo message
"""

import json
import logging
import os
import time
from typing import Dict, List

import requests
from requests import RequestException

try:  # pragma: no cover - optional dependency
    import pywhat3k  # type: ignore  # noqa: F401
except Exception:  # pragma: no cover
    pass

try:  # pragma: no cover - optional dependency
    from telegram import Bot  # type: ignore
except Exception:  # pragma: no cover
    Bot = None  # type: ignore

logging.basicConfig(level=logging.INFO)
log = logging.getLogger(__name__)

ACMDA_API_URL = os.getenv("ACMDA_API_URL", "http://localhost/acmda.php")
ACMDA_API_KEY = os.getenv("ACMDA_API_KEY", "test-key")
HOSTINGER_WH_URL = os.getenv("HOSTINGER_WH_URL", "http://localhost/webhook")
TELEGRAM_TOKEN = os.getenv("TELEGRAM_TOKEN")
TELEGRAM_CHAT_ID = os.getenv("TELEGRAM_CHAT_ID")


def fetch_messages() -> List[Dict[str, str]]:
    """Poll Hostinger webhook for new messages."""
    try:
        resp = requests.get(HOSTINGER_WH_URL, timeout=10)
        resp.raise_for_status()
        data = resp.json()
        if isinstance(data, list):
            return data
        log.warning("Unexpected webhook payload: %s", data)
    except RequestException as exc:
        log.error("Fetching messages failed: %s", exc)
    except ValueError as exc:
        log.error("Invalid JSON from webhook: %s", exc)
    return []


def handle_incoming(payload: Dict[str, str]) -> Dict[str, str]:
    """Forward inbound message to ACMDA and return its draft reply."""
    sender = payload.get("from", "")
    text = payload.get("text", "")
    data = {"sender": sender, "message": text}
    headers = {"Authorization": f"Bearer {ACMDA_API_KEY}"}
    try:
        resp = requests.post(ACMDA_API_URL, json=data, headers=headers, timeout=10)
        resp.raise_for_status()
        return resp.json()
    except RequestException as exc:
        log.error("ACMDA request failed: %s", exc)
        return {"error": str(exc)}


def send_response(to: str, text: str) -> None:
    """Send an approved message back to Hostinger and Telegram."""
    data = {"to": to, "text": text}
    try:
        resp = requests.post(HOSTINGER_WH_URL, json=data, timeout=10)
        resp.raise_for_status()
    except RequestException as exc:
        log.error("Sending response failed: %s", exc)
    if TELEGRAM_TOKEN and TELEGRAM_CHAT_ID and Bot:
        try:
            bot = Bot(token=TELEGRAM_TOKEN)
            bot.send_message(chat_id=TELEGRAM_CHAT_ID, text=f"Sent to {to}: {text}")
        except Exception as exc:  # pragma: no cover - network side effect
            log.error("Telegram notification failed: %s", exc)


def run_loop(interval: int = 5) -> None:
    """Continuously poll for messages and process them."""
    log.info("Starting poll loop with %ss interval", interval)
    while True:
        for msg in fetch_messages():
            ai = handle_incoming(msg)
            reply = ai.get("reply")
            if reply:
                send_response(msg.get("from", ""), reply)
        time.sleep(interval)


def demo() -> None:
    """Run a small demo using hard-coded values."""
    sample = {"from": "+441234567890", "text": "Can you fit a lock?"}
    ai = handle_incoming(sample)
    reply = ai.get("reply", "")
    send_response(sample["from"], reply)
    print("Processed sample message")


if __name__ == "__main__":
    if os.getenv("ACMDA_BRIDGE_LOOP"):
        run_loop()
    elif os.getenv("ACMDA_BRIDGE_DEMO"):
        demo()
