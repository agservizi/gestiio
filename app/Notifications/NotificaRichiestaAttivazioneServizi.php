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

        return $this->gestiioMail('Richiesta attivazione servizi: '.$this->agente->nominativo(), [
            'kicker' => 'Nuovo agente',
            'title' => 'Un agente chiede di attivare i servizi',
            'preheader' => $this->agente->nominativo().' non ha ancora servizi attivi.',
            'intro' => $this->agente->nominativo().' si è registrato e non ha ancora nessun servizio abilitato. Ha richiesto l\'attivazione per poter iniziare a lavorare.',
            'summary' => $this->compactRows([
                'Agente' => $this->agente->nominativo(),
                'Email' => $this->agente->email,
                'Servizi richiesti' => $richiesti !== '' ? $richiesti : 'Nessuna preferenza indicata',
            ]),
            'cta_label' => 'Apri profilo agente',
            'cta_url' => route('agente.edit', $this->agente->id),
            'note' => 'Abilita almeno un servizio dal profilo dell\'agente per sbloccare il suo accesso al gestionale.',
        ]);
    }
}
