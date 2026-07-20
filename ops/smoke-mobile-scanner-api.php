<?php
$base = 'http://stirling-pdf:8080/pdf-tools';
$ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);

function hit($url, $method = 'GET', $body = null, $headers = []) {
    global $ctx;
    $opts = [
        'http' => [
            'method' => $method,
            'timeout' => 8,
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
    echo "$method $url => $status\n";
    echo substr((string) $resp, 0, 400)."\n---\n";
    return [$status, $resp];
}

$session = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$paths = [
    ['POST', '/api/v1/mobile-scanner/create-session', '{}'],
    ['POST', '/api/v1/mobile-scanner/session', '{}'],
    ['POST', '/api/v1/mobile-scanner/sessions', '{}'],
    ['GET', '/api/v1/mobile-scanner/create-session', null],
    ['POST', "/api/v1/mobile-scanner/session/$session", '{}'],
    ['GET', "/api/v1/mobile-scanner/validate-session/$session", null],
    ['GET', "/api/v1/mobile-scanner/session/$session", null],
    ['GET', "/api/v1/mobile-scanner/session/$session/files", null],
    ['GET', "/api/v1/mobile-scanner/files/$session", null],
    ['GET', "/api/v1/mobile-scanner/get-files/$session", null],
    ['GET', "/api/v1/mobile-scanner/$session/files", null],
];

foreach ($paths as [$method, $path, $body]) {
    $headers = [];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
    }
    hit($base.$path, $method, $body, $headers);
}

echo "SESSION=$session\n";
