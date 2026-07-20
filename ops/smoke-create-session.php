<?php
$base = 'http://stirling-pdf:8080/pdf-tools';
$session = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

function req($url, $method = 'GET', $body = null, $headers = []) {
    $opts = [
        'http' => [
            'method' => $method,
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => implode("\r\n", array_merge(['Accept: application/json'], $headers)),
        ],
    ];
    if ($body !== null) {
        $opts['http']['content'] = $body;
    }
    $resp = @file_get_contents($url, false, stream_context_create($opts));
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    return [$status, $resp];
}

echo "SESSION=$session\n";
$tests = [
    ['POST', "/api/v1/mobile-scanner/create-session/", null],
    ['POST', "/api/v1/mobile-scanner/create-session/$session", null],
    ['POST', "/api/v1/mobile-scanner/create-session/$session/", null],
    ['GET', "/api/v1/mobile-scanner/create-session/", null],
    ['GET', "/api/v1/mobile-scanner/create-session/$session", null],
    ['POST', "/api/v1/mobile-scanner/create-session/", '{"sessionId":"'.$session.'"}'],
];
foreach ($tests as [$m, $p, $b]) {
    $h = $b !== null ? ['Content-Type: application/json'] : [];
    [$st, $r] = req($base.$p, $m, $b, $h);
    echo "$m $p => $st ".substr((string)$r, 0, 250)."\n";
}
[$st, $r] = req("$base/api/v1/mobile-scanner/validate-session/$session");
echo "validate => $st $r\n";
