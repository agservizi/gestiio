<?php

namespace App\Notifications;

use App\Http\Controllers\Backend\AttivaServizioController;
use App\Models\User;
use App\Notifications\Concerns\BuildsGestiioMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaRichiestaAttivazioneServizi extends Notification
{
    use Queueable;
    use BuildsGestiioMail;

    public function __construct(protected User $agente, protected array $serviziRichiesti = [])
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $etichette = AttivaServizioController::ETICHETTE_SERVIZI;
        $richiesti = collect($this->serviziRichiesti)->map(fn ($nome) => $etichette[$nome] ?? $nome)->implode(', ');

        return $this->gestiioMail('Nuovi servizi attivati da '.$this->agente->nominativo(), [
            'kicker' => 'Nuovo agente',
            'title' => 'Un agente ha attivato dei servizi in autonomia',
            'preheader' => $this->agente->nominativo().' ha attivato da solo i suoi primi servizi.',
            'intro' => $this->agente->nominativo().' si è registrato di recente e ha appena attivato in autonomia i seguenti servizi per iniziare a lavorare.',
            'summary' => $this->compactRows([
                'Agente' => $this->agente->nominativo(),
                'Email' => $this->agente->email,
                'Servizi attivati' => $richiesti !== '' ? $richiesti : 'Nessuno',
            ]),
            'cta_label' => 'Apri profilo agente',
            'cta_url' => route('agente.edit', $this->agente->id),
            'note' => 'Nessuna azione richiesta: l\'agente può già lavorare. Puoi rivedere o modificare i suoi servizi dal profilo in qualsiasi momento.',
        ]);
    }
}
