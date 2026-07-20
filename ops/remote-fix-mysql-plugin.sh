#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PASS=SfRootGestiio2026Secure

$DOCKER exec seafile-mysql mariadb -uroot -p"$PASS" -e "
ALTER USER 'root'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('$PASS');
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('$PASS');
FLUSH PRIVILEGES;
SELECT user,host,plugin,LEFT(authentication_string,20) FROM mysql.user WHERE user='root';
"

echo "== mysql client in seafile image =="
$DOCKER exec seafile bash -lc 'which mysql || which mariadb || ls /usr/bin/*sql* 2>/dev/null | head'

echo "== python MySQLdb retry =="
$DOCKER exec seafile bash -lc "python3 - <<'PY'
import MySQLdb, os
pw=os.environ['DB_ROOT_PASSWD']
for host in ['db','seafile-mysql']:
  try:
    c=MySQLdb.connect(host=host, user='root', passwd=pw, connect_timeout=5)
    print(host, 'OK', c.get_server_info())
    c.close()
  except Exception as e:
    print(host, 'FAIL', e)
PY"

echo "== create dedicated bootstrap user =="
$DOCKER exec seafile-mysql mariadb -uroot -p"$PASS" -e "
CREATE USER IF NOT EXISTS 'seafile'@'%' IDENTIFIED BY '$PASS';
ALTER USER 'seafile'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('$PASS');
GRANT ALL PRIVILEGES ON *.* TO 'seafile'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
"

$DOCKER exec seafile bash -lc "python3 - <<'PY'
import MySQLdb
pw='SfRootGestiio2026Secure'
try:
  c=MySQLdb.connect(host='db', user='seafile', passwd=pw)
  print('seafile user OK')
except Exception as e:
  print('seafile user FAIL', e)
try:
  c=MySQLdb.connect(host='db', user='root', passwd=pw)
  print('root OK')
except Exception as e:
  print('root FAIL', e)
PY"
