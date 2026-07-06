#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP_CONTAINER=gestiio-app
DB_CONTAINER=gestiio-db
APP_PATH=/var/www/html
BACKUP_ROOT=/Volume1/homes/Carmine/gestiio-backups
LOG=/home/Carmine/gestiio-backup.log
RETENTION_DAYS=14
ALERT=/Volume1/homes/Carmine/gestiio-alert.sh

stamp() {
    date "+%Y-%m-%d %H:%M:%S"
}

log() {
    echo "$(stamp) $*" >> "$LOG"
}

alert() {
    if [ -x "$ALERT" ]; then
        "$ALERT" "$1" "$2"
    fi
}

run_app() {
    "$DOCKER" exec "$APP_CONTAINER" sh -lc "$1"
}

backup_id=$(date "+%Y%m%d-%H%M%S")
dest="$BACKUP_ROOT/$backup_id"
mkdir -p "$dest"

log "backup begin id=$backup_id"

if ! "$DOCKER" info >/dev/null 2>&1; then
    log "Docker unavailable"
    alert "Backup Gestiio fallito: Docker non disponibile" "CRITICAL"
    exit 1
fi

if ! "$DOCKER" inspect "$APP_CONTAINER" >/dev/null 2>&1 || ! "$DOCKER" inspect "$DB_CONTAINER" >/dev/null 2>&1; then
    log "containers missing"
    alert "Backup Gestiio fallito: container mancanti" "CRITICAL"
    exit 1
fi

env_file="$dest/env.redacted"
run_app "cd $APP_PATH && sed -E 's/^(.*(PASSWORD|SECRET|KEY|TOKEN|APP_KEY).*)=.*/\\1=REDACTED/I' .env" > "$env_file" 2>>"$LOG" || true

db_name=$(run_app "php -r '\$e=parse_ini_file(\"$APP_PATH/.env\", false, INI_SCANNER_RAW); echo \$e[\"DB_DATABASE\"] ?? \"\";'" 2>>"$LOG" || true)
db_user=$(run_app "php -r '\$e=parse_ini_file(\"$APP_PATH/.env\", false, INI_SCANNER_RAW); echo \$e[\"DB_USERNAME\"] ?? \"\";'" 2>>"$LOG" || true)
db_pass=$(run_app "php -r '\$e=parse_ini_file(\"$APP_PATH/.env\", false, INI_SCANNER_RAW); echo \$e[\"DB_PASSWORD\"] ?? \"\";'" 2>>"$LOG" || true)

if [ "$db_name" = "" ] || [ "$db_user" = "" ]; then
    log "database config missing"
    alert "Backup Gestiio fallito: config database non letta" "CRITICAL"
    exit 1
fi

dump_bin=$("$DOCKER" exec "$DB_CONTAINER" sh -lc "command -v mariadb-dump || command -v mysqldump" 2>>"$LOG" || true)
if [ "$dump_bin" = "" ]; then
    log "dump binary missing"
    alert "Backup Gestiio fallito: mariadb-dump/mysqldump non disponibile" "CRITICAL"
    exit 1
fi

if ! "$DOCKER" exec "$DB_CONTAINER" sh -lc "MYSQL_PWD='$db_pass' '$dump_bin' --single-transaction --routines --triggers -u '$db_user' '$db_name'" > "$dest/database.sql" 2>>"$LOG"; then
    log "mysqldump failed"
    alert "Backup Gestiio fallito: dump database non riuscito" "CRITICAL"
    exit 1
fi

if ! "$DOCKER" cp "$APP_CONTAINER:$APP_PATH/storage/app" "$dest/storage-app" >> "$LOG" 2>&1; then
    log "storage copy failed"
    alert "Backup Gestiio warning: storage/app non copiato" "WARNING"
fi

tar -C "$BACKUP_ROOT" -czf "$BACKUP_ROOT/gestiio-backup-$backup_id.tar.gz" "$backup_id" >> "$LOG" 2>&1
archive="$BACKUP_ROOT/gestiio-backup-$backup_id.tar.gz"

if [ ! -s "$archive" ]; then
    log "archive missing"
    alert "Backup Gestiio fallito: archivio vuoto" "CRITICAL"
    exit 1
fi

if ! tar -tzf "$archive" >/dev/null 2>>"$LOG"; then
    log "archive validation failed"
    alert "Backup Gestiio fallito: archivio non valido" "CRITICAL"
    exit 1
fi

if ! grep -q "CREATE TABLE" "$dest/database.sql" 2>/dev/null; then
    log "database dump validation failed"
    alert "Backup Gestiio fallito: dump database non valido" "CRITICAL"
    exit 1
fi

find "$BACKUP_ROOT" -maxdepth 1 -name "gestiio-backup-*.tar.gz" -mtime +"$RETENTION_DAYS" -delete 2>>"$LOG" || true
find "$BACKUP_ROOT" -maxdepth 1 -type d -name "20*" -mtime +2 -exec rm -rf {} \; 2>>"$LOG" || true

size=$(du -h "$archive" | awk '{print $1}')
log "backup complete id=$backup_id size=$size archive=$archive"
alert "Backup Gestiio completato: $archive ($size)" "INFO"
