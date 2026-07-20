<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$req = Illuminate\Http\Request::create(
    '/locker-point/ritiro/01KXNHS3VF3MJVXDMT06B0B3F2',
    'GET',
    ['t' => '8a059888-a3d6-4bbe-9704-6f15625696f2']
);
$res = $kernel->handle($req);

echo 'status='.$res->getStatusCode()."\n";
echo 'route='.(optional($req->route())->uri() ?? 'none')."\n";
echo 'action='.(optional($req->route())->getActionName() ?? 'none')."\n";
$body = $res->getContent();
echo 'has_waiting='.(str_contains($body, 'non ancora in giacenza') ? 'yes' : 'no')."\n";
echo 'has_not_found='.(str_contains($body, 'Not Found') ? 'yes' : 'no')."\n";
echo 'snippet='.substr(trim(preg_replace('/\s+/', ' ', strip_tags($body))), 0, 180)."\n";
