<?php
$html = file_get_contents('http://stirling-pdf:8080/pdf-tools/');
echo "len=".strlen($html)."\n";
foreach (['Developer', 'developer', 'sviluppator', 'Show Javascript', 'dev-api', 'air-gapped', 'folder-scanning', 'SSO'] as $n) {
    echo $n.':'.(stripos($html, $n) !== false ? 'YES' : 'no')."\n";
}
// dump nearby context for developer
$pos = stripos($html, 'Developer');
if ($pos === false) $pos = stripos($html, 'svilupp');
if ($pos !== false) {
    echo "---context---\n";
    echo substr($html, max(0, $pos - 200), 600)."\n";
}
