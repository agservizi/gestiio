#!/usr/bin/env php
<?php
/**
 * Smoke pubblico PDF Tools / mobile-scanner (da eseguire in gestiio-app o host con rete).
 * Exit 0 = OK.
 */
$public = getenv('STIRLING_PUBLIC_URL') ?: 'https://gestiio.agenziaplinio.it/pdf-tools';
$public = rtrim($public, '/');
$failures = 0;

function hit(string $url, string $method = 'GET'): array
{
    $opts = [
        'http' => [
            'method' => $method,
            'timeout' => 20,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => "Accept: */*\r\n",
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    $body = @file_get_contents($url, false, stream_context_create($opts));
    $status = 0;
    $location = '';
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $h, $m)) {
            $status = (int) $m[1];
        }
        if (stripos($h, 'Location:') === 0) {
            $location = trim(substr($h, 9));
        }
    }

    return [$status, (string) $body, $location];
}

function ok(string $label, bool $pass, string $detail = ''): void
{
    global $failures;
    echo ($pass ? 'OK  ' : 'FAIL')." $label".($detail !== '' ? " — $detail" : '')."\n";
    if (! $pass) {
        $failures++;
    }
}

// 1) Nessun redirect http:// sul trailing slash (o follow interno già risolto)
[$st, $body, $loc] = hit($public.'/');
$locHttp = $loc !== '' && stripos($loc, 'http://') === 0;
ok('GET /pdf-tools/ non degrada a http Location', ! $locHttp, "status=$st location=$loc");

// 2) mobile-scanner page pubblica
[$st, $body] = hit($public.'/mobile-scanner?session=00000000-0000-4000-8000-000000000001');
ok('GET mobile-scanner pubblico', $st === 200 && $st !== 302, "status=$st");

// 3) create + validate session via API pubblica
$session = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0, 0x0fff) | 0x4000,
    random_int(0, 0x3fff) | 0x8000,
    random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0, 0xffff)
);
[$st, $body] = hit($public.'/api/v1/mobile-scanner/create-session/'.$session, 'POST');
ok('POST create-session', $st === 200 && str_contains($body, $session), "status=$st");
[$st, $body] = hit($public.'/api/v1/mobile-scanner/validate-session/'.$session);
ok('GET validate-session', $st === 200 && str_contains($body, '"valid":true'), "status=$st body=".substr($body, 0, 120));

// 4) frontendUrl in config
[$st, $body] = hit($public.'/api/v1/config/app-config');
ok(
    'config frontendUrl pubblico',
    $st === 200 && str_contains($body, 'gestiio.agenziaplinio.it') && ! str_contains($body, 'stirling-pdf'),
    "status=$st"
);

// cleanup
hit($public.'/api/v1/mobile-scanner/session/'.$session, 'DELETE');

echo $failures === 0 ? "SMOKE_PDF_TOOLS_OK\n" : "SMOKE_PDF_TOOLS_FAIL count=$failures\n";
exit($failures === 0 ? 0 : 1);
