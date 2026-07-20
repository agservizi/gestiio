#!/usr/bin/env python3
from pathlib import Path

p = Path("/home/Carmine/apps/stirling-pdf/configs/settings.yml")
text = p.read_text(encoding="utf-8")
replacements = [
    (
        "defaultHideUnavailableTools: false # Default user preference: hide disabled tools instead of greying them out",
        "defaultHideUnavailableTools: true # Default user preference: hide disabled tools instead of greying them out",
    ),
    (
        "defaultHideUnavailableConversions: false # Default user preference: hide disabled conversion options instead of greying them out",
        "defaultHideUnavailableConversions: true # Default user preference: hide disabled conversion options instead of greying them out",
    ),
    (
        "groupsToRemove: [] # list groups to disable (e.g. ['LibreOffice', 'DeveloperTools', 'DeveloperDocs', 'Automation'])",
        "groupsToRemove: ['DeveloperTools', 'DeveloperDocs'] # list groups to disable",
    ),
]
out = text
for old, new in replacements:
    out = out.replace(old, new)
if out == text:
    print("NO_CHANGE")
else:
    p.write_text(out, encoding="utf-8")
    print("SETTINGS_UPDATED")
