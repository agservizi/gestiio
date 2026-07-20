<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Backend\CafPatronatoController;
use App\Models\AllegatoCafPatronato;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

$user = User::permission('admin')->first()
    ?: User::role('admin')->first()
    ?: User::find(2);
Auth::login($user);

$uid = (string) Str::ulid();
$tmp = tempnam(sys_get_temp_dir(), 'caf');
file_put_contents($tmp, "%PDF-1.4 smoke-caf-upload");
$upload = new UploadedFile($tmp, 'smoke-caf.pdf', 'application/pdf', null, true);

$request = Request::create('/backend/allegato-caf', 'POST', [
    'uid' => $uid,
    'caf_patronato_id' => '0',
    'per_cliente' => 0,
], [], [
    'file' => $upload,
]);

try {
    $response = app(CafPatronatoController::class)->uploadAllegato($request);
    $status = $response->getStatusCode();
    $body = json_decode($response->getContent(), true);
} catch (Throwable $e) {
    $status = 500;
    $body = ['message' => $e->getMessage()];
}

$created = AllegatoCafPatronato::where('uid', $uid)->first();
$result = [
    'status' => $status,
    'body' => $body,
    'created_id' => $created?->id,
    'created_caf_id' => $created?->caf_patronato_id,
];

if ($created) {
    app(\App\Http\Services\SensitiveFileService::class)->delete($created->path_filename);
    $created->delete();
    $result['cleaned'] = true;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
@unlink($tmp);
