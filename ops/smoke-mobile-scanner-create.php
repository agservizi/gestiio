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
[$st, $r] = req("$base/api/v1/mobile-scanner/validate-session/$session");
echo "1 validate => $st $r\n";
[$st, $r] = req("$base/api/v1/mobile-scanner/files/$session");
echo "2 files => $st $r\n";
[$st, $r] = req("$base/api/v1/mobile-scanner/validate-session/$session");
echo "3 validate after files => $st $r\n";

// try PUT/POST register variants
foreach ([
    ['POST', "/api/v1/mobile-scanner/register/$session", null],
    ['POST', "/api/v1/mobile-scanner/init/$session", null],
    ['PUT', "/api/v1/mobile-scanner/session/$session", '{}'],
    ['POST', "/api/v1/mobile-scanner/session/$session/init", null],
    ['POST', "/api/v1/mobile-scanner/create", '{"sessionId":"'.$session.'"}'],
    ['POST', '/api/v1/mobile-scanner/start-session', '{"sessionId":"'.$session.'"}'],
    ['POST', '/api/v1/mobile-scanner/new-session', null],
    ['GET', "/api/v1/mobile-scanner/start/$session", null],
    ['POST', "/api/v1/mobile-scanner/wait/$session", null],
] as $row) {
    [$m, $p, $b] = $row;
    $h = $b !== null ? ['Content-Type: application/json'] : [];
    [$st, $r] = req($base.$p, $m, $b, $h);
    echo "$m $p => $st ".substr((string)$r, 0, 180)."\n";
}

[$st, $r] = req("$base/api/v1/mobile-scanner/validate-session/$session");
echo "final validate => $st $r\n";
