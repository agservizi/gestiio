<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$key = (string) config('luggage.api_key');
echo 'configured='.($key !== '' ? 'yes' : 'no').PHP_EOL;
echo 'length='.strlen($key).PHP_EOL;
