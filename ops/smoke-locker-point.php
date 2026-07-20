<?php

/**
 * Smoke test Locker Point module.
 * Usage: php ops/smoke-locker-point.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Services\LockerPackageService;
use App\Models\LockerPackage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

$failures = 0;

function ok(string $msg): void
{
    echo "[OK] {$msg}\n";
}

function fail(string $msg): void
{
    global $failures;
    $failures++;
    echo "[FAIL] {$msg}\n";
}

if (! Schema::hasTable('locker_packages')) {
    fail('Tabella locker_packages assente — eseguire migrate');
    exit(1);
}

$service = app(LockerPackageService::class);

try {
    $package = $service->create([
        'recipient_name' => 'Smoke Test',
        'expected_pickup_date' => today()->toDateString(),
    ], 'api');
    ok('Book API/desk create: '.$package->code);
} catch (Throwable $e) {
    fail('Create: '.$e->getMessage());
    exit(1);
}

try {
    $service->acceptIntake($package, UploadedFile::fake()->image('intake.jpg'));
    ok('Intake con foto → IN_GIACENZA');
} catch (Throwable $e) {
    fail('Intake: '.$e->getMessage());
}

$fresh = LockerPackage::find($package->id);
if ($fresh && $fresh->status->value === 'IN_GIACENZA' && $fresh->photo_path) {
    ok('Stato e photo_path persistiti');
} else {
    fail('Stato/photo_path non corretti dopo intake');
}

try {
    $service->completePickup($fresh, 'Contanti', [], 'data:image/png;base64,invalid', '');
    fail('Complete senza scan/firma valida doveva fallire');
} catch (Throwable $e) {
    ok('Complete bloccato senza scan/firma: '.$e->getMessage());
}

$service->scanPickupTag($fresh, $fresh->code);
$png = base64_encode(str_repeat("\0", 200)."\x89PNG\r\n\x1a\n".str_repeat('x', 100));
try {
    $result = $service->completePickup(
        $fresh->fresh(),
        'Contanti',
        [$fresh->code],
        'data:image/png;base64,'.$png,
        'Smoke Signer'
    );
    if ($result['package']->status->value === 'CONSEGNATO') {
        ok('Complete con scan+firma → CONSEGNATO');
    } else {
        fail('Stato finale non CONSEGNATO');
    }
} catch (Throwable $e) {
    fail('Complete: '.$e->getMessage());
}

// cleanup
LockerPackage::where('recipient_name', 'Smoke Test')->delete();

echo $failures === 0 ? "\nSmoke PASSED\n" : "\nSmoke FAILED ({$failures})\n";
exit($failures === 0 ? 0 : 1);
