<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

$admin = User::permission('admin')->first() ?: User::role('admin')->first();
Auth::login($admin);

$html = View::make('Backend._layout.app-sidebar-menu')->render();

$pos = strpos($html, 'menu-title">Impostazioni');
echo 'pos='.$pos."\n";
$slice = substr($html, $pos, 80000);
echo 'slice_len='.strlen($slice)."\n";
echo 'IN_IMPOSTAZIONI_LOCKER='.(str_contains($slice, 'Locker Point') ? 'YES' : 'NO')."\n";
echo 'IN_IMPOSTAZIONI_DEPOSITO='.(str_contains($slice, 'Deposito Bagagli') ? 'YES' : 'NO')."\n";
echo 'IN_IMPOSTAZIONI_EBIKE='.(str_contains($slice, 'Ebike B2B') ? 'YES' : 'NO')."\n";

// Find end of Impostazioni accordion roughly
$ebikePos = strpos($slice, 'Ebike B2B');
echo 'ebike_offset='.($ebikePos === false ? 'NONE' : $ebikePos)."\n";
if ($ebikePos !== false) {
    echo substr($slice, max(0, $ebikePos - 800), 1600)."\n";
}
