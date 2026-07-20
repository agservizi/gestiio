<?php
$ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
$html = file_get_contents('https://gestiio.agenziaplinio.it/pdf-tools/mobile-scanner?session=test', false, $ctx);
echo "page_len=".strlen((string)$html)."\n";
if (preg_match('/STIRLING_PDF_API_BASE_URL\s*=\s*[\'\"]([^\'\"]+)/', (string)$html, $m)) {
    echo "API_BASE={$m[1]}\n";
}
if (preg_match('/<base href="([^"]+)"/', (string)$html, $m)) {
    echo "BASE_HREF={$m[1]}\n";
}
if (preg_match_all('/src="(\.?\/?assets\/[^"]+\.js)"/', (string)$html, $mm)) {
    foreach ($mm[1] as $src) {
        $url = (str_starts_with($src, 'http') ? $src : 'https://gestiio.agenziaplinio.it/pdf-tools/'.ltrim($src, './'));
        echo "JS=$url\n";
        $js = file_get_contents($url, false, $ctx);
        // snippets around validate-session
        $pos = strpos((string)$js, 'validate-session');
        if ($pos !== false) {
            echo "CONTEXT=".substr((string)$js, max(0, $pos - 120), 280)."\n";
        }
        if (preg_match('/API_BASE[^,]{0,80}/', (string)$js, $m2)) {
            echo "API_BASE_USAGE={$m2[0]}\n";
        }
        if (preg_match('/STIRLING_PDF_API_BASE_URL.{0,120}/', (string)$js, $m3)) {
            echo "WIN_API={$m3[0]}\n";
        }
    }
}
