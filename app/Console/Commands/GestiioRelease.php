<?php

namespace App\Console\Commands;

use App\Http\Services\AppVersionService;
use Illuminate\Console\Command;

class GestiioRelease extends Command
{
    protected $signature = 'gestiio:release
        {version? : Nuova versione (es. 5.1). Se omessa, registra solo la versione attuale di config}
        {--title= : Titolo breve della release}
        {--notes= : Note / changelog (usa \\n per a capo)}
        {--major : Forza flag major anche se non finisce con .0}
        {--ensure : Solo assicurati che la versione config sia già in DB}';

    protected $description = 'Aggiorna la versione Gestiio (config + footer) e la registra nello storico di sistema';

    public function handle(AppVersionService $versions): int
    {
        if ($this->option('ensure')) {
            $release = $versions->ensureCurrentRegistered(
                $this->option('title') ?: null,
                $this->normalizeNotes($this->option('notes'))
            );
            if (! $release) {
                $this->warn('Tabella app_version_releases non disponibile (migration non eseguita?).');

                return self::FAILURE;
            }
            $this->info('Versione corrente in sistema: v'.$release->version);

            return self::SUCCESS;
        }

        $target = $this->argument('version') ?: $versions->current();
        $notes = $this->normalizeNotes($this->option('notes'));

        $release = $versions->release(
            $target,
            $this->option('title') ?: null,
            $notes,
            (bool) $this->option('major')
        );

        $this->info('Release registrata: v'.$release->version);
        if ($release->title) {
            $this->line('Titolo: '.$release->title);
        }
        $this->line('Config aggiornata: configurazione.versione = '.$versions->current());
        $this->comment('Dopo il deploy esegui: php artisan migrate --force && php artisan config:clear');

        return self::SUCCESS;
    }

    protected function normalizeNotes(?string $notes): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }

        return str_replace('\\n', "\n", $notes);
    }
}
