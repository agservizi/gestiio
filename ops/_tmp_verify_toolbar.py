#!/usr/bin/env python3
import subprocess
import sys

sh = r"""
set -e
D=/Volume1/@apps/DockerEngine/dockerd/bin/docker
echo '=== hub toolbar ==='
$D exec gestiio-app grep -n 'flex-nowrap\|Genera proforma\|Anteprima' /var/www/html/resources/views/Backend/Billing/hub.blade.php | head -15
echo '=== responsive ==='
$D exec gestiio-app grep -n 'ui-toolbar-actions\|width: auto' /var/www/html/public/assets_backend/css-miei/responsive.css | head -20
echo '=== head css ver ==='
$D exec gestiio-app grep 'mio.css\|responsive.css' /var/www/html/resources/views/Backend/_layout/partials/head.blade.php
echo '=== app-toolbar ==='
$D exec gestiio-app grep -n 'justify-content-between\|flex-stack' /var/www/html/resources/views/Backend/_layout/app-toolbar.blade.php
"""
sh = sh.replace("\r\n", "\n")
# avoid $D expansion by powershell - write without variables
sh = """set -e
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app grep -n 'flex-nowrap\\|Genera proforma\\|Anteprima' /var/www/html/resources/views/Backend/Billing/hub.blade.php | head -15
echo '=== responsive ==='
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app grep -n 'ui-toolbar-actions\\|width: auto' /var/www/html/public/assets_backend/css-miei/responsive.css | head -25
echo '=== head ==='
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app grep 'mio.css\\|responsive.css' /var/www/html/resources/views/Backend/_layout/partials/head.blade.php
echo '=== toolbar ==='
/Volume1/@apps/DockerEngine/dockerd/bin/docker exec gestiio-app grep -n 'justify-content-between\\|flex-stack' /var/www/html/resources/views/Backend/_layout/app-toolbar.blade.php
"""
p = subprocess.run(["ssh", "Carmine@192.168.1.50", "bash", "-s"], input=sh.encode(), capture_output=True)
sys.stdout.buffer.write(p.stdout)
sys.stderr.buffer.write(p.stderr)
sys.exit(p.returncode)
