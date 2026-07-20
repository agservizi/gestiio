#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PASS=SfRootGestiio2026Secure

echo "== shared tree =="
$DOCKER exec seafile find /shared -type f 2>/dev/null | head -50

echo "== env =="
$DOCKER exec seafile printenv | grep -E 'DB_|SEAFILE_' | sort

echo "== try python mysql from seafile =="
$DOCKER exec seafile bash -lc "python3 - <<'PY'
import os
try:
    import MySQLdb
except Exception as e:
    print('no MySQLdb', e)
    raise SystemExit(0)
pw=os.environ.get('DB_ROOT_PASSWD','')
print('pw_len', len(pw), 'pw', repr(pw))
try:
    c=MySQLdb.connect(host='db', user='root', passwd=pw)
    print('connect_ok', c.get_server_info())
except Exception as e:
    print('connect_fail', e)
PY"

echo "== bootstrap logs / scripts =="
$DOCKER exec seafile ls -la /scripts 2>/dev/null | head
$DOCKER exec seafile bash -lc 'grep -R DB_ROOT /scripts 2>/dev/null | head -20'

# Official image may use SEAFILE_MYSQL_DB_PASSWORD in newer versions
# Clear shared completely and also set password without special pattern
echo "== wipe shared and recreate with mariadb:10.5 =="
