<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AllegatoCafPatronato;
use Illuminate\Support\Str;

$uid = (string) Str::ulid();
$path = 'sensitive://allegati_contratti/_smoke_caf_'.$uid.'.pdf';

$failedWithZero = false;
try {
    $bad = new AllegatoCafPatronato;
    $bad->path_filename = $path;
    $bad->filename_originale = 'smoke-zero.pdf';
    $bad->mime_type = 'application/pdf';
    $bad->dimensione_file = 10;
    $bad->uid = $uid;
    $bad->caf_patronato_id = 0;
    $bad->per_cliente = 0;
    $bad->save();
} catch (Throwable $e) {
    $failedWithZero = str_contains($e->getMessage(), '1452') || str_contains($e->getMessage(), 'Integrity constraint');
}

$ok = new AllegatoCafPatronato;
$ok->path_filename = $path;
$ok->filename_originale = 'smoke-null.pdf';
$ok->mime_type = 'application/pdf';
$ok->dimensione_file = 10;
$ok->uid = $uid;
$ok->caf_patronato_id = null;
$ok->per_cliente = 0;
$ok->save();

$id = $ok->id;
$ok->delete();

echo json_encode([
    'fk_zero_fails' => $failedWithZero,
    'null_insert_ok' => true,
    'smoke_id' => $id,
], JSON_UNESCAPED_UNICODE).PHP_EOL;
