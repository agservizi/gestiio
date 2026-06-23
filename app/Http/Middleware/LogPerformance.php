<?php

namespace App\Http\Middleware;

use App\Services\StructuredLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogPerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (int) ((microtime(true) - $startTime) * 1000);

        // Log slow requests (> 500ms)
        if ($duration > 500) {
            StructuredLogger::logPerformance(
                $request->method().' '.$request->path(),
                $duration,
                [
                    'slow_request' => true,
                    'threshold_ms' => 500,
                    'status_code' => $response->status(),
                    'user_id' => auth()->id(),
                ]
            );
        }

        // Always log API calls
        if ($request->is('api/*')) {
            StructuredLogger::logApiCall(
                $request->path(),
                $request->method(),
                $response->status(),
                $duration
            );
        }

        // Add performance header for debugging
        $response->header('X-Response-Time-Ms', $duration);

        return $response;
    }
}
