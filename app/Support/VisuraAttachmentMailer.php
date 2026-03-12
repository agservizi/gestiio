<?php

namespace App\Support;

use App\Models\AllegatoServizio;
use App\Models\Visura;
use App\Notifications\NotificaVisuraACliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class VisuraAttachmentMailer
{
    public static function notifyCliente(Visura $visura, AllegatoServizio $allegato): void
    {
        if (!self::shouldNotify($visura, $allegato)) {
            return;
        }

        DB::afterCommit(function () use ($visura, $allegato) {
            try {
                $visuraFresh = $visura->fresh(['agente', 'tipo']) ?: $visura;
                $allegatoFresh = $allegato->fresh() ?: $allegato;
                Notification::route('mail', trim((string) $visuraFresh->email))
                    ->notify(new NotificaVisuraACliente($visuraFresh, $allegatoFresh));
            } catch (\Throwable $e) {
                Log::warning('Invio email cliente visura non riuscito', [
                    'visura_id' => (int) $visura->id,
                    'allegato_id' => (int) $allegato->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    protected static function shouldNotify(Visura $visura, AllegatoServizio $allegato): bool
    {
        $email = trim((string) $visura->email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ((int) $allegato->allegato_id !== (int) $visura->id) {
            return false;
        }

        if ((string) $allegato->allegato_type !== Visura::class) {
            return false;
        }

        $mime = strtolower((string) $allegato->mime_type);
        $name = strtolower((string) $allegato->filename_originale);

        return str_contains($mime, 'pdf') || str_ends_with($name, '.pdf');
    }
}
