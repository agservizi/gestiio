<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$path = storage_path('logs/laravel.log');
echo 'mtime='.date('c', filemtime($path))."\n";
$lines = file($path);
for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (str_contains($lines[$i], 'local.ERROR')) {
        echo $lines[$i];
        // print nearby message lines
        for ($j = $i; $j < min(count($lines), $i + 5); $j++) {
            if (str_contains($lines[$j], 'message') || str_contains($lines[$j], 'URL:') || str_contains($lines[$j], 'Exception')) {
                echo $lines[$j];
            }
        }
        break;
    }
}
