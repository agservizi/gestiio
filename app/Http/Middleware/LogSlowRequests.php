<?php

namespace App\Http\Middleware;

use App\Support\PerformanceTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next)
    {
        PerformanceTracker::reset();
        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
        $slowRequestMs = (float) env('SLOW_REQUEST_LOG_MS', 750);
        $slowQueryTotalMs = (float) env('SLOW_QUERY_TOTAL_LOG_MS', 500);
        $queryTimeMs = PerformanceTracker::queryTimeMs();

        if ($durationMs >= $slowRequestMs || $queryTimeMs >= $slowQueryTotalMs) {
            Log::info('slow_request', [
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route' => optional($request->route())->getName(),
                'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'duration_ms' => $durationMs,
                'query_count' => PerformanceTracker::queryCount(),
                'query_time_ms' => $queryTimeMs,
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
                'user_id' => Auth::id(),
            ]);
        }

        return $response;
    }
}
