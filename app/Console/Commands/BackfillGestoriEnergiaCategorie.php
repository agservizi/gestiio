<?php

namespace App\Console\Commands;

use App\Models\GestoreContrattoEnergia;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillGestoriEnergiaCategorie extends Command
{
    protected $signature = 'gestori-energia:backfill-categorie {--dry-run : Mostra solo le modifiche senza salvare}';

    protected $description = 'Allinea categoria_pratica/switch_key e crea la coppia consumer-business mancante per i gestori energia';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Modalita dry-run attiva.' : 'Backfill gestori energia in corso...');

        $gestori = GestoreContrattoEnergia::query()->orderBy('id')->get();
        if ($gestori->isEmpty()) {
            $this->warn('Nessun gestore trovato.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($gestori as $gestore) {
            $categoria = $this->guessCategoria($gestore);
            $switchKey = $this->guessSwitchKey($gestore);

            $dirty = false;
            if ($gestore->categoria_pratica !== $categoria) {
                $gestore->categoria_pratica = $categoria;
                $dirty = true;
            }
            if ($gestore->switch_key !== $switchKey) {
                $gestore->switch_key = $switchKey;
                $dirty = true;
            }

            if ($dirty) {
                $updated++;
                $this->line("Aggiorno #{$gestore->id} {$gestore->nome}: {$categoria} / {$switchKey}");
                if (! $dryRun) {
                    $gestore->save();
                }
            }
        }

        // Ricarico dopo eventuali update
        $gestori = GestoreContrattoEnergia::query()->orderBy('id')->get()->groupBy('switch_key');

        $created = 0;
        foreach ($gestori as $switchKey => $records) {
            if (! $switchKey) {
                continue;
            }

            $consumer = $records->firstWhere('categoria_pratica', 'consumer');
            $business = $records->firstWhere('categoria_pratica', 'business');

            if ($consumer && $business) {
                continue;
            }

            $source = $consumer ?: $business;
            if (! $source) {
                continue;
            }

            $targetCategoria = $consumer ? 'business' : 'consumer';
            $nuovo = $source->replicate([
                'created_at',
                'updated_at',
            ]);
            $nuovo->categoria_pratica = $targetCategoria;
            $nuovo->nome = $this->normalizeNomePerCategoria($source->nome, $targetCategoria);
            $nuovo->model_prodotto = $this->counterpartModelProdotto((string) $source->model_prodotto, $targetCategoria);
            $nuovo->switch_key = $switchKey;

            $created++;
            $this->line("Creo variante {$targetCategoria} per {$switchKey}: {$nuovo->nome} ({$nuovo->model_prodotto})");
            if (! $dryRun) {
                $nuovo->save();
            }
        }

        $this->info("Completato. Aggiornati: {$updated}. Creati: {$created}.");

        return self::SUCCESS;
    }

    protected function guessCategoria(GestoreContrattoEnergia $gestore): string
    {
        if (in_array($gestore->categoria_pratica, ['consumer', 'business'], true)) {
            return $gestore->categoria_pratica;
        }

        $fromModel = $this->categoriaDaStringa((string) $gestore->model_prodotto);
        if ($fromModel) {
            return $fromModel;
        }

        $fromNome = $this->categoriaDaStringa((string) $gestore->nome);
        if ($fromNome) {
            return $fromNome;
        }

        return 'consumer';
    }

    protected function guessSwitchKey(GestoreContrattoEnergia $gestore): string
    {
        if ($gestore->switch_key) {
            return Str::slug((string) $gestore->switch_key, '-');
        }

        $nome = preg_replace('/\b(consumer|business)\b/i', '', (string) $gestore->nome);

        return Str::slug(trim((string) $nome), '-');
    }

    protected function categoriaDaStringa(string $value): ?string
    {
        $value = strtolower($value);
        if (str_contains($value, 'business')) {
            return 'business';
        }
        if (str_contains($value, 'consumer')) {
            return 'consumer';
        }

        return null;
    }

    protected function normalizeNomePerCategoria(string $nome, string $categoria): string
    {
        $base = preg_replace('/\s*[\(\-]?\s*(consumer|business)\s*[\)]?$/i', '', trim($nome));

        return trim($base).' '.ucfirst($categoria);
    }

    protected function counterpartModelProdotto(string $modelProdotto, string $categoria): string
    {
        if (! $modelProdotto) {
            return $modelProdotto;
        }

        if ($categoria === 'consumer') {
            $target = str_ireplace('business', 'Consumer', $modelProdotto);
            if ($target !== $modelProdotto) {
                return $target;
            }

            return $modelProdotto;
        }

        $target = str_ireplace('consumer', 'Business', $modelProdotto);
        if ($target !== $modelProdotto) {
            return $target;
        }

        return $modelProdotto;
    }
}
