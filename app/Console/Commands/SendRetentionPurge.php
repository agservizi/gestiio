<?php

namespace App\Console\Commands;

use App\Enums\SendRequestStatus;
use App\Models\SendRequest;
use App\Models\SendRequestDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SendRetentionPurge extends Command
{
    protected $signature = 'send:retention-purge {--dry-run : Mostra senza cancellare}';

    protected $description = 'Purge pratiche SEND chiuse/annullate oltre retention_days';

    public function handle(): int
    {
        $days = (int) config('send.retention_days', 0);
        if ($days <= 0) {
            $this->info('Retention disabilitata (retention_days=0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $dry = (bool) $this->option('dry-run');
        $count = 0;

        $statuses = [
            SendRequestStatus::CLOSED->value,
            SendRequestStatus::CANCELLED->value,
            SendRequestStatus::REJECTED->value,
            SendRequestStatus::EXPIRED->value,
        ];

        SendRequest::query()
            ->whereIn('status', $statuses)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($w) use ($cutoff) {
                    $w->whereNotNull('closed_at')->where('closed_at', '<=', $cutoff);
                })->orWhere(function ($w) use ($cutoff) {
                    $w->whereNull('closed_at')
                        ->where(function ($x) use ($cutoff) {
                            $x->whereNotNull('cancelled_at')->where('cancelled_at', '<=', $cutoff);
                        });
                })->orWhere(function ($w) use ($cutoff) {
                    $w->whereNull('closed_at')->whereNull('cancelled_at')
                        ->where('updated_at', '<=', $cutoff);
                });
            })
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($dry, &$count) {
                foreach ($rows as $request) {
                    $count++;
                    if ($dry) {
                        $this->line('[dry-run] '.$request->request_number);
                        continue;
                    }

                    $docs = SendRequestDocument::withTrashed()
                        ->where('send_request_id', $request->id)
                        ->get();
                    foreach ($docs as $doc) {
                        try {
                            if ($doc->path && Storage::disk($doc->disk ?: config('send.disk', 'sensitive'))->exists($doc->path)) {
                                Storage::disk($doc->disk ?: config('send.disk', 'sensitive'))->delete($doc->path);
                            }
                        } catch (\Throwable $e) {
                        }
                        $doc->forceDelete();
                    }

                    $request->delete(); // soft-delete pratica
                }
            });

        $this->info(($dry ? 'Da purgare' : 'Purgate').": {$count}");

        return self::SUCCESS;
    }
}
