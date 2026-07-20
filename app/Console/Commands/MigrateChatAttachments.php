<?php

namespace App\Console\Commands;

use App\Models\ChatMessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateChatAttachments extends Command
{
    protected $signature = 'chat:migrate-attachments {--dry-run : Mostra cosa verrebbe spostato senza modificare i file}';

    protected $description = 'Sposta gli allegati chat dal disk public al disk local (storage/app/chat-allegati)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $spostati = 0;
        $mancanti = 0;
        $giaLocali = 0;

        ChatMessageAttachment::query()->chunkById(200, function ($allegati) use (&$spostati, &$mancanti, &$giaLocali, $dryRun) {
            foreach ($allegati as $allegato) {
                $relative = ltrim((string) $allegato->path_filename, '/');
                if ($relative === '') {
                    continue;
                }

                if (Storage::disk('local')->exists($relative)) {
                    $giaLocali++;

                    continue;
                }

                if (! Storage::disk('public')->exists($relative)) {
                    $mancanti++;
                    $this->warn("File assente su public: {$relative} (attachment #{$allegato->id})");

                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] public → local: {$relative}");
                    $spostati++;

                    continue;
                }

                $contenuto = Storage::disk('public')->get($relative);
                Storage::disk('local')->put($relative, $contenuto);
                Storage::disk('public')->delete($relative);
                $spostati++;
            }
        });

        $this->info('Allegati spostati: '.$spostati);
        $this->info('Già su local: '.$giaLocali);
        $this->info('File mancanti: '.$mancanti);

        return self::SUCCESS;
    }
}
