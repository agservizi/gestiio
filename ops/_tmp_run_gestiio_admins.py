#!/usr/bin/env python3
import base64
import subprocess
import sys
from pathlib import Path

REMOTE = "Carmine@192.168.1.50"
DOCKER = "/Volume1/@apps/DockerEngine/dockerd/bin/docker"

php = Path(__file__).with_name("_tmp_gestiio_admins.php").read_text(encoding="utf-8")
b64 = base64.b64encode(php.encode()).decode()
sh = (
    "set -e\n"
    f"echo {b64} | base64 -d > /tmp/g_admins.php\n"
    f"{DOCKER} cp /tmp/g_admins.php gestiio-app:/tmp/g_admins.php\n"
    f"{DOCKER} exec gestiio-app php /tmp/g_admins.php\n"
)
p = subprocess.run(["ssh", REMOTE, "bash", "-s"], input=sh.encode(), capture_output=True)
sys.stdout.buffer.write(p.stdout)
sys.stderr.buffer.write(p.stderr)
sys.exit(p.returncode)
