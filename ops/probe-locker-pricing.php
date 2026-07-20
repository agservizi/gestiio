<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = (string) config('locker.api_key');
$request = Illuminate\Http\Request::create(
    '/api/public/locker-point/pricing',
    'GET',
    [],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_API_KEY' => $key,
    ]
);

$response = $kernel->handle($request);
echo 'status='.$response->getStatusCode()."\n";
echo 'body='.substr($response->getContent(), 0, 300)."\n";
echo 'cfg_len='.strlen($key)."\n";
