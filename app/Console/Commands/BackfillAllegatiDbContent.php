<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackfillAllegatiDbContent extends Command
{
    protected $signature = 'allegati:backfill-db-content
                            {--disk= : Disk storage da usare (default: disk di default)}
                            {--table=* : Esegui solo sulle tabelle indicate (ripetibile o separate da virgola)}
                            {--force : Aggiorna anche record già popolati}
                            {--chunk=200 : Dimensione chunk}';

    protected $description = 'Popola mime_type e file_contenuto_base64 leggendo gli allegati dal filesystem';

    public function handle(): int
    {
        $tables = [
            'contratti_allegati',
            'contratti_energia_allegati',
            'caf_patronato_allegati',
            'allegati_tutti_servizi',
            'visure_allegati',
            'tickets_allegati',
        ];

        $selectedTables = collect((array)$this->option('table'))
            ->flatMap(function ($value) {
                return array_map('trim', explode(',', (string)$value));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($selectedTables)) {
            $invalidTables = array_values(array_diff($selectedTables, $tables));
            if (!empty($invalidTables)) {
                $this->error('Tabelle non valide: ' . implode(', ', $invalidTables));
                $this->line('Tabelle supportate: ' . implode(', ', $tables));
                return self::FAILURE;
            }

            $tables = $selectedTables;
        }

        $diskName = $this->option('disk') ?: config('filesystems.default');
        $force = (bool)$this->option('force');
        $chunkSize = max(1, (int)$this->option('chunk'));

        $this->info("Disk: {$diskName}");
        $this->info('Tabelle: ' . implode(', ', $tables));
        $this->info('Modalità: ' . ($force ? 'FORCE (aggiorna tutto)' : 'solo record mancanti'));

        $totali = [
            'rows' => 0,
            'updated' => 0,
            'missing_file' => 0,
            'read_error' => 0,
            'skipped' => 0,
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->error('Connessione DB non disponibile. Verifica credenziali/host nel file .env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).');
            $this->line('Dettaglio: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($tables as $tableName) {
            try {
                if (!Schema::hasTable($tableName)) {
                    $this->warn("[{$tableName}] tabella non trovata, salto");
                    continue;
                }

                if (!Schema::hasColumn($tableName, 'path_filename') ||
                    !Schema::hasColumn($tableName, 'file_contenuto_base64') ||
                    !Schema::hasColumn($tableName, 'mime_type')) {
                    $this->warn("[{$tableName}] colonne mancanti, salto");
                    continue;
                }
            } catch (QueryException $e) {
                $this->error("[{$tableName}] errore accesso schema: " . $e->getMessage());
                return self::FAILURE;
            }

            $this->line("\n[{$tableName}] backfill in corso...");

            $query = DB::table($tableName)->select('id', 'path_filename', 'filename_originale', 'mime_type', 'file_contenuto_base64');

            if (!$force) {
                $query->where(function ($q) {
                    $q->whereNull('file_contenuto_base64')
                        ->orWhere('file_contenuto_base64', '=','')
                        ->orWhereNull('mime_type')
                        ->orWhere('mime_type', '=','');
                });
            }

            $tableCounters = [
                'rows' => 0,
                'updated' => 0,
                'missing_file' => 0,
                'read_error' => 0,
                'skipped' => 0,
            ];

            $query->orderBy('id')->chunkById($chunkSize, function ($rows) use ($tableName, $diskName, &$tableCounters, &$totali) {
                foreach ($rows as $row) {
                    $tableCounters['rows']++;
                    $totali['rows']++;

                    $path = ltrim((string)$row->path_filename, '/');
                    if ($path === '' || !Storage::disk($diskName)->exists($path)) {
                        $tableCounters['missing_file']++;
                        $totali['missing_file']++;
                        continue;
                    }

                    try {
                        $content = Storage::disk($diskName)->get($path);
                    } catch (\Throwable $e) {
                        report($e);
                        $tableCounters['read_error']++;
                        $totali['read_error']++;
                        continue;
                    }

                    if ($content === '' || $content === null) {
                        $tableCounters['read_error']++;
                        $totali['read_error']++;
                        continue;
                    }

                    $mimeType = $row->mime_type;
                    if (!$mimeType) {
                        $mimeType = $this->guessMimeTypeFromPath($path);
                    }

                    $base64 = base64_encode($content);

                    if (!$this->shouldUpdateRow($row, $base64, $mimeType)) {
                        $tableCounters['skipped']++;
                        $totali['skipped']++;
                        continue;
                    }

                    DB::table($tableName)
                        ->where('id', $row->id)
                        ->update([
                            'file_contenuto_base64' => $base64,
                            'mime_type' => $mimeType,
                        ]);

                    $tableCounters['updated']++;
                    $totali['updated']++;
                }
            }, 'id');

            $this->line("[{$tableName}] lette {$tableCounters['rows']} - aggiornate {$tableCounters['updated']} - mancanti {$tableCounters['missing_file']} - errori {$tableCounters['read_error']} - skip {$tableCounters['skipped']}");
        }

        $this->newLine();
        $this->info('Backfill completato');
        $this->line("Totale lette: {$totali['rows']}");
        $this->line("Totale aggiornate: {$totali['updated']}");
        $this->line("Totale file mancanti: {$totali['missing_file']}");
        $this->line("Totale errori lettura: {$totali['read_error']}");
        $this->line("Totale skip: {$totali['skipped']}");

        return self::SUCCESS;
    }

    protected function shouldUpdateRow(object $row, string $base64, string $mimeType): bool
    {
        $hasBase64 = isset($row->file_contenuto_base64) && $row->file_contenuto_base64 !== '';
        $hasMime = isset($row->mime_type) && $row->mime_type !== '';

        if (!$hasBase64 || !$hasMime) {
            return true;
        }

        return $row->file_contenuto_base64 !== $base64 || $row->mime_type !== $mimeType;
    }

    protected function guessMimeTypeFromPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }
}
