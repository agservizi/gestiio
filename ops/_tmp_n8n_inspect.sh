#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
echo "=== gestiio env keys ==="
$DOCKER exec gestiio-app sh -c "grep -E '^(N8N_|GROQ_|NVIDIA_|MARKETING_)' /var/www/html/.env | cut -d= -f1"
echo "=== n8n env (masked values length) ==="
$DOCKER inspect n8n --format '{{range .Config.Env}}{{println .}}{{end}}' | grep -iE 'GROQ|NVIDIA|GESTIIO|CALLBACK|MARKETING|WEBHOOK_URL|N8N_HOST|EDITOR' | while IFS= read -r line; do
  key=$(echo "$line" | cut -d= -f1)
  val=$(echo "$line" | cut -d= -f2-)
  echo "$key len=${#val}"
done
echo "=== n8n networks ==="
$DOCKER inspect n8n --format '{{json .NetworkSettings.Networks}}'
echo "=== try n8n api ==="
$DOCKER exec n8n wget -qO- http://127.0.0.1:5678/healthz 2>/dev/null || $DOCKER exec n8n wget -qO- http://localhost:5678/healthz 2>/dev/null || echo no_health
