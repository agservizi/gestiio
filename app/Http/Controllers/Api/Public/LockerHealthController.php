<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\RespondsWithLockerJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LockerHealthController extends Controller
{
    use RespondsWithLockerJson;

    public function show(): JsonResponse
    {
        return $this->lockerSuccess([
            'status' => 'ok',
            'service' => 'locker-point',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
