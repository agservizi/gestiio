<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = $app->make(App\Http\Services\StirlingMobileScannerService::class);
$created = $svc->createSession();
echo "sessionId=".$created['sessionId']."\n";
echo "scanUrl=".$created['scanUrl']."\n";
echo "public_ok=".(str_contains($created['scanUrl'], 'gestiio.agenziaplinio.it') && !str_contains($created['scanUrl'], 'stirling-pdf') ? 'yes' : 'no')."\n";

$listed = $svc->listFiles($created['sessionId']);
echo "count=".$listed['count']."\n";

// upload a tiny jpeg into the session via Stirling
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=');
$sid = $created['sessionId'];
$boundary = '----gestiio'.bin2hex(random_bytes(4));
$body = "--$boundary\r\nContent-Disposition: form-data; name=\"files\"; filename=\"scan-test.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n$jpeg\r\n--$boundary--\r\n";
$ctx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\n",
    'content' => $body,
    'timeout' => 20,
    'ignore_errors' => true,
]]);
$up = file_get_contents('http://stirling-pdf:8080/pdf-tools/api/v1/mobile-scanner/upload/'.$sid, false, $ctx);
echo "upload=".substr((string)$up, 0, 120)."\n";

$listed = $svc->listFiles($sid);
echo "count_after=".$listed['count']."\n";
if ($listed['count'] > 0) {
    $rel = $svc->downloadToTemp($sid, $listed['files'][0]['filename']);
    echo "temp=$rel bytes=".filesize(Storage::disk('local')->path($rel))."\n";
}
$svc->deleteSession($sid);
echo "deleted=ok\n";
echo "SMOKE_OK\n";
