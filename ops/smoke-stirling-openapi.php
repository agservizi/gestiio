<?php
$urls = [
    'http://stirling-pdf:8080/pdf-tools/v3/api-docs',
    'http://stirling-pdf:8080/pdf-tools/v3/api-docs.yaml',
    'http://stirling-pdf:8080/pdf-tools/api-docs',
    'http://stirling-pdf:8080/pdf-tools/v1/api-docs',
    'http://stirling-pdf:8080/pdf-tools/swagger-ui/index.html',
];
foreach ($urls as $u) {
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $body = @file_get_contents($u, false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    echo "$status $u len=".strlen((string)$body)."\n";
    if ($status === 200 && $body && stripos($body, 'mobile-scanner') !== false) {
        // extract mobile-scanner paths
        if (preg_match_all('#/api/v1/mobile-scanner[^"\']+#', $body, $m)) {
            foreach (array_unique($m[0]) as $p) {
                echo "PATH $p\n";
            }
        }
    }
}
