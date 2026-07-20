#!/bin/sh
# Ripara @endfor troncati in @endfo (effetto collaterale del fix @enderro su alcuni deploy).
set -e
HOST="Carmine@192.168.1.50"
APP="/home/Carmine/apps/gestiio-20260624-2128"
DOCKER="/Volume1/@apps/DockerEngine/dockerd/bin/docker"
CONTAINER="gestiio-app"

# (?!r) evita di corrompere @endforeach -> @endforreach
fix_endfor() {
  find "$1/resources/views" -name '*.blade.php' -exec perl -pi -e 's/\\@endforreach/\\@endforeach/g; s/\\@endfo(?!r)/\\@endfor/g' {} +
}

echo "Fix @endfo -> @endfor on host..."
ssh "$HOST" "find $APP/resources/views -name '*.blade.php' -exec perl -pi -e 's/\\@endforreach/\\@endforeach/g; s/\\@endfo(?!r)/\\@endfor/g' {} +"

echo "Fix @endfo -> @endfor in container..."
ssh "$HOST" "$DOCKER exec $CONTAINER find /var/www/html/resources/views -name '*.blade.php' -exec perl -pi -e 's/\\@endforreach/\\@endforeach/g; s/\\@endfo(?!r)/\\@endfor/g' {} +"

echo "Remaining @endfo / @endforreach (should be none):"
ssh "$HOST" "$DOCKER exec $CONTAINER grep -rnE '@endfo\$|@endforreach' /var/www/html/resources/views --include='*.blade.php' || echo 'NONE'"

ssh "$HOST" "$DOCKER exec -u www-data $CONTAINER php /var/www/html/artisan view:clear"
ssh "$HOST" "$DOCKER exec -u www-data $CONTAINER php /var/www/html/artisan view:cache"
ssh "$HOST" "$DOCKER restart $CONTAINER"
sleep 6
ssh "$HOST" "curl -s -o /dev/null -w 'login=%{http_code}\n' http://localhost:8090/login"
echo FIX_BLADE_ENDFOR_OK
