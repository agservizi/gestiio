#!/usr/bin/env python3
"""Deploy Billing/Fatturazione toolbar UI fixes to NAS gestiio-app."""
import base64
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REMOTE = "Carmine@192.168.1.50"
DOCKER = "/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER = "gestiio-app"

FILES = [
    "resources/views/Backend/Billing/hub.blade.php",
    "resources/views/Backend/Billing/index.blade.php",
    "resources/views/Backend/Billing/show.blade.php",
    "resources/views/Backend/Billing/invoiceshelf.blade.php",
    "resources/views/Backend/Billing/preview.blade.php",
    "resources/views/Backend/FatturaProforma/index.blade.php",
    "resources/views/Backend/FatturaProforma/show.blade.php",
    "resources/views/Backend/FatturaProforma/tabella.blade.php",
    "resources/views/Backend/ProduzioneOperatore/index.blade.php",
    "resources/views/Backend/ProduzioneOperatore/show.blade.php",
    "resources/views/Backend/ProduzioneOperatore/tabella.blade.php",
    "resources/views/Backend/_layout/app-toolbar.blade.php",
    "resources/views/Backend/_layout/partials/head.blade.php",
    "public/assets_backend/css-miei/mio.css",
    "public/assets_backend/css-miei/responsive.css",
]


def ssh(script: str) -> tuple[int, str, str]:
    script = script.replace("\r\n", "\n").replace("\r", "\n")
    p = subprocess.run(
        ["ssh", REMOTE, "bash", "-s"],
        input=script.encode("utf-8"),
        capture_output=True,
    )
    return p.returncode, p.stdout.decode("utf-8", "replace"), p.stderr.decode("utf-8", "replace")


def main() -> int:
    missing = [f for f in FILES if not (ROOT / f).is_file()]
    if missing:
        print("Missing:", missing)
        return 1

    # Pack as tar via stdin base64
    import io
    import tarfile

    buf = io.BytesIO()
    with tarfile.open(fileobj=buf, mode="w:gz") as tar:
        for rel in FILES:
            path = ROOT / rel
            tar.add(path, arcname=rel)
    payload = base64.b64encode(buf.getvalue()).decode("ascii")

    script = f"""
set -e
D={DOCKER}
APP=$($D exec {CONTAINER} sh -c 'if [ -f /var/www/html/artisan ]; then echo /var/www/html; else echo /app; fi')
echo "APP=$APP"
HOSTDIR=/home/Carmine/apps/gestiio-live-sync
mkdir -p "$HOSTDIR"
echo {payload} | base64 -d > /tmp/fatturazione_ui.tgz
rm -rf /tmp/fatturazione_ui_extract
mkdir -p /tmp/fatturazione_ui_extract
tar -xzf /tmp/fatturazione_ui.tgz -C /tmp/fatturazione_ui_extract
# sync into container
$D cp /tmp/fatturazione_ui_extract/. {CONTAINER}:$APP/
# also mirror on host app dir if present
LIVE=$(ls -d /home/Carmine/apps/gestiio-* 2>/dev/null | grep -v backup | sort | tail -1 || true)
if [ -n "$LIVE" ] && [ -d "$LIVE/resources" ]; then
  echo "HOST_LIVE=$LIVE"
  cp -a /tmp/fatturazione_ui_extract/. "$LIVE/"
fi
$D exec {CONTAINER} php $APP/artisan view:clear
$D exec {CONTAINER} php $APP/artisan cache:clear
echo DONE
"""
    rc, out, err = ssh(script)
    sys.stdout.write(out)
    sys.stderr.write(err)
    return rc


if __name__ == "__main__":
    sys.exit(main())
