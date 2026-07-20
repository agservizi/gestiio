#!/bin/bash
set -euo pipefail
cp -f /tmp/stirling-docker-restart-watch.sh /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
perl -pi -e 's/\r\n/\n/g' /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh
chmod +x /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh

# kill old watchers
ps aux | grep '[s]tirling-docker-restart-watch' | awk '{print $2}' | xargs -r kill 2>/dev/null || true
sleep 1
nohup bash /home/Carmine/apps/stirling-pdf/stirling-docker-restart-watch.sh >> /home/Carmine/apps/stirling-pdf/logs/restart-watch.log 2>&1 &
echo "pid=$!"
sleep 1
ps aux | grep '[s]tirling-docker-restart-watch' || echo 'WATCHER_MISSING'
grep -n 'defaultLocale\|languages:' /home/Carmine/apps/stirling-pdf/configs/settings.yml | head -5
echo DONE
