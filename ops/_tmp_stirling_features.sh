#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

echo '=== binaries ==='
$DOCKER exec stirling-pdf sh -c 'command -v libreoffice || command -v soffice || echo NO_LIBREOFFICE; command -v tesseract || echo NO_TESSERACT; command -v qpdf || echo NO_QPDF; command -v gs || echo NO_GHOSTSCRIPT; ls /usr/share/tessdata 2>/dev/null | head'

echo '=== settings endpoints/ui/premium ==='
$DOCKER exec stirling-pdf sh -c 'grep -nE "groupsToRemove|toRemove|defaultHide|premium:|enabled:|processEndpoint|endpoints:" /configs/settings.yml | head -80'

echo '=== feature flags via API ==='
$DOCKER exec stirling-pdf sh -c '
for u in /pdf-tools/api/v1/info/status /pdf-tools/api/v1/info/endpoints /pdf-tools/api/v1/settings /api/v1/info/status; do
  echo "URL $u"
  wget -qO- "http://127.0.0.1:8080$u" 2>/dev/null | head -c 800
  echo
done
'

echo '=== image labels ==='
$DOCKER inspect stirling-pdf --format '{{.Config.Image}} {{.Config.Labels}}' | head -c 2000
echo
