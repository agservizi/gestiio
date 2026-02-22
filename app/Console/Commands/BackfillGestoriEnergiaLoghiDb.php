<?php

namespace App\Console\Commands;

use App\Models\GestoreContrattoEnergia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackfillGestoriEnergiaLoghiDb extends Command
{
    protected $signature = 'gestori-energia:backfill-loghi-db {--dry-run : Mostra solo le modifiche senza salvare} {--force : Sovrascrive anche i record gia valorizzati}';

    protected $description = 'Popola logo_contenuto_base64/logo_mime_type dei gestori energia leggendo i loghi da storage';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info($dryRun ? 'Modalita dry-run attiva.' : 'Backfill loghi DB in corso...');

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        $records = GestoreContrattoEnergia::query()->orderBy('id')->get();
        foreach ($records as $record) {
            if (!$record->logo) {
                $skipped++;
                continue;
            }

            if (!$force && $record->logo_contenuto_base64) {
                $skipped++;
                continue;
            }

            $relativePath = ltrim((string) $record->logo, '/');
            $content = null;

            if (Storage::disk('public')->exists($relativePath)) {
                $content = Storage::disk('public')->get($relativePath);
            } elseif (Storage::exists('/' . $relativePath)) {
                $content = Storage::get('/' . $relativePath);
            }

            if ($content === null || $content === '') {
                $missing++;
                $this->warn("File logo non trovato per gestore #{$record->id}: {$relativePath}");
                continue;
            }

            $mime = $this->detectMimeType($relativePath, $content);

            $updated++;
            $this->line("Aggiorno #{$record->id} {$record->nome} ({$relativePath})");

            if (!$dryRun) {
                $record->logo_contenuto_base64 = base64_encode($content);
                $record->logo_mime_type = $mime;
                $record->save();
            }
        }

        $this->info("Completato. Aggiornati: {$updated}. Saltati: {$skipped}. Mancanti: {$missing}.");

        return self::SUCCESS;
    }

    protected function detectMimeType(string $relativePath, string $content): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        $ext = Str::lower(pathinfo($relativePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
