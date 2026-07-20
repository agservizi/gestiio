<?php

namespace App\Console\Commands;

use App\Http\Services\OpenApiVisureService;
use App\Models\TipoVisura;
use Illuminate\Console\Command;

class SyncTipoVisuraOpenApiHash extends Command
{
    protected $signature = 'visure:sync-openapi-hash
                            {--dry-run : Mostra solo mapping senza salvare}
                            {--force : Sovrascrive anche hash gia valorizzati}
                            {--import-missing : Crea tipi_visure per servizi Visengine assenti in Gestiio}
                            {--default-prezzo-agente=15 : Prezzo agente Gestiio di default per import}
                            {--default-prezzo-cliente=25 : Prezzo cliente Gestiio di default per import}
                            {--debug : Mostra struttura dati OpenAPI per diagnosi}';

    protected $description = 'Allinea tipi_visure a Visengine (hash + eventuale import prodotti mancanti)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $importMissing = (bool) $this->option('import-missing');

        $service = new OpenApiVisureService;
        $elenco = $service->elencoVisure();

        if (! $elenco) {
            $this->error($service->message ?: 'Nessuna visura ricevuta da OpenAPI.');

            return self::FAILURE;
        }

        $indicizzato = $this->indicizzaVisureOpenApi($elenco);
        if (empty($indicizzato)) {
            if ((bool) $this->option('debug')) {
                $this->warn('DEBUG struttura risposta OpenAPI (prime 5 voci):');
                $preview = array_slice($this->flattenNodes($elenco), 0, 5);
                $this->line(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            $this->error('Elenco OpenAPI ricevuto ma non interpretabile (nome/hash mancanti).');

            return self::FAILURE;
        }

        $this->line('Servizi Visengine indicizzati: '.count($indicizzato));

        $records = TipoVisura::query()->orderBy('id')->get();
        $updated = 0;
        $skipped = 0;
        $notMatched = 0;

        foreach ($records as $record) {
            $current = trim((string) $record->openapi_hash_visura);
            if ($current !== '' && ! $force) {
                $skipped++;
                $this->line("SKIP #{$record->id} {$record->nome} (hash gia presente)");

                continue;
            }

            $match = $this->matchHashByNome((string) $record->nome, $indicizzato);
            if (! $match) {
                $notMatched++;
                $this->warn("NO MATCH #{$record->id} {$record->nome}");

                continue;
            }

            $hash = $match['hash'];
            $sourceName = $match['name'];

            $this->info("MATCH #{$record->id} {$record->nome} -> {$sourceName} ({$hash})");
            $record->openapi_hash_visura = $hash;
            $updated++;

            if (! $dryRun) {
                $record->save();
            }
        }

        $imported = 0;
        if ($importMissing) {
            $imported = $this->importMissingTipi($indicizzato, $dryRun);
        }

        $this->newLine();
        $this->line('Tipi Gestiio: '.$records->count());
        $this->line("Hash aggiornati: {$updated}");
        $this->line("Saltati (gia valorizzati): {$skipped}");
        $this->line("Senza match: {$notMatched}");
        if ($importMissing) {
            $this->line("Importati da Visengine: {$imported}");
        }
        $this->line($dryRun ? 'Modalita dry-run: nessun salvataggio eseguito.' : 'Salvataggi completati.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{name:string, normalized:string, hash:string, price:?float, tipo_visura:string}>  $indicizzato
     */
    protected function importMissingTipi(array $indicizzato, bool $dryRun): int
    {
        $existingHashes = TipoVisura::query()
            ->whereNotNull('openapi_hash_visura')
            ->pluck('openapi_hash_visura')
            ->map(fn ($h) => trim((string) $h))
            ->filter()
            ->all();
        $existingHashes = array_fill_keys($existingHashes, true);

        $existingNames = TipoVisura::query()
            ->pluck('nome')
            ->map(fn ($n) => $this->normalize((string) $n))
            ->filter()
            ->all();
        $existingNames = array_fill_keys($existingNames, true);

        $defaultAgente = (float) $this->option('default-prezzo-agente');
        $defaultCliente = (float) $this->option('default-prezzo-cliente');
        $imported = 0;

        foreach ($indicizzato as $row) {
            $hash = trim((string) $row['hash']);
            $name = trim((string) $row['name']);
            if ($hash === '' || $name === '') {
                continue;
            }
            if (isset($existingHashes[$hash])) {
                continue;
            }
            $norm = $this->normalize($name);
            if ($norm !== '' && isset($existingNames[$norm])) {
                // Stesso nome senza hash: aggiorna hash sul record esistente
                $tipo = TipoVisura::query()->get()->first(function (TipoVisura $t) use ($norm) {
                    return $this->normalize((string) $t->nome) === $norm;
                });
                if ($tipo && trim((string) $tipo->openapi_hash_visura) === '') {
                    $this->info("LINK hash a tipo esistente #{$tipo->id} {$tipo->nome} -> {$hash}");
                    if (! $dryRun) {
                        $tipo->openapi_hash_visura = $hash;
                        $tipo->save();
                    }
                    $existingHashes[$hash] = true;
                }

                continue;
            }

            $prezzoOpenapi = $row['price'] ?? null;
            $prezzoAgente = $prezzoOpenapi !== null ? round($prezzoOpenapi * 1.5, 2) : $defaultAgente;
            $prezzoCliente = $prezzoOpenapi !== null ? round($prezzoOpenapi * 2, 2) : $defaultCliente;
            if ($prezzoAgente <= 0) {
                $prezzoAgente = $defaultAgente;
            }
            if ($prezzoCliente <= 0) {
                $prezzoCliente = $defaultCliente;
            }

            $this->info("IMPORT {$name} ({$hash}) agente={$prezzoAgente} cliente={$prezzoCliente} tipo={$row['tipo_visura']}");
            $imported++;
            $existingHashes[$hash] = true;
            $existingNames[$norm] = true;

            if ($dryRun) {
                continue;
            }

            $tipo = new TipoVisura;
            $tipo->nome = $name;
            $tipo->prezzo_agente = $prezzoAgente;
            $tipo->prezzo_cliente = $prezzoCliente;
            $tipo->abilitato = 1;
            $tipo->richiedi_allegati = 0;
            $tipo->tipo_visura = $row['tipo_visura'] ?? 'privato';
            $tipo->openapi_hash_visura = $hash;
            $tipo->html = null;
            $tipo->save();
        }

        return $imported;
    }

    /**
     * @return array<int, array{name:string, normalized:string, hash:string, price:?float, tipo_visura:string}>
     */
    protected function indicizzaVisureOpenApi(array $elenco): array
    {
        $out = [];
        $nodes = $this->flattenNodes($elenco);
        foreach ($nodes as $item) {
            if (! is_array($item) || empty($item)) {
                continue;
            }

            $name = $this->extractName($item);
            $hash = $this->extractHash($item);
            if ($name === '' || $hash === '') {
                continue;
            }

            $out[] = [
                'name' => $name,
                'normalized' => $this->normalize($name),
                'hash' => $hash,
                'price' => $this->extractPrice($item),
                'tipo_visura' => $this->inferTipoVisura($name, $item),
            ];
        }

        // Unicizza per hash.
        $unique = [];
        foreach ($out as $row) {
            $unique[$row['hash']] = $row;
        }

        return array_values($unique);
    }

    protected function extractPrice(array $item): ?float
    {
        $keys = ['prezzo', 'price', 'costo', 'importo', 'amount', 'tariffa', 'cost'];
        foreach ($keys as $key) {
            $value = $item[$key] ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (is_string($value) && is_numeric(str_replace(',', '.', trim($value)))) {
                return (float) str_replace(',', '.', trim($value));
            }
        }

        foreach ($item as $key => $value) {
            $k = mb_strtolower((string) $key, 'UTF-8');
            if (! str_contains($k, 'prezz') && ! str_contains($k, 'price') && ! str_contains($k, 'cost')) {
                continue;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    protected function inferTipoVisura(string $name, array $item = []): string
    {
        $explicit = strtolower((string) ($item['tipo'] ?? $item['tipo_visura'] ?? $item['subject'] ?? ''));
        if (in_array($explicit, ['azienda', 'impresa', 'societa', 'company'], true)) {
            return 'azienda';
        }
        if (in_array($explicit, ['privato', 'persona', 'person', 'fisica'], true)) {
            return 'privato';
        }

        $n = $this->normalize($name);
        foreach (['impresa', 'aziend', 'societa', 'capitale', 'persone', 'camera', 'camerale', 'cciaa', 'bilancio', 'statuto', 'fascicolo'] as $token) {
            if (str_contains($n, $token)) {
                return 'azienda';
            }
        }

        return 'privato';
    }

    protected function extractName(array $item): string
    {
        $keys = ['nome', 'name', 'titolo', 'title', 'visura', 'servizio', 'label', 'descrizione', 'description'];
        foreach ($keys as $key) {
            $value = $item[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        foreach ($item as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }
            $k = mb_strtolower((string) $key, 'UTF-8');
            if (str_contains($k, 'nome') || str_contains($k, 'name') || str_contains($k, 'title') || str_contains($k, 'visura') || str_contains($k, 'servizio')) {
                return trim($value);
            }
        }

        return '';
    }

    protected function extractHash(array $item): string
    {
        $keys = ['hash_visura', 'hash', 'hashVisura', 'service_hash', 'id'];
        foreach ($keys as $key) {
            $value = $item[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        foreach ($item as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }
            $k = mb_strtolower((string) $key, 'UTF-8');
            if (str_contains($k, 'hash')) {
                return trim($value);
            }
        }

        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function flattenNodes(array $payload): array
    {
        $stack = [$payload];
        $nodes = [];

        while (! empty($stack)) {
            $current = array_pop($stack);
            if (! is_array($current)) {
                continue;
            }

            // Nodo candidato se contiene almeno 1 valore scalare stringa.
            $hasScalarString = false;
            foreach ($current as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $hasScalarString = true;
                    break;
                }
            }
            if ($hasScalarString) {
                $nodes[] = $current;
            }

            foreach ($current as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return $nodes;
    }

    protected function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $map = [
            'à' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return $value;
    }

    /**
     * @param  array<int, array{name:string, normalized:string, hash:string}>  $openApiList
     */
    protected function matchHashByNome(string $tipoVisuraNome, array $openApiList): ?array
    {
        $needle = $this->normalize($tipoVisuraNome);
        if ($needle === '') {
            return null;
        }

        $needleFamily = $this->detectFamily($needle);
        $filtered = $openApiList;
        if ($needleFamily) {
            $filtered = array_values(array_filter($openApiList, function ($candidate) use ($needleFamily) {
                return $this->detectFamily($candidate['normalized']) === $needleFamily;
            }));
            if (empty($filtered)) {
                $filtered = $openApiList;
            }
        }

        $needle = $this->normalizeWithSynonyms($needle);

        // 1) Exact normalized match
        foreach ($filtered as $candidate) {
            $candidateNorm = $this->normalizeWithSynonyms($candidate['normalized']);
            if ($candidateNorm === $needle) {
                return $candidate;
            }
        }

        // 2) Contains either direction
        foreach ($filtered as $candidate) {
            $candidateNorm = $this->normalizeWithSynonyms($candidate['normalized']);
            if (str_contains($candidateNorm, $needle) || str_contains($needle, $candidateNorm)) {
                return $candidate;
            }
        }

        // 3) Token overlap heuristic
        $needleTokens = array_values(array_filter(explode(' ', $needle)));
        $best = null;
        $bestScore = 0;
        foreach ($filtered as $candidate) {
            $candidateNorm = $this->normalizeWithSynonyms($candidate['normalized']);
            $candidateTokens = array_values(array_filter(explode(' ', $candidateNorm)));
            if (empty($candidateTokens)) {
                continue;
            }
            $common = array_intersect($needleTokens, $candidateTokens);
            $score = count($common) * 10;
            // Premia nomi che contengono token distintivi lato visura.
            foreach (['immobile', 'soggetto', 'terreni', 'giuridica', 'fisica', 'ordinaria', 'storica'] as $token) {
                if (in_array($token, $needleTokens, true) && in_array($token, $candidateTokens, true)) {
                    $score += 5;
                }
            }
            if ($score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $bestScore >= 20 ? $best : null;
    }

    protected function detectFamily(string $normalized): ?string
    {
        if (str_contains($normalized, 'catast') || str_contains($normalized, 'catasto')) {
            return 'catastale';
        }
        if (str_contains($normalized, 'camerale') || str_contains($normalized, 'impresa')) {
            return 'camerale';
        }
        // CRIF (SIC privato) ≠ Centrale Rischi Banca d'Italia (CR): famiglie separate.
        if (str_contains($normalized, 'crif')) {
            return 'crif';
        }
        if (str_contains($normalized, 'centrale rischi') || preg_match('/\bcr\b/', $normalized)) {
            return 'centrale_rischi';
        }
        if (str_contains($normalized, 'protest')) {
            return 'protesti';
        }

        return null;
    }

    protected function normalizeWithSynonyms(string $value): string
    {
        $value = ' '.$value.' ';
        $replacements = [
            ' catastale ' => ' catasto ',
            // Non mappare "centrale rischi" su "crif": sono prodotti diversi.
            ' pregiudizievoli ' => ' protesti ',
            ' societa ' => ' giuridica ',
            ' persona giuridica ' => ' giuridica ',
            ' persona fisica ' => ' fisica ',
            ' per soggetto ' => ' soggetto ',
        ];
        $value = strtr($value, $replacements);

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
