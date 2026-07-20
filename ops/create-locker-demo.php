<?php

/**
 * Crea un pacco Locker Point demo in contesto admin (senza postazione agente).
 * Usage: php ops/create-locker-demo.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\LockerPackageService;
use App\Models\LockerSetting;
use Illuminate\Support\Facades\Schema;

if (! Schema::hasTable('locker_packages')) {
    fwrite(STDERR, "Tabella locker_packages assente — eseguire migrate\n");
    exit(1);
}

LockerSetting::singleton();

$service = app(LockerPackageService::class);
$pickup = today()->addDay()->toDateString();

// Admin desk create: station_id = null (visibile in admin senza filtro postazione)
$package = $service->create([
    'recipient_name' => 'Mario Rossi (DEMO)',
    'recipient_email' => 'mario.rossi.demo@example.com',
    'recipient_phone' => '+39 333 1234567',
    'sender_name' => 'Amazon Logistics',
    'sender_phone' => '+39 02 1234567',
    'carrier' => 'Amazon',
    'tracking_code' => 'DEMO-AMZ-'.strtoupper(substr(uniqid(), -6)),
    'expected_pickup_date' => $pickup,
    'notes' => 'Pacco demo admin — test accettazione/consegna Locker Point.',
], 'desk', null);

$base = rtrim(config('app.url'), '/');

echo "DEMO_OK\n";
echo "id={$package->id}\n";
echo "code={$package->code}\n";
echo "status={$package->status->value}\n";
echo "station=admin (global)\n";
echo "pickup={$pickup}\n";
echo "show={$base}/backend/locker-point/{$package->id}\n";
echo "intake={$base}/backend/locker-point/{$package->id}/accetta\n";
echo "dashboard={$base}/backend/locker-point/dashboard\n";
echo "elenco={$base}/backend/locker-point?view=prenotati\n";
echo "public_pickup={$base}/locker-point/ritiro/{$package->id}?token={$package->qr_token}\n";
