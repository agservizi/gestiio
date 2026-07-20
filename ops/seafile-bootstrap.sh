#!/bin/sh
# Bootstrap Seafile: locale IT, library Documenti, utente agente RO.
# Eseguire sul NAS dopo il primo `docker compose up` (Seafile già healthy).
set -e

DOCKER="${DOCKER:-/Volume1/@apps/DockerEngine/dockerd/bin/docker}"
CONTAINER="${SEAFILE_CONTAINER:-seafile}"
CONF_DIR="/home/Carmine/apps/seafile/data/seafile/conf"
SETTINGS="$CONF_DIR/seahub_settings.py"
PUBLIC_URL="${SEAFILE_PUBLIC_URL:-https://documenti.agenziaplinio.it}"
ADMIN_EMAIL="${SEAFILE_ADMIN_EMAIL:-admin@gestiio.local}"
ADMIN_PASSWORD="${SEAFILE_ADMIN_PASSWORD:?SEAFILE_ADMIN_PASSWORD required}"
AGENTE_EMAIL="${SEAFILE_AGENTE_EMAIL:-agente-ro@gestiio.local}"
AGENTE_PASSWORD="${SEAFILE_AGENTE_PASSWORD:?SEAFILE_AGENTE_PASSWORD required}"
LIBRARY_NAME="${SEAFILE_LIBRARY_NAME:-Documenti Gestiio}"
GESTIIO_ORIGIN="${GESTIIO_ORIGIN:-https://gestiio.agenziaplinio.it}"

echo "==> Attendo Seahub..."
i=0
while [ "$i" -lt 60 ]; do
  if $DOCKER exec "$CONTAINER" curl -sf "http://127.0.0.1/" >/dev/null 2>&1; then
    break
  fi
  i=$((i + 1))
  sleep 5
done

if [ ! -f "$SETTINGS" ]; then
  # Path tipico volume Docker
  SETTINGS=$($DOCKER exec "$CONTAINER" sh -c 'ls /shared/seafile/conf/seahub_settings.py 2>/dev/null || ls /opt/seafile/conf/seahub_settings.py 2>/dev/null' | tr -d '\r')
  if [ -z "$SETTINGS" ]; then
    echo "seahub_settings.py non trovato" >&2
    exit 1
  fi
  # Usa path host se montato
  if [ -f /home/Carmine/apps/seafile/data/seafile/conf/seahub_settings.py ]; then
    SETTINGS=/home/Carmine/apps/seafile/data/seafile/conf/seahub_settings.py
  else
    echo "Modifico settings via docker exec"
    SETTINGS_IN_CONTAINER=/shared/seafile/conf/seahub_settings.py
    $DOCKER exec "$CONTAINER" sh -c "grep -q \"LANGUAGE_CODE = 'it'\" $SETTINGS_IN_CONTAINER 2>/dev/null || cat >> $SETTINGS_IN_CONTAINER << 'EOF'

# --- Gestiio: italiano obbligatorio ---
LANGUAGE_CODE = 'it'
TIME_ZONE = 'Europe/Rome'
LANGUAGES = (
    ('it', 'Italiano'),
)
SITE_NAME = 'Documenti'
SITE_TITLE = 'Documenti Gestiio'
SHARE_LINK_EMAIL_LANGUAGE = 'it'
ENABLE_SETTINGS_VIA_WEB = False
SERVICE_URL = '${PUBLIC_URL}'
FILE_SERVER_ROOT = '${PUBLIC_URL}/seafhttp'
EOF"
    $DOCKER restart "$CONTAINER"
    sleep 15
    SETTINGS=""
  fi
fi

if [ -n "$SETTINGS" ] && [ -f "$SETTINGS" ]; then
  if ! grep -q "LANGUAGE_CODE = 'it'" "$SETTINGS" 2>/dev/null; then
    cat >> "$SETTINGS" << EOF

# --- Gestiio: italiano obbligatorio ---
LANGUAGE_CODE = 'it'
TIME_ZONE = 'Europe/Rome'
LANGUAGES = (
    ('it', 'Italiano'),
)
SITE_NAME = 'Documenti'
SITE_TITLE = 'Documenti Gestiio'
SHARE_LINK_EMAIL_LANGUAGE = 'it'
ENABLE_SETTINGS_VIA_WEB = False
SERVICE_URL = '${PUBLIC_URL}'
FILE_SERVER_ROOT = '${PUBLIC_URL}/seafhttp'
EOF
  fi
  $DOCKER restart "$CONTAINER"
  sleep 15
fi

# Frame embedding da Gestiio (nginx interno Seafile)
$DOCKER exec "$CONTAINER" sh -c '
NGX=$(ls /shared/nginx/conf/seafile.nginx.conf /etc/nginx/sites-enabled/seafile.conf 2>/dev/null | head -1)
if [ -n "$NGX" ] && ! grep -q frame-ancestors "$NGX" 2>/dev/null; then
  sed -i "/server_name/a\    add_header Content-Security-Policy \"frame-ancestors '"${GESTIIO_ORIGIN}"' '\''self'\'';\" always;\n    add_header X-Frame-Options \"ALLOWALL\" always;" "$NGX" 2>/dev/null || true
  nginx -s reload 2>/dev/null || true
fi
' || true

echo "==> Token admin + library + utente RO"
$DOCKER run --rm --network gestiio-20260624-2128_default \
  -e PUBLIC_URL="$PUBLIC_URL" \
  -e ADMIN_EMAIL="$ADMIN_EMAIL" \
  -e ADMIN_PASSWORD="$ADMIN_PASSWORD" \
  -e AGENTE_EMAIL="$AGENTE_EMAIL" \
  -e AGENTE_PASSWORD="$AGENTE_PASSWORD" \
  -e LIBRARY_NAME="$LIBRARY_NAME" \
  curlimages/curl:8.5.0 sh -c '
set -e
BASE=http://seafile
# attesa
i=0
while [ $i -lt 40 ]; do
  curl -sf "$BASE/" >/dev/null 2>&1 && break
  i=$((i+1)); sleep 3
done

TOKEN=$(curl -sf -X POST "$BASE/api2/auth-token/" \
  -d "username=$ADMIN_EMAIL" -d "password=$ADMIN_PASSWORD" | sed -n "s/.*\"token\"[[:space:]]*:[[:space:]]*\"\\([^\"]*\\)\".*/\\1/p")
test -n "$TOKEN"

# lingua admin
curl -sf -X PUT "$BASE/api/v2.1/user/" \
  -H "Authorization: Token $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"language\":\"it\"}" >/dev/null 2>&1 || true

# crea utente agente se manca
curl -sf -X PUT "$BASE/api2/accounts/$AGENTE_EMAIL/" \
  -H "Authorization: Token $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"password\":\"$AGENTE_PASSWORD\",\"is_active\":true,\"is_staff\":false}" >/dev/null 2>&1 \
  || curl -sf -X PUT "$BASE/api/v2.1/admin/users/" \
  -H "Authorization: Token $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$AGENTE_EMAIL\",\"password\":\"$AGENTE_PASSWORD\"}" >/dev/null 2>&1 \
  || true

# library
REPO=$(curl -sf "$BASE/api2/repos/" -H "Authorization: Token $TOKEN")
REPO_ID=$(echo "$REPO" | sed -n "s/.*\"name\"[[:space:]]*:[[:space:]]*\"$LIBRARY_NAME\".*\"id\"[[:space:]]*:[[:space:]]*\"\\([^\"]*\\)\".*/\\1/p" | head -1)
if [ -z "$REPO_ID" ]; then
  REPO_ID=$(echo "$REPO" | sed -n "s/.*\"id\"[[:space:]]*:[[:space:]]*\"\\([^\"]*\\)\".*\"name\"[[:space:]]*:[[:space:]]*\"$LIBRARY_NAME\".*/\\1/p" | head -1)
fi
if [ -z "$REPO_ID" ]; then
  REPO_ID=$(curl -sf -X POST "$BASE/api2/repos/" \
    -H "Authorization: Token $TOKEN" \
    -d "name=$LIBRARY_NAME" -d "desc=Documenti migrati da Gestiio" \
    | sed -n "s/.*\"repo_id\"[[:space:]]*:[[:space:]]*\"\\([^\"]*\\)\".*/\\1/p")
fi
test -n "$REPO_ID"

# share RO all agente
curl -sf -X PUT "$BASE/api2/repos/$REPO_ID/dir/shared_items/?p=/" \
  -H "Authorization: Token $TOKEN" \
  -d "share_type=user" -d "username=$AGENTE_EMAIL" -d "permission=r" >/dev/null 2>&1 \
  || curl -sf -X PUT "$BASE/api2/repos/$REPO_ID/shared-users/" \
  -H "Authorization: Token $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"share_type\":\"user\",\"username\":\"$AGENTE_EMAIL\",\"permission\":\"r\"}" >/dev/null 2>&1 \
  || curl -sf -X POST "$BASE/api/v2.1/repos/$REPO_ID/share/" \
  -H "Authorization: Token $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"share_type\":\"user\",\"username\":\"$AGENTE_EMAIL\",\"permission\":\"r\"}" >/dev/null 2>&1 \
  || true

echo "SEAFILE_REPO_ID=$REPO_ID"
echo "SEAFILE_ADMIN_EMAIL=$ADMIN_EMAIL"
echo "SEAFILE_AGENTE_EMAIL=$AGENTE_EMAIL"
echo "SEAFILE_PUBLIC_URL=$PUBLIC_URL"
echo BOOTSTRAP_OK
'

echo "==> Bootstrap completato. Aggiungi SEAFILE_REPO_ID al .env di gestiio-app."
