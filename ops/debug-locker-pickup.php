<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LockerPackage;

$id = $argv[1] ?? '01KXNHS3VF3MJVXDMT06B0B3F2';
$token = $argv[2] ?? '8a059888-a3d6-4bbe-9704-6f15625696f2';

$p = LockerPackage::find($id);
if (! $p) {
    echo "FOUND=no\n";
    exit(0);
}

echo "FOUND=yes\n";
echo 'code='.$p->code."\n";
echo 'status='.$p->status->value."\n";
echo 'token_match='.(hash_equals((string) $p->qr_token, $token) ? 'yes' : 'no')."\n";
echo 'token_db='.$p->qr_token."\n";
echo 'has_photo='.($p->photo_path ? 'yes' : 'no')."\n";
echo 'pickupUrl='.$p->pickupUrl()."\n";
