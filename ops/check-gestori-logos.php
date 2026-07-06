<?php

require '/var/www/html/vendor/autoload.php';

$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Gestore::select('id', 'nome', 'logo')
    ->whereNotNull('logo')
    ->where('logo', '<>', '')
    ->get()
    ->each(function ($gestore): void {
        $path = ltrim((string) $gestore->logo, '/');
        $exists = Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? 'yes' : 'no';
        echo $gestore->id.'|'.$gestore->nome.'|'.$path.'|'.$exists.PHP_EOL;
    });
