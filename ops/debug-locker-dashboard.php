<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$admin = User::permission('admin')->first() ?: User::role('admin')->first();
if (! $admin) {
    echo "NO_ADMIN\n";
    exit(1);
}
Auth::login($admin);

try {
    $request = Illuminate\Http\Request::create('/backend/locker-point/dashboard', 'GET');
    $response = $app->handle($request);
    echo 'STATUS='.$response->getStatusCode()."\n";
    if ($response->getStatusCode() >= 400) {
        echo substr((string) $response->getContent(), 0, 1500)."\n";
    } else {
        echo 'LEN='.strlen((string) $response->getContent())."\n";
    }
} catch (Throwable $e) {
    echo 'EX='.get_class($e).': '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
