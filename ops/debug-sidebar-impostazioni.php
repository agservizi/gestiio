<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

$admin = User::permission('admin')->first()
    ?: User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'Admin', 'Administrator']))->first()
    ?: User::query()->orderBy('id')->first();
if (! $admin) {
    fwrite(STDERR, "NO_USER\n");
    exit(1);
}
echo 'USER='.$admin->id.' '.$admin->email."\n";
Auth::login($admin);

$html = View::make('Backend._layout.app-sidebar-menu')->render();

echo 'HAS_SEZIONI='.(str_contains($html, 'sezioni') ? 'YES' : 'NO')."\n";
echo 'HAS_FLAT_SETTINGS_LINK='.(str_contains($html, "locker-point/settings\">") || str_contains($html, "locker-point/settings'") ? 'YES' : 'NO')."\n";
echo 'COUNT_LOCKER_POINT='.substr_count($html, 'Locker Point')."\n";

$p = strpos($html, 'Controlli contratti');
echo "--- Controlli contratti slice ---\n";
echo substr($html, $p, 1500)."\n";
