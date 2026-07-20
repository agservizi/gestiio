#!/bin/sh
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
PASS=SfRootGestiio2026Secure

echo "== users =="
$DOCKER exec seafile-mysql mariadb -uroot -p"$PASS" -e "SELECT user,host,plugin FROM mysql.user;"

echo "== remote from network =="
$DOCKER run --rm --network seafile_seafile_net mariadb:10.11 \
  mariadb -hdb -uroot -p"$PASS" -e "SELECT 1 AS ok;" 2>&1

echo "== fix root@% =="
$DOCKER exec seafile-mysql mariadb -uroot -p"$PASS" -e "
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '$PASS';
ALTER USER 'root'@'%' IDENTIFIED BY '$PASS';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SELECT user,host,plugin FROM mysql.user;
"

echo "== remote retry =="
$DOCKER run --rm --network seafile_seafile_net mariadb:10.11 \
  mariadb -hdb -uroot -p"$PASS" -e "SELECT 1 AS ok;" 2>&1

echo "== restart seafile =="
$DOCKER restart seafile
sleep 30
$DOCKER logs seafile --tail 20
$DOCKER exec seafile curl -s -o /dev/null -w 'http=%{http_code}\n' http://127.0.0.1/
