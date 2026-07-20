#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
$DOCKER exec seafile curl -s http://127.0.0.1/accounts/login/ > /tmp/sea-login.html
grep -oE 'name="[^"]+"' /tmp/sea-login.html | sort -u
echo ----
grep -i remember /tmp/sea-login.html | head -5
grep -i csrf /tmp/sea-login.html | head -5
