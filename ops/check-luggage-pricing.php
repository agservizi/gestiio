<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = (string) config('luggage.api_key');
$request = Illuminate\Http\Request::create('/api/public/deposito-bagagli/pricing', 'GET');
$request->headers->set('x-api-key', $key);
$response = $app->handle($request);
echo 'status='.$response->getStatusCode().PHP_EOL;
echo 'success='.(str_contains($response->getContent(), '"success":true') ? 'yes' : 'no').PHP_EOL;
