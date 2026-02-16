<?php

declare(strict_types=1);

$targetFile = __DIR__ . '/../vendor/arcanedev/support/helpers.php';

if (! file_exists($targetFile)) {
    fwrite(STDOUT, "[fix-arcanedev] File non trovato, skip.\n");
    exit(0);
}

$content = file_get_contents($targetFile);

if ($content === false) {
    fwrite(STDERR, "[fix-arcanedev] Impossibile leggere il file target.\n");
    exit(1);
}

$legacySignature = 'function laravel_version(string $version = null)';
$patchedSignature = 'function laravel_version(?string $version = null)';

if (strpos($content, $patchedSignature) !== false) {
    fwrite(STDOUT, "[fix-arcanedev] Patch già presente.\n");
    exit(0);
}

if (strpos($content, $legacySignature) === false) {
    fwrite(STDOUT, "[fix-arcanedev] Signature attesa non trovata, skip.\n");
    exit(0);
}

$updated = str_replace($legacySignature, $patchedSignature, $content);

if ($updated === $content) {
    fwrite(STDOUT, "[fix-arcanedev] Nessuna modifica necessaria.\n");
    exit(0);
}

$result = file_put_contents($targetFile, $updated);

if ($result === false) {
    fwrite(STDERR, "[fix-arcanedev] Scrittura file fallita.\n");
    exit(1);
}

fwrite(STDOUT, "[fix-arcanedev] Patch applicata con successo.\n");
