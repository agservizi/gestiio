#!/bin/sh
set -u

DOCKER=/Volume1/@apps/DockerEngine/dockerd/bin/docker
LINES=${LINES:-5000}

"$DOCKER" exec gestiio-app sh -lc "tail -n $LINES /var/www/html/storage/logs/laravel.log" \
    | awk '
        /slow_request/ {
            path="unknown"; duration="0"; queries="0"; query_ms="0";
            if (match($0, /"path":"[^"]+"/)) {
                path=substr($0, RSTART+8, RLENGTH-9);
            }
            if (match($0, /"duration_ms":[0-9.]+/)) {
                duration=substr($0, RSTART+14, RLENGTH-14);
            }
            if (match($0, /"query_count":[0-9]+/)) {
                queries=substr($0, RSTART+14, RLENGTH-14);
            }
            if (match($0, /"query_time_ms":[0-9.]+/)) {
                query_ms=substr($0, RSTART+16, RLENGTH-16);
            }
            count[path]++;
            total[path]+=duration;
            max[path]=(duration>max[path]?duration:max[path]);
            qcount[path]+=queries;
            qtime[path]+=query_ms;
        }
        END {
            printf "%-45s %8s %10s %10s %10s %12s\n", "path", "count", "avg_ms", "max_ms", "avg_q", "avg_q_ms";
            for (path in count) {
                printf "%-45s %8d %10.2f %10.2f %10.2f %12.2f\n", path, count[path], total[path]/count[path], max[path], qcount[path]/count[path], qtime[path]/count[path];
            }
        }
    ' \
    | sort -k3 -nr

echo
echo "Slow queries:"
"$DOCKER" exec gestiio-app sh -lc "tail -n $LINES /var/www/html/storage/logs/laravel.log | grep 'slow_query' | tail -20"
