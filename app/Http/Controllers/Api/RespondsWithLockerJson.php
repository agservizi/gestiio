<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

trait RespondsWithLockerJson
{
    protected function lockerSuccess(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function lockerError(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
