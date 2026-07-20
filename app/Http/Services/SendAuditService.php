<?php

namespace App\Http\Services;

use App\Models\SendRequest;
use App\Models\SendRequestAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SendAuditService
{
    public function log(
        string $action,
        ?SendRequest $request = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        // Non loggare dati sensibili completi
        $before = $this->sanitize($before);
        $after = $this->sanitize($after);

        SendRequestAuditLog::query()->create([
            'send_request_id' => $request?->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $masked = $data;
        foreach (['tax_code', 'document_number', 'email', 'phone', 'vat_number'] as $key) {
            if (isset($masked[$key]) && is_string($masked[$key])) {
                $masked[$key] = $this->mask($masked[$key]);
            }
        }

        return $masked;
    }

    public function mask(string $value): string
    {
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return mb_substr($value, 0, 2).str_repeat('*', max(0, $len - 4)).mb_substr($value, -2);
    }
}
