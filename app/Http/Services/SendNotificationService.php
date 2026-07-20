<?php

namespace App\Http\Services;

use App\Models\Notifica;
use App\Models\SendRequest;
use App\Models\User;
use App\Notifications\NotificaSendAssigned;
use App\Notifications\NotificaSendResubmitted;

class SendNotificationService
{
    public function notifySupervisorAssigned(SendRequest $request, User $supervisor): void
    {
        $fresh = $request->fresh(['documents', 'creator']);

        $this->panel(
            $supervisor,
            'SEND: nuova pratica assegnata',
            $this->panelBody($fresh, 'La pratica ti è stata assegnata.'),
            'info'
        );

        try {
            $supervisor->notify(new NotificaSendAssigned($fresh));
        } catch (\Throwable $e) {
            //
        }
    }

    public function notifySupervisorResubmitted(SendRequest $request): void
    {
        $supervisor = $request->supervisor;
        if (! $supervisor) {
            return;
        }

        $fresh = $request->fresh(['documents', 'creator']);

        $this->panel(
            $supervisor,
            'SEND: pratica reinviata',
            $this->panelBody($fresh, 'L\'operatore ha reinviato la pratica con documentazione aggiornata.'),
            'warning'
        );

        try {
            $supervisor->notify(new NotificaSendResubmitted($fresh));
        } catch (\Throwable $e) {
            //
        }
    }

    private function panel(User $supervisor, string $titolo, string $testo, string $tipo): void
    {
        try {
            Notifica::notificaAdSupervisore($supervisor, $titolo, $testo, $tipo);
        } catch (\Throwable $e) {
            //
        }
    }

    private function panelBody(SendRequest $request, string $intro): string
    {
        $url = url('/backend/send/'.$request->uuid);

        return $intro.' Pratica <span class="fw-bold">'.$request->request_number.'</span>. '
            .'<a href="'.$url.'">Apri pratica</a>';
    }
}
