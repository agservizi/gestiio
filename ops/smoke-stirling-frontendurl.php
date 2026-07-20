<?php
$url = 'http://stirling-pdf:8080/pdf-tools/api/v1/config/app-config';
$j = json_decode((string) file_get_contents($url));
echo 'frontendUrl=' . ($j->frontendUrl ?? 'NULL') . PHP_EOL;
echo 'enableMobileScanner=' . json_encode($j->enableMobileScanner ?? null) . PHP_EOL;
echo 'contextPath=' . ($j->contextPath ?? 'NULL') . PHP_EOL;
echo 'baseUrl=' . ($j->baseUrl ?? 'NULL') . PHP_EOL;
