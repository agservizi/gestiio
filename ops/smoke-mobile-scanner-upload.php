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
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
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
    return [$status, $resp, $http_response_header ?? []];
}

// minimal 1x1 jpeg
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=');
$boundary = '----gestiio'.bin2hex(random_bytes(8));
$body = "--$boundary\r\nContent-Disposition: form-data; name=\"files\"; filename=\"scan-test.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n$jpeg\r\n--$boundary--\r\n";

[$st, $resp] = req(
    "$base/api/v1/mobile-scanner/upload/$session",
    'POST',
    $body,
    ["Content-Type: multipart/form-data; boundary=$boundary", 'Accept: application/json']
);
echo "UPLOAD => $st\n$resp\n---\n";

[$st, $resp] = req("$base/api/v1/mobile-scanner/validate-session/$session");
echo "VALIDATE => $st\n$resp\n---\n";

[$st, $resp] = req("$base/api/v1/mobile-scanner/files/$session");
echo "FILES => $st\n$resp\n---\n";

$files = json_decode((string) $resp, true);
$fileName = $files['files'][0]['fileName'] ?? $files['files'][0]['name'] ?? $files['files'][0]['filename'] ?? null;
$fileId = $files['files'][0]['id'] ?? $files['files'][0]['fileId'] ?? 0;
echo "fileName=$fileName fileId=$fileId\n";

$candidates = [];
if ($fileName) {
    $candidates[] = "$base/api/v1/mobile-scanner/download/$session/".rawurlencode($fileName);
    $candidates[] = "$base/api/v1/mobile-scanner/file/$session/".rawurlencode($fileName);
    $candidates[] = "$base/api/v1/mobile-scanner/files/$session/".rawurlencode($fileName);
}
$candidates[] = "$base/api/v1/mobile-scanner/download/$session/$fileId";
$candidates[] = "$base/api/v1/mobile-scanner/file/$session/$fileId";
$candidates[] = "$base/api/v1/mobile-scanner/get-file/$session/$fileId";
$candidates[] = "$base/api/v1/mobile-scanner/download-file/$session/$fileId";

foreach ($candidates as $url) {
    [$st, $bodyResp, $hdrs] = req($url);
    $ct = '';
    foreach ($hdrs as $h) {
        if (stripos($h, 'content-type:') === 0) {
            $ct = $h;
        }
    }
    echo "GET $url => $st ($ct) bytes=".strlen((string)$bodyResp)."\n";
    if ($st >= 200 && $st < 300 && strlen((string)$bodyResp) > 10) {
        echo "OK_DOWNLOAD\n";
        break;
    }
}

echo "SESSION=$session\n";
