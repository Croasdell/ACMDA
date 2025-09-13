#!/usr/bin/env python3
"""Bridge WhatsApp webhook with ACMDA and host environment.

This script demonstrates how to connect incoming WhatsApp messages
received via Hostinger to the local ACMDA service. It also shows how to
send approved responses back through the same webhook.

Requirements:
  - requests
  - pywhat3k (WhatsApp integration helper)
  - python-telegram-bot (optional Telegram support)

Configuration is done via environment variables:
  ACMDA_API_URL   -> endpoint for local ACMDA (default http://localhost/acmda.php)
  ACMDA_API_KEY   -> key for authenticating with ACMDA
  HOSTINGER_WH_URL-> webhook URL on Hostinger to post outbound messages
"""

import json
import os
from typing import Dict

import requests

try:
    import pywhat3k  # type: ignore  # noqa: F401
except Exception:  # pragma: no cover - library optional for this demo
    pass

ACMDA_API_URL = os.getenv("ACMDA_API_URL", "http://localhost/acmda.php")
ACMDA_API_KEY = os.getenv("ACMDA_API_KEY", "test-key")
HOSTINGER_WH_URL = os.getenv("HOSTINGER_WH_URL", "http://localhost/webhook")


def handle_incoming(payload: Dict[str, str]) -> Dict[str, str]:
    """Forward inbound WhatsApp message to ACMDA and return its draft reply."""
    sender = payload.get("from", "")
    text = payload.get("text", "")
    data = {"sender": sender, "message": text}
    headers = {"Authorization": f"Bearer {ACMDA_API_KEY}"}
    resp = requests.post(ACMDA_API_URL, json=data, headers=headers, timeout=10)
    resp.raise_for_status()
    return resp.json()


def send_response(to: str, text: str) -> None:
    """Send an approved message back to Hostinger for dispatch."""
    data = {"to": to, "text": text}
    resp = requests.post(HOSTINGER_WH_URL, json=data, timeout=10)
    resp.raise_for_status()


def demo() -> None:
    """Run a small demo using hard-coded values."""
    sample = {"from": "+441234567890", "text": "Can you fit a lock?"}
    ai = handle_incoming(sample)
    reply = ai.get("reply", "")
    send_response(sample["from"], reply)
    print("Processed sample message")


if __name__ == "__main__":
    if os.environ.get("ACMDA_BRIDGE_DEMO"):
        demo()
