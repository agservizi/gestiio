#!/bin/bash
set -euo pipefail
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker

# Enable Authorization header passthrough for PHP/Apache
CONF=/etc/apache2/conf-available/pass-authorization.conf
$DOCKER exec gestiio-app bash -c "cat > $CONF <<'EOF'
<Directory /var/www/html>
    CGIPassAuth On
    SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1
</Directory>
EOF
a2enconf pass-authorization 2>/dev/null || true
# Also patch vhost if present
for f in /etc/apache2/sites-enabled/*; do
  [ -f \"\$f\" ] || continue
  if ! grep -q 'CGIPassAuth On' \"\$f\" 2>/dev/null; then
    sed -i 's|<Directory /var/www/>|<Directory /var/www/>\n\tCGIPassAuth On\n\tSetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1|' \"\$f\" 2>/dev/null || true
  fi
done
apache2ctl configtest && apache2ctl graceful
echo APACHE_OK
"

echo '=== apache conf check ==='
$DOCKER exec gestiio-app sh -c 'grep -R CGIPassAuth /etc/apache2 2>/dev/null | head -10; ls /etc/apache2/conf-enabled/ | head'
