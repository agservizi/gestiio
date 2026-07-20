<?php
$url = 'http://stirling-pdf:8080/pdf-tools/';
$ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
$body = @file_get_contents($url, false, $ctx);
$headers = $http_response_header ?? [];
echo 'headers=' . json_encode($headers) . PHP_EOL;
echo 'bytes=' . ($body === false ? 0 : strlen($body)) . PHP_EOL;
echo ($body !== false && strlen($body) > 0) ? "STIRLING_REACHABLE\n" : "STIRLING_FAIL\n";
