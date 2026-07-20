#!/bin/sh
set -e
DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
APP=/home/Carmine/apps/gestiio-20260624-2128

perl -pi -e 's/\r$//' /tmp/label.blade.php /tmp/LockerTagPdf.php /tmp/regen-locker-label.php

mkdir -p "$APP/resources/views/Backend/LockerPoint/pdf" "$APP/app/Http/Support" "$APP/ops"
cp /tmp/label.blade.php "$APP/resources/views/Backend/LockerPoint/pdf/label.blade.php"
cp /tmp/LockerTagPdf.php "$APP/app/Http/Support/LockerTagPdf.php"
cp /tmp/regen-locker-label.php "$APP/ops/regen-locker-label.php"

$DOCKER exec gestiio-app mkdir -p /var/www/html/resources/views/Backend/LockerPoint/pdf /var/www/html/app/Http/Support /var/www/html/ops
$DOCKER cp /tmp/label.blade.php gestiio-app:/var/www/html/resources/views/Backend/LockerPoint/pdf/label.blade.php
$DOCKER cp /tmp/LockerTagPdf.php gestiio-app:/var/www/html/app/Http/Support/LockerTagPdf.php
$DOCKER cp /tmp/regen-locker-label.php gestiio-app:/var/www/html/ops/regen-locker-label.php

$DOCKER exec -w /var/www/html gestiio-app php artisan view:clear
$DOCKER exec -w /var/www/html gestiio-app php /var/www/html/ops/regen-locker-label.php LP-5VB8PA

# Prefer /tmp inside container for outputs from sys_get_temp_dir()
$DOCKER cp gestiio-app:/tmp/etichetta-LP-5VB8PA-a6.pdf /home/Carmine/etichetta-LP-5VB8PA-a6.pdf || true
$DOCKER cp gestiio-app:/tmp/locker-label-debug.html /home/Carmine/locker-label-debug.html || true

# Some PHP use /var/tmp
$DOCKER cp gestiio-app:/var/tmp/etichetta-LP-5VB8PA-a6.pdf /home/Carmine/etichetta-LP-5VB8PA-a6.pdf || true
$DOCKER cp gestiio-app:/var/tmp/locker-label-debug.html /home/Carmine/locker-label-debug.html || true

echo '--- debug html markers ---'
grep -n 'adesiva\|105×148\|Formato A6\|scan codice' /home/Carmine/locker-label-debug.html | head -15 || echo 'no html'
ls -la /home/Carmine/etichetta-LP-5VB8PA-a6.pdf /home/Carmine/locker-label-debug.html
echo DEPLOY_LABEL_OK
