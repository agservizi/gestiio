<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targets = [
    'intakePage',
    'intake',
    'dashboard',
    'pipeline',
];

foreach ($targets as $method) {
    try {
        $url = action([\App\Http\Controllers\Backend\LockerPackageController::class, $method], $method === 'intake' ? ['id' => 'x'] : []);
        echo "OK {$method} => {$url}\n";
    } catch (Throwable $e) {
        echo "FAIL {$method} => {$e->getMessage()}\n";
    }
}

$route = collect(app('router')->getRoutes())->first(function ($r) {
    return $r->uri() === 'backend/locker-point/accetta';
});

if ($route) {
    echo 'ROUTE_ACTION='.json_encode($route->getAction())."\n";
} else {
    echo "ROUTE_MISSING\n";
}
