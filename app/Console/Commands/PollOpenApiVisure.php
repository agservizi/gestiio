<?php

namespace App\Console\Commands;

use App\Http\Services\OpenApiVisureService;
use App\Models\AllegatoServizio;
use App\Models\Visura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PollOpenApiVisure extends Command
{
    protected $signature = 'visure:poll-openapi {--limit=50 : Numero massimo visure da processare} {--dry-run : Mostra solo cosa verrebbe fatto}';

    protected $description = 'Esegue polling delle visure OpenAPI e allega automaticamente il PDF quando disponibile';

    public function handle(): int
    {
        if (!Schema::hasColumn('visure', 'openapi_request_id')) {
            $this->warn('Colonne OpenAPI su visure non presenti. Esegui prima le migration.');
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $query = Visura::query()
            ->with('tipo:id,nome')
            ->whereNotNull('openapi_request_id');

        if (Schema::hasColumn('visure', 'openapi_documento_scaricato_at')) {
            $query->whereNull('openapi_documento_scaricato_at');
        }

        $records = $query->orderBy('id')->limit($limit)->get();
        if ($records->isEmpty()) {
            $this->info('Nessuna visura in attesa di polling.');
            return self::SUCCESS;
        }

        $service = new OpenApiVisureService();
        $processed = 0;
        $downloaded = 0;
        $errors = 0;

        foreach ($records as $record) {
            $processed++;
            $requestId = (string) $record->openapi_request_id;
            if ($requestId === '') {
                continue;
            }

            $statusData = $service->statoRichiesta($requestId);
            if (!$statusData) {
                $errors++;
                $this->warn("Visura #{$record->id}: errore stato ({$service->message})");
                continue;
            }

            if (!$dryRun) {
                $this->salvaStato($record, $statusData);
            }

            $stato = strtolower((string) ($statusData['stato_richiesta'] ?? $statusData['status'] ?? ''));
            if (!$this->isDocumentoPronto($stato)) {
                $this->line("Visura #{$record->id}: non pronta ({$stato})");
                continue;
            }

            if (!$dryRun && Schema::hasColumn('visure', 'openapi_documento_scaricato_at') && $record->openapi_documento_scaricato_at) {
                continue;
            }

            $document = $service->scaricaDocumento($requestId);
            if (!$document) {
                $errors++;
                $this->warn("Visura #{$record->id}: documento non disponibile ({$service->message})");
                continue;
            }

            if ($dryRun) {
                $this->info("Visura #{$record->id}: PDF pronto (dry-run)");
                $downloaded++;
                continue;
            }

            $saved = $this->salvaDocumento($record, $document);
            if ($saved) {
                $downloaded++;
                $this->info("Visura #{$record->id}: PDF allegato ({$saved['fileName']})");
            } else {
                $errors++;
                $this->warn("Visura #{$record->id}: contenuto documento non valido");
            }
        }

        $this->newLine();
        $this->line("Processate: {$processed}");
        $this->line("PDF allegati: {$downloaded}");
        $this->line("Errori: {$errors}");

        return self::SUCCESS;
    }

    protected function salvaStato(Visura $record, array $statusData): void
    {
        if (Schema::hasColumn('visure', 'openapi_stato_richiesta')) {
            $record->openapi_stato_richiesta = (string) ($statusData['stato_richiesta'] ?? $statusData['status'] ?? $record->openapi_stato_richiesta);
        }
        if (Schema::hasColumn('visure', 'openapi_response')) {
            $record->openapi_response = $statusData;
        }
        if (Schema::hasColumn('visure', 'openapi_last_sync_at')) {
            $record->openapi_last_sync_at = now();
        }
        $record->save();
    }

    protected function isDocumentoPronto(string $stato): bool
    {
        if ($stato === '') {
            return false;
        }

        return str_contains($stato, 'evas')
            || str_contains($stato, 'complet')
            || str_contains($stato, 'chius')
            || str_contains($stato, 'ready')
            || str_contains($stato, 'dispon');
    }

    protected function salvaDocumento(Visura $record, array $document): ?array
    {
        $base64 = (string) ($document['file'] ?? '');
        if ($base64 === '') {
            return null;
        }

        $content = base64_decode($base64, true);
        if ($content === false) {
            $content = $base64;
        }

        $fileName = (string) ($document['nome'] ?? ('visura_' . $record->id . '.pdf'));
        $mimeType = (string) ($document['mime_type'] ?? 'application/octet-stream');

        // Evita duplicati se il polling ripete lo stesso documento.
        $already = AllegatoServizio::query()
            ->where('allegato_id', $record->id)
            ->where('allegato_type', Visura::class)
            ->where('filename_originale', $fileName)
            ->exists();
        if ($already) {
            if (Schema::hasColumn('visure', 'openapi_documento_scaricato_at')) {
                $record->openapi_documento_scaricato_at = now();
                $record->save();
            }
            return ['fileName' => $fileName];
        }

        $path = ltrim(config('configurazione.allegati_visure.cartella'), '/')
            . '/' . Str::ulid() . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        try {
            Storage::put($path, $content);
        } catch (\Throwable $e) {
            Log::error('Errore salvataggio file visura OpenAPI', [
                'visura_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $allegato = new AllegatoServizio();
        $allegato->uid = $record->uid;
        $allegato->filename_originale = $fileName;
        $allegato->path_filename = $path;
        $allegato->dimensione_file = strlen($content);
        $allegato->mime_type = $mimeType;
        $allegato->file_contenuto_base64 = base64_encode($content);
        $allegato->per_cliente = 0;
        $allegato->allegato_id = $record->id;
        $allegato->allegato_type = Visura::class;
        $allegato->save();

        if (Schema::hasColumn('visure', 'openapi_documento_nome')) {
            $record->openapi_documento_nome = $fileName;
            $record->openapi_documento_mime = $mimeType;
            $record->openapi_documento_dimensione = strlen($content);
            $record->openapi_documento_scaricato_at = now();
            $record->openapi_last_sync_at = now();
            $record->save();
        }

        return [
            'fileName' => $fileName,
        ];
    }
}

