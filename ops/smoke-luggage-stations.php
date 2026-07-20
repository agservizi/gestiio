<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\LuggageDepositService;
use App\Http\Services\LuggageStationService;
use App\Models\Agente;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

if (! Schema::hasTable('luggage_stations')) {
    echo json_encode(['ok' => false, 'error' => 'migration missing'])."\n";
    exit(1);
}

$service = app(LuggageDepositService::class);
$stations = app(LuggageStationService::class);

$agentRows = Agente::query()->orderBy('id')->get();
$users = $agentRows
    ->map(fn ($a) => User::find($a->user_id))
    ->filter(fn ($u) => $u && ! $u->hasPermissionTo('admin'))
    ->values();
$agentA = $users->get(0);
$agentB = $users->get(1);

if (! $agentA) {
    echo json_encode(['ok' => false, 'error' => 'NO_AGENT'])."\n";
    exit(1);
}

$stationA = $stations->ensureStation($agentA);
$stationB = $agentB ? $stations->ensureStation($agentB) : null;

$day = Carbon::today()->addDays(3);
$depA = $service->create([
    'customer_name' => 'Smoke Station A',
    'booking_date' => $day->toDateString(),
    'bag_count' => 1,
    'expected_check_out' => $day->copy()->addDay()->toDateString(),
], 'SPORTELLO', $stationA);

$depB = null;
if ($stationB) {
    $depB = $service->create([
        'customer_name' => 'Smoke Station B',
        'booking_date' => $day->toDateString(),
        'bag_count' => 1,
        'expected_check_out' => $day->copy()->addDay()->toDateString(),
    ], 'SPORTELLO', $stationB);
}

$listA = $service->list(['q' => 'Smoke Station'], 1, 50, $stationA);
$codesA = collect($listA->items())->pluck('code')->all();

$isolationOk = in_array($depA->code, $codesA, true)
    && (! $depB || ! in_array($depB->code, $codesA, true));

$availA = $service->getAvailability($day, $stationA);
$availB = $stationB ? $service->getAvailability($day, $stationB) : null;
$hqAvail = $service->getAvailability($day, null);

$result = $stations->enableApi($stationA);
$keyOk = (bool) $stations->findByApiKey($result['plain_key']);

$depA->delete();
$depB?->delete();
$stations->disableApi($stationA->fresh());

echo json_encode([
    'ok' => $isolationOk && $keyOk && $depA->station_id === $stationA->id,
    'isolation_ok' => $isolationOk,
    'api_key_ok' => $keyOk,
    'station_a' => $stationA->slug,
    'station_b' => $stationB?->slug,
    'avail_a_booked' => $availA['booked_bags'],
    'avail_b_booked' => $availB['booked_bags'] ?? null,
    'hq_booked' => $hqAvail['booked_bags'],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
