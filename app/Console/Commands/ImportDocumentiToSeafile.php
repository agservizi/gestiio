<?php

namespace App\Console\Commands;

use App\Http\Services\SeafileClient;
use App\Http\Services\SensitiveFileService;
use App\Models\CartellaFiles;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportDocumentiToSeafile extends Command
{
    protected $signature = 'documenti:import-seafile
                            {--dry-run : Simula senza caricare}
                            {--limit=0 : Max file da importare (0 = tutti)}
                            {--force : Re-importa anche se seafile_path è già valorizzato}';

    protected $description = 'Importa Documenti Gestiio in Seafile preservando l’albero cartelle (nessuna cancellazione locale)';

    public function handle(SeafileClient $seafile, SensitiveFileService $sensitive): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $this->info('Ping Seafile...');
        if (! $dry && ! $seafile->ping()) {
            $this->error('Seafile non raggiungibile o credenziali errate.');

            return self::FAILURE;
        }

        try {
            $repoId = $seafile->repoId();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $dry) {
            $seafile->withAdmin();
        }

        // Pre-crea tutte le cartelle (anche vuote) per mantenere categorizzazione
        $folders = CartellaFiles::query()->defaultOrder()->get();
        $folderPathMap = [];
        $this->info('Cartelle da sincronizzare: '.$folders->count());

        foreach ($folders as $folder) {
            $path = $this->folderPath($folder);
            $folderPathMap[(int) $folder->id] = $path;
            $this->line('  DIR  '.$path);
            if (! $dry) {
                try {
                    $seafile->ensureDir($repoId, $path);
                } catch (\Throwable $e) {
                    $this->error('Cartella fallita '.$path.': '.$e->getMessage());
                    Log::error('seafile.mkdir', ['path' => $path, 'error' => $e->getMessage()]);

                    return self::FAILURE;
                }
            }
        }

        $query = File::query()->orderBy('id');
        if (! $force) {
            $query->whereNull('seafile_path');
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $files = $query->get();
        $this->info('File da importare: '.$files->count().($dry ? ' (dry-run)' : ''));

        $ok = 0;
        $skip = 0;
        $fail = 0;

        foreach ($files as $file) {
            /** @var File $file */
            $parentDir = '/';
            if ($file->cartella_id && isset($folderPathMap[(int) $file->cartella_id])) {
                $parentDir = $folderPathMap[(int) $file->cartella_id];
            } elseif ($file->cartella_id) {
                $cartella = CartellaFiles::find($file->cartella_id);
                $parentDir = $cartella ? $this->folderPath($cartella) : '/_senza_cartella';
            } else {
                $parentDir = '/_senza_cartella';
            }

            $filename = $this->safeFilename((string) $file->filename_originale, (int) $file->id);

            if (! $sensitive->exists($file->path_filename)) {
                $this->warn('SKIP missing disk #'.$file->id.' '.$filename);
                $skip++;
                continue;
            }

            $absolute = $sensitive->absolutePath($file->path_filename);
            $dest = rtrim($parentDir, '/').'/'.$filename;

            if ($dry) {
                $this->line('  FILE '.$dest);
                $ok++;
                continue;
            }

            try {
                if (! $force && $seafile->fileExists($repoId, $parentDir, $filename)) {
                    $file->seafile_path = $dest;
                    $file->seafile_imported_at = now();
                    $file->save();
                    $this->line('  EXISTS '.$dest);
                    $ok++;
                    continue;
                }

                $seaPath = $seafile->uploadFile($repoId, $parentDir, $absolute, $filename);
                $file->seafile_path = $seaPath;
                $file->seafile_imported_at = now();
                $file->save();
                $ok++;
                $this->line('  OK   '.$seaPath);
            } catch (\Throwable $e) {
                $fail++;
                $this->error('  FAIL #'.$file->id.' '.$filename.': '.$e->getMessage());
                Log::error('seafile.import', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Risultato: ok=$ok skip=$skip fail=$fail (file locali NON cancellati)");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function folderPath(CartellaFiles $folder): string
    {
        $ancestors = CartellaFiles::ancestorsAndSelf($folder->id);
        $names = [];
        foreach ($ancestors as $node) {
            $name = trim((string) $node->nome);
            if ($name === '') {
                $name = 'cartella-'.$node->id;
            }
            $names[] = $name;
        }

        return '/'.implode('/', $names);
    }

    private function safeFilename(string $name, int $id): string
    {
        $name = str_replace(["\0", '/', '\\'], '-', $name);
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..') {
            return 'file-'.$id;
        }

        // Evita collisioni rare: se nome troppo generico resta com'è; Seafile replace=1
        return $name;
    }
}
