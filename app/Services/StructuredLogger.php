<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class StructuredLogger
{
    /**
     * Log a business event with structured data.
     */
    public static function logBusinessEvent(string $event, array $data = [], string $channel = 'business_events'): void
    {
        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'user_id' => auth()->id(),
            'data' => $data,
        ];

        Log::channel($channel)->info($event, $payload);
    }

    /**
     * Log authentication event.
     */
    public static function logAuthEvent(string $action, array $context = []): void
    {
        self::logBusinessEvent("auth.{$action}", [
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            ...($context),
        ]);
    }

    /**
     * Log ticket-related event.
     */
    public static function logTicketEvent(string $action, int $ticketId, array $context = []): void
    {
        self::logBusinessEvent("ticket.{$action}", [
            'ticket_id' => $ticketId,
            'user_id' => auth()->id(),
            ...($context),
        ]);
    }

    /**
     * Log contract-related event.
     */
    public static function logContractEvent(string $action, int $contractId, array $context = []): void
    {
        self::logBusinessEvent("contract.{$action}", [
            'contract_id' => $contractId,
            'user_id' => auth()->id(),
            ...($context),
        ]);
    }

    /**
     * Log performance metric.
     */
    public static function logPerformance(string $operation, int $durationMs, array $context = []): void
    {
        Log::channel('performance')->info($operation, [
            'operation' => $operation,
            'duration_ms' => $durationMs,
            'timestamp' => now()->toIso8601String(),
            ...($context),
        ]);
    }

    /**
     * Log API call.
     */
    public static function logApiCall(string $endpoint, string $method, int $statusCode, int $durationMs): void
    {
        self::logBusinessEvent('api.call', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
        ]);
    }
}
