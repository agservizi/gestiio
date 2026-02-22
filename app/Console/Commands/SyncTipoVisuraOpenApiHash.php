<?php

namespace App\Console\Commands;

use App\Http\Services\OpenApiVisureService;
use App\Models\TipoVisura;
use Illuminate\Console\Command;

class SyncTipoVisuraOpenApiHash extends Command
{
    protected $signature = 'visure:sync-openapi-hash {--dry-run : Mostra solo mapping senza salvare} {--force : Sovrascrive anche hash gia valorizzati}';

    protected $description = 'Allinea tipi_visure.openapi_hash_visura usando l elenco servizi Visengine OpenAPI';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $service = new OpenApiVisureService();
        $elenco = $service->elencoVisure();

        if (!$elenco) {
            $this->error($service->message ?: 'Nessuna visura ricevuta da OpenAPI.');
            return self::FAILURE;
        }

        $indicizzato = $this->indicizzaVisureOpenApi($elenco);
        if (empty($indicizzato)) {
            $this->error('Elenco OpenAPI ricevuto ma non interpretabile (nome/hash mancanti).');
            return self::FAILURE;
        }

        $records = TipoVisura::query()->orderBy('id')->get();
        if ($records->isEmpty()) {
            $this->warn('Nessun tipo visura trovato.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $notMatched = 0;

        foreach ($records as $record) {
            $current = trim((string) $record->openapi_hash_visura);
            if ($current !== '' && !$force) {
                $skipped++;
                $this->line("SKIP #{$record->id} {$record->nome} (hash gia presente)");
                continue;
            }

            $match = $this->matchHashByNome((string) $record->nome, $indicizzato);
            if (!$match) {
                $notMatched++;
                $this->warn("NO MATCH #{$record->id} {$record->nome}");
                continue;
            }

            $hash = $match['hash'];
            $sourceName = $match['name'];

            $this->info("MATCH #{$record->id} {$record->nome} -> {$sourceName} ({$hash})");
            $record->openapi_hash_visura = $hash;
            $updated++;

            if (!$dryRun) {
                $record->save();
            }
        }

        $this->newLine();
        $this->line("Totali: " . $records->count());
        $this->line("Aggiornati: {$updated}");
        $this->line("Saltati (gia valorizzati): {$skipped}");
        $this->line("Senza match: {$notMatched}");
        $this->line($dryRun ? 'Modalita dry-run: nessun salvataggio eseguito.' : 'Salvataggi completati.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{name:string, normalized:string, hash:string}>
     */
    protected function indicizzaVisureOpenApi(array $elenco): array
    {
        $out = [];
        foreach ($elenco as $item) {
            if (!is_array($item)) {
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
            ];
        }

        return $out;
    }

    protected function extractName(array $item): string
    {
        $keys = ['nome', 'name', 'titolo', 'title', 'visura', 'label'];
        foreach ($keys as $key) {
            $value = $item[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
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

        return '';
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
     * @param array<int, array{name:string, normalized:string, hash:string}> $openApiList
     */
    protected function matchHashByNome(string $tipoVisuraNome, array $openApiList): ?array
    {
        $needle = $this->normalize($tipoVisuraNome);
        if ($needle === '') {
            return null;
        }

        // 1) Exact normalized match
        foreach ($openApiList as $candidate) {
            if ($candidate['normalized'] === $needle) {
                return $candidate;
            }
        }

        // 2) Contains either direction
        foreach ($openApiList as $candidate) {
            if (str_contains($candidate['normalized'], $needle) || str_contains($needle, $candidate['normalized'])) {
                return $candidate;
            }
        }

        // 3) Token overlap heuristic
        $needleTokens = array_values(array_filter(explode(' ', $needle)));
        $best = null;
        $bestScore = 0;
        foreach ($openApiList as $candidate) {
            $candidateTokens = array_values(array_filter(explode(' ', $candidate['normalized'])));
            if (empty($candidateTokens)) {
                continue;
            }
            $common = array_intersect($needleTokens, $candidateTokens);
            $score = count($common);
            if ($score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $bestScore >= 2 ? $best : null;
    }
}

