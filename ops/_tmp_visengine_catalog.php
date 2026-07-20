<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = new App\Http\Services\OpenApiVisureService;
$e = $s->elencoVisure();
if (! is_array($e)) {
    fwrite(STDERR, 'ERR: '.($s->message ?: 'null').PHP_EOL);
    exit(1);
}

echo 'TOP_COUNT='.count($e).PHP_EOL;
echo 'TOP_KEYS='.json_encode(array_keys($e)).PHP_EOL;

$cmd = new App\Console\Commands\SyncTipoVisuraOpenApiHash;
$ref = new ReflectionClass($cmd);
$m = $ref->getMethod('indicizzaVisureOpenApi');
$m->setAccessible(true);
$idx = $m->invoke($cmd, $e);
echo 'INDEXED='.count($idx).PHP_EOL;

$existing = App\Models\TipoVisura::query()->pluck('nome', 'openapi_hash_visura')->all();
echo 'DB_TIPI='.count($existing).PHP_EOL;

$missing = [];
foreach ($idx as $row) {
    $hash = $row['hash'];
    $found = false;
    foreach ($existing as $eh => $nome) {
        if ((string) $eh === (string) $hash) {
            $found = true;
            break;
        }
    }
    if (! $found) {
        // also check by nome
        foreach ($existing as $nome) {
            if (mb_strtolower(trim((string) $nome)) === mb_strtolower(trim($row['name']))) {
                $found = true;
                break;
            }
        }
    }
    if (! $found) {
        $missing[] = $row;
    }
}

echo 'MISSING='.count($missing).PHP_EOL;
foreach (array_slice($missing, 0, 40) as $row) {
    echo '- '.$row['name'].' | '.$row['hash'].PHP_EOL;
}
if (count($missing) > 40) {
    echo '... +'.(count($missing) - 40).' more'.PHP_EOL;
}

$mFlat = $ref->getMethod('flattenNodes');
$mFlat->setAccessible(true);
$nodes = $mFlat->invoke($cmd, $e);
echo 'SAMPLE_NODE='.json_encode($nodes[0] ?? null, JSON_UNESCAPED_UNICODE).PHP_EOL;
