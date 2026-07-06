<?php

namespace App\Http\Middleware;

use App\Models\RegistroAttivita;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LogBackendActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if ($this->shouldLog($request)) {
            $this->logActivity($request, $response, $startedAt);
        }

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        return $request->is('backend/*')
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && Schema::hasTable('registro_attivita');
    }

    protected function logActivity(Request $request, Response $response, float $startedAt): void
    {
        try {
            RegistroAttivita::create([
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => $request->route()?->getName(),
                'controller_action' => $request->route()?->getActionName(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'payload' => $this->safePayload($request),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function safePayload(Request $request): array
    {
        return collect($request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'file',
            'files',
            'allegato',
            'allegati',
            'documento',
            'documenti',
        ]))
            ->filter(fn ($value) => is_scalar($value) || is_null($value))
            ->map(fn ($value) => is_string($value) ? mb_substr($value, 0, 250) : $value)
            ->all();
    }
}
