<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\RespondsWithLuggageJson;
use App\Http\Controllers\Controller;
use App\Http\Services\LuggageDepositService;
use App\Http\Services\LuggageStationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LuggageStatsController extends Controller
{
    use RespondsWithLuggageJson;

    public function __construct(
        private LuggageDepositService $service,
        private LuggageStationService $stations,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\LuggageDeposit::class);

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;
        $user = $request->user();
        $isAdmin = $user->hasPermissionTo('admin');
        $station = $isAdmin ? null : $this->stations->forUser($user);

        return $this->luggageSuccess($this->service->stats($from, $to, $station, $isAdmin));
    }
}
