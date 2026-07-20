<?php
$ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
$docs = file_get_contents('http://stirling-pdf:8080/pdf-tools/v3/api-docs', false, $ctx);
echo $docs, "\n----\n";

$html = file_get_contents('http://stirling-pdf:8080/pdf-tools/', false, $ctx);
if (preg_match_all('#src="(\./assets/[^"]+\.js)"#', $html, $m)) {
    foreach ($m[1] as $src) {
        $url = 'http://stirling-pdf:8080/pdf-tools/'.ltrim($src, './');
        echo "ASSET $url\n";
        $js = file_get_contents($url, false, $ctx);
        // find mobile-scanner related strings
        if (preg_match_all('#mobile-scanner/[a-zA-Z0-9_{}/-]+#', $js, $mm)) {
            foreach (array_unique($mm[0]) as $p) {
                echo "JS_PATH $p\n";
            }
        }
        if (stripos($js, 'createSession') !== false || stripos($js, 'create-session') !== false) {
            echo "HAS_CREATE_SESSION in $src\n";
        }
        if (preg_match_all('#[\'\"](/api/v1/mobile-scanner[^\'\"]+)[\'\"]#', $js, $mm)) {
            foreach (array_unique($mm[0]) as $p) {
                echo "LIT $p\n";
            }
        }
    }
}
