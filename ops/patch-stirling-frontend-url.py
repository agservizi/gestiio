#!/usr/bin/env python3
"""Imposta frontendUrl/backendUrl pubblici per QR mobile-scanner."""
from pathlib import Path

p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
public = "https://gestiio.agenziaplinio.it/pdf-tools"

replacements = [
    (
        'backendUrl: "" # Backend base URL for SAML/OAuth/API callbacks (e.g. \'http://localhost:8080\' for dev, \'https://api.example.com\' for production). REQUIRED for SSO authentication to work correctly. This is where your IdP will send SAML responses and OAuth callbacks. Leave empty to default to \'http://localhost:8080\' in development.',
        f'backendUrl: "{public}" # Backend base URL (public Gestiio proxy)',
    ),
    (
        'frontendUrl: "" # Frontend URL for invite email links (e.g. \'https://app.example.com\'). Optional - if not set, will use backendUrl. This is the URL users click in invite emails.',
        f'frontendUrl: "{public}" # Public URL for QR mobile-scanner / invite links',
    ),
]

# Fallback più corto se il commento nel file differisce
import re

out = text
for old, new in replacements:
    if old in out:
        out = out.replace(old, new)

if out == text:
    out2 = re.sub(
        r'^(\s*)backendUrl:\s*".*?"',
        rf'\1backendUrl: "{public}"',
        out,
        count=1,
        flags=re.M,
    )
    out2 = re.sub(
        r'^(\s*)frontendUrl:\s*".*?"',
        rf'\1frontendUrl: "{public}"',
        out2,
        count=1,
        flags=re.M,
    )
    out = out2

if out == text:
    print("NO_CHANGE")
else:
    p.write_text(out, encoding="utf-8")
    print("FRONTEND_URL_UPDATED")
    for line in out.splitlines():
        if "frontendUrl:" in line or "backendUrl:" in line:
            if "customDatabaseUrl" not in line:
                print(line.strip())
