<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Http\Services\StirlingMobileScannerService::class);
$created = $svc->createSession();
$sid = $created['sessionId'];
echo "SID=$sid\n";
echo "scanUrl={$created['scanUrl']}\n";

function hit($url) {
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return [$status, (string) $body];
}

[$st, $body] = hit("http://stirling-pdf:8080/pdf-tools/api/v1/mobile-scanner/validate-session/$sid");
echo "INTERNAL validate => $st $body\n";

[$st, $body] = hit("https://gestiio.agenziaplinio.it/pdf-tools/api/v1/mobile-scanner/validate-session/$sid");
echo "PUBLIC validate => $st $body\n";

[$st, $body] = hit("https://gestiio.agenziaplinio.it/pdf-tools/mobile-scanner?session=$sid");
echo "PUBLIC page => $st len=".strlen($body)."\n";

// simulate create via PUBLIC proxy (as Stirling iframe would)
$sid2 = (string) Illuminate\Support\Str::uuid();
$opts = [
    'http' => [
        'method' => 'POST',
        'timeout' => 15,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\nContent-Length: 0\r\n",
    ],
];
$url = "https://gestiio.agenziaplinio.it/pdf-tools/api/v1/mobile-scanner/create-session/$sid2";
$resp = @file_get_contents($url, false, stream_context_create($opts));
$status = 0;
if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
    $status = (int) $m[1];
}
echo "PUBLIC create => $status $resp\n";
[$st, $body] = hit("https://gestiio.agenziaplinio.it/pdf-tools/api/v1/mobile-scanner/validate-session/$sid2");
echo "PUBLIC validate after create => $st $body\n";
