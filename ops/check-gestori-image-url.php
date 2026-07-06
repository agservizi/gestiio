<?php

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Gestore::select('id', 'nome', 'logo')
    ->whereNotNull('logo')
    ->where('logo', '<>', '')
    ->take(5)
    ->get()
    ->each(function ($gestore): void {
        echo $gestore->id.'|'.$gestore->nome.'|'.$gestore->immagineLogo().PHP_EOL;
    });
