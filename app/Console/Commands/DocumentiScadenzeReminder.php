<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Notifica;
use Illuminate\Console\Command;

class DocumentiScadenzeReminder extends Command
{
    protected $signature = 'documenti:promemoria-scadenze {--giorni=7} {--limit=200}';

    protected $description = 'Invia promemoria automatici per documenti in scadenza';

    public function handle(): int
    {
        $giorni = max(1, (int)$this->option('giorni'));
        $limit = max(1, (int)$this->option('limit'));

        $start = now();
        $end = now()->copy()->addDays($giorni);

        $records = File::query()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$start, $end])
            ->where(function ($q) {
                $q->whereNull('last_reminder_at')
                    ->orWhereDate('last_reminder_at', '<', now()->toDateString());
            })
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            $this->info('Nessun documento da notificare.');
            return self::SUCCESS;
        }

        foreach ($records as $file) {
            $testo = sprintf(
                'Documento <span class="fw-bold">%s</span> in scadenza il <span class="fw-bold">%s</span>.',
                e($file->filename_originale),
                $file->expires_at?->format('d/m/Y H:i')
            );
            Notifica::notificaAdAdmin('Promemoria scadenza documento', $testo, 'warning');
            $file->last_reminder_at = now();
            $file->save();
        }

        $this->info('Promemoria inviati: ' . $records->count());
        return self::SUCCESS;
    }
}
