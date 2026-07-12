#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
CONTAINER=gestiio-app
ENV_FILE=/var/www/html/.env

if $DOCKER exec "$CONTAINER" sh -lc "grep -q '^LUGGAGE_API_KEY=' $ENV_FILE"; then
  echo LUGGAGE_ENV_EXISTS
  exit 0
fi

KEY=$($DOCKER exec "$CONTAINER" php -r 'echo bin2hex(random_bytes(32));')
$DOCKER exec "$CONTAINER" sh -lc "cat >> $ENV_FILE <<EOF

# Deposito Bagagli
LUGGAGE_API_KEY=$KEY
LUGGAGE_DEFAULT_RATE=2
LUGGAGE_MAX_CAPACITY=50
LUGGAGE_MAX_BAGS_PER_BOOKING=10
LUGGAGE_MIN_DAYS=1
LUGGAGE_CURRENCY=EUR
EOF"
echo LUGGAGE_ENV_ADDED
$DOCKER exec "$CONTAINER" grep LUGGAGE_API_KEY "$ENV_FILE"
