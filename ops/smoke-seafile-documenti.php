#!/usr/bin/env php
<?php
/**
 * Smoke Documenti / Seafile (da gestiio-app).
 * Exit 0 = OK.
 */
$base = rtrim(getenv('SEAFILE_URL') ?: 'http://seafile', '/');
$adminEmail = getenv('SEAFILE_ADMIN_EMAIL') ?: 'admin@gestiio.local';
$adminPass = getenv('SEAFILE_ADMIN_PASSWORD') ?: '';
$agenteEmail = getenv('SEAFILE_AGENTE_EMAIL') ?: 'agente-ro@gestiio.local';
$agentePass = getenv('SEAFILE_AGENTE_PASSWORD') ?: '';
$repoId = getenv('SEAFILE_REPO_ID') ?: '';
$failures = 0;

function hit(string $url, string $method = 'GET', array $opts = []): array
{
    $headers = $opts['headers'] ?? ["Accept: */*\r\n"];
    $content = $opts['body'] ?? null;
    $ctx = stream_context_create([
        'http' => [
            'method' => $method,
            'timeout' => 30,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => is_array($headers) ? implode('', $headers) : $headers,
            'content' => $content,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $h, $m)) {
            $status = (int) $m[1];
        }
    }

    return [$status, (string) $body];
}

function ok(string $label, bool $pass, string $detail = ''): void
{
    global $failures;
    echo ($pass ? 'OK  ' : 'FAIL')." $label".($detail !== '' ? " — $detail" : '')."\n";
    if (! $pass) {
        $failures++;
    }
}

[$st] = hit($base.'/');
ok('Seafile HTTP up', $st > 0 && $st < 500, "status=$st");

if ($adminPass === '') {
    ok('SEAFILE_ADMIN_PASSWORD set', false, 'env vuota');
    echo "SMOKE_SEAFILE_FAIL count=$failures\n";
    exit(1);
}

$tokenBody = http_build_query(['username' => $adminEmail, 'password' => $adminPass]);
[$st, $body] = hit($base.'/api2/auth-token/', 'POST', [
    'headers' => ["Content-Type: application/x-www-form-urlencoded\r\n", "Accept: application/json\r\n"],
    'body' => $tokenBody,
]);
$token = '';
if (preg_match('/"token"\s*:\s*"([^"]+)"/', $body, $m)) {
    $token = $m[1];
}
ok('Admin auth-token', $st === 200 && $token !== '', "status=$st");

if ($token !== '' && $repoId !== '') {
    [$st, $body] = hit($base.'/api2/repos/'.$repoId.'/dir/?p=/', 'GET', [
        'headers' => ["Authorization: Token $token\r\n", "Accept: application/json\r\n"],
    ]);
    ok('Admin legge library root', $st === 200, "status=$st");
}

if ($agentePass !== '') {
    $tokenBody = http_build_query(['username' => $agenteEmail, 'password' => $agentePass]);
    [$st, $body] = hit($base.'/api2/auth-token/', 'POST', [
        'headers' => ["Content-Type: application/x-www-form-urlencoded\r\n", "Accept: application/json\r\n"],
        'body' => $tokenBody,
    ]);
    $agenteToken = '';
    if (preg_match('/"token"\s*:\s*"([^"]+)"/', $body, $m)) {
        $agenteToken = $m[1];
    }
    ok('Agente auth-token', $st === 200 && $agenteToken !== '', "status=$st");

    if ($agenteToken !== '' && $repoId !== '') {
        // Agente non deve poter creare cartelle (RO)
        [$st] = hit($base.'/api2/repos/'.$repoId.'/dir/?p=/__smoke_ro_test__', 'POST', [
            'headers' => [
                "Authorization: Token $agenteToken\r\n",
                "Content-Type: application/x-www-form-urlencoded\r\n",
            ],
            'body' => 'operation=mkdir',
        ]);
        ok('Agente non può mkdir (RO)', $st === 403 || $st === 401 || $st === 404 || $st >= 400, "status=$st");
    }
}

// Locale: pagina login in italiano
[$st, $body] = hit($base.'/accounts/login/');
$itHints = (stripos($body, 'Password') !== false || stripos($body, 'password') !== false)
    && (stripos($body, 'lang="it"') !== false
        || stripos($body, 'Italiano') !== false
        || stripos($body, 'Accedi') !== false
        || stripos($body, 'django_language=it') !== false
        || stripos($body, 'login') !== false);
ok('Login page raggiungibile (locale IT da verificare in UI)', $st === 200, "status=$st");

echo $failures === 0 ? "SMOKE_SEAFILE_OK\n" : "SMOKE_SEAFILE_FAIL count=$failures\n";
exit($failures === 0 ? 0 : 1);
