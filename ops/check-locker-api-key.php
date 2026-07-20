<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$env = (string) env('LOCKER_API_KEY');
$cfg = (string) config('locker.api_key');

echo 'len_env='.strlen($env)."\n";
echo 'len_cfg='.strlen($cfg)."\n";
echo 'prefix_env='.substr($env, 0, 8)."\n";
echo 'prefix_cfg='.substr($cfg, 0, 8)."\n";
echo 'match='.(($env !== '' && hash_equals($env, $cfg)) ? 'yes' : 'no')."\n";
