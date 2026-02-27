<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $table = "tickets";

    public const NOME_SINGOLARE = "ticket";
    public const NOME_PLURALE = "tickets";

    public const TIPI_TICKETS = [
        'supporto' => 'Supporto tecnico',
        'amministrazione' => 'Amministrazione',
        'info-contratto' => 'Informazioni su contratto',
    ];

    public const STATI_TICKETS = [
        'nuovo' => ['testo' => 'Nuovo', 'colore' => 'dark', 'coloreHex' => '#f1f5f9'],
        'da_prendere' => ['testo' => 'Da prendere', 'colore' => 'warning', 'coloreHex' => '#fff8dd'],
        'in_lavorazione' => ['testo' => 'In lavorazione', 'colore' => 'primary', 'coloreHex' => '#eef6ff'],
        'in_attesa_cliente' => ['testo' => 'In attesa cliente', 'colore' => 'info', 'coloreHex' => '#f1faff'],
        'in_attesa_fornitore' => ['testo' => 'In attesa fornitore', 'colore' => 'secondary', 'coloreHex' => '#f8f5ff'],
        'risolto' => ['testo' => 'Risolto', 'colore' => 'success', 'coloreHex' => '#e8fff3'],
        'chiuso' => ['testo' => 'Chiuso', 'colore' => 'light', 'coloreHex' => '#f9f9f9'],
    ];

    public const PRIORITA_TICKETS = [
        'bassa' => ['testo' => 'Bassa', 'colore' => 'light', 'peso' => 1],
        'media' => ['testo' => 'Media', 'colore' => 'info', 'peso' => 2],
        'alta' => ['testo' => 'Alta', 'colore' => 'warning', 'peso' => 3],
        'urgente' => ['testo' => 'Urgente', 'colore' => 'danger', 'peso' => 4],
    ];

    public const TEAM_TICKETS = [
        'helpdesk' => 'Helpdesk',
        'amministrazione' => 'Amministrazione',
        'commerciale' => 'Commerciale',
        'tecnico' => 'Tecnico',
    ];

    protected $casts = [
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'last_agent_message_at' => 'datetime',
        'escalated_at' => 'datetime',
        'automation_notes' => 'array',
    ];

    public function causaleTicket(): HasOne
    {
        return $this->hasOne(CausaleTicket::class, 'id', 'causale_ticket_id')
            ->withDefault([
                'descrizione_causale' => 'Senza causale',
            ]);
    }

    public function lettura(): HasOne
    {
        return $this->hasOne(LetturaTicket::class, 'ticket_id')->where('user_id', Auth::id());
    }

    public function messaggi(): HasMany
    {
        return $this->hasMany(MessaggioTicket::class, 'ticket_id', 'id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TicketStatusLog::class, 'ticket_id')->latest('created_at');
    }

    public function servizio()
    {
        return $this->morphTo();
    }

    public function utente()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function assegnatario()
    {
        return $this->hasOne(User::class, 'id', 'agente_id');
    }

    public function classeServizio()
    {
        return Str::of((string)$this->servizio_type)->afterLast('\\')->value();
    }

    public function uidTicket()
    {
        return '#' . Str::substr((string)$this->uid, -6);
    }

    public function labelStatoTicket(): string
    {
        $stato = self::STATI_TICKETS[$this->stato] ?? null;
        if (!$stato) {
            return '<span class="badge badge-light fw-bolder me-2">' . e((string)$this->stato) . '</span>';
        }

        return '<span class="badge badge-' . $stato['colore'] . ' fw-bolder me-2">' . $stato['testo'] . '</span>';
    }

    public function labelPrioritaTicket(): string
    {
        $priorita = self::PRIORITA_TICKETS[$this->priorita ?: 'media'] ?? null;
        if (!$priorita) {
            return '<span class="badge badge-light fw-bolder">Media</span>';
        }

        return '<span class="badge badge-light-' . $priorita['colore'] . ' fw-bolder">' . $priorita['testo'] . '</span>';
    }

    public function labelBoxStatoTicket(): ?string
    {
        $stato = self::STATI_TICKETS[$this->stato] ?? null;
        if (!$stato) {
            return null;
        }

        return '<div class="d-flex flex-center w-50px h-50px w-lg-75px h-lg-75px flex-shrink-0 rounded text-' . $stato['colore'] . ' bg-light-' . $stato['colore'] . ' fw-bolder">' . $stato['testo'] . '</div>';
    }

    public function isClosed(): bool
    {
        return in_array($this->stato, ['risolto', 'chiuso'], true);
    }

    public function isSlaViolated(): bool
    {
        $now = now();

        if (!$this->first_response_at && $this->first_response_due_at && $this->first_response_due_at->lt($now)) {
            return true;
        }

        if (!$this->isClosed() && $this->resolution_due_at && $this->resolution_due_at->lt($now)) {
            return true;
        }

        return false;
    }

    public function isSlaAtRisk(): bool
    {
        if ($this->isSlaViolated()) {
            return false;
        }

        $now = now();
        $thresholdMinutes = 120;

        if (!$this->first_response_at && $this->first_response_due_at && $this->first_response_due_at->between($now, $now->copy()->addMinutes($thresholdMinutes))) {
            return true;
        }

        if (!$this->isClosed() && $this->resolution_due_at && $this->resolution_due_at->between($now, $now->copy()->addMinutes($thresholdMinutes))) {
            return true;
        }

        return false;
    }

    public function slaStatus(): array
    {
        if ($this->isSlaViolated()) {
            return ['key' => 'violato', 'testo' => 'SLA violato', 'classe' => 'danger'];
        }

        if ($this->isSlaAtRisk()) {
            return ['key' => 'attenzione', 'testo' => 'SLA in scadenza', 'classe' => 'warning'];
        }

        return ['key' => 'ok', 'testo' => 'SLA in linea', 'classe' => 'success'];
    }

    public function slaSummary(): string
    {
        $status = $this->slaStatus();

        if ($status['key'] === 'violato' && $this->resolution_due_at) {
            return 'Scaduto ' . $this->resolution_due_at->diffForHumans();
        }

        if (!$this->isClosed() && $this->resolution_due_at) {
            return 'Scadenza ' . $this->resolution_due_at->format('d/m/Y H:i');
        }

        if ($this->resolved_at) {
            return 'Risolto ' . $this->resolved_at->format('d/m/Y H:i');
        }

        return 'SLA non disponibile';
    }

    public function workloadScore(): int
    {
        $priorityWeight = self::PRIORITA_TICKETS[$this->priorita ?: 'media']['peso'] ?? 2;
        $staleHours = (int) max(0, min(24, $this->updated_at?->diffInHours(now()) ?? 0));
        $slaPenalty = $this->isSlaViolated() ? 4 : ($this->isSlaAtRisk() ? 2 : 0);

        return $priorityWeight + $slaPenalty + (int) floor($staleHours / 6);
    }

    public function aiSnapshot(): array
    {
        $messageCount = $this->relationLoaded('messaggi') ? $this->messaggi->count() : 0;
        $waitingOn = 'team';
        if ($this->stato === 'in_attesa_cliente') {
            $waitingOn = 'cliente';
        } elseif ($this->stato === 'in_attesa_fornitore') {
            $waitingOn = 'fornitore';
        }

        $summaryParts = [
            'Ticket ' . $this->uidTicket(),
            $this->causaleTicket?->descrizione_causale ? 'causale ' . strtolower($this->causaleTicket->descrizione_causale) : null,
            'priorita ' . ($this->priorita ?: 'media'),
            $messageCount > 0 ? $messageCount . ' messaggi in thread' : null,
        ];

        $summary = collect($summaryParts)->filter()->implode(', ') . '.';

        $nextAction = match ($this->stato) {
            'nuovo', 'da_prendere' => 'Assegnare un owner e inviare la prima risposta.',
            'in_lavorazione' => 'Aggiornare il cliente con il prossimo step e confermare la presa in carico.',
            'in_attesa_cliente' => 'Sollecitare il cliente e fissare un promemoria di follow-up.',
            'in_attesa_fornitore' => 'Verificare con il fornitore e documentare l’esito nel thread.',
            'risolto' => 'Chiedere conferma finale e chiudere il ticket se non ci sono blocchi.',
            'chiuso' => 'Nessuna azione immediata, tenere il ticket in archivio.',
            default => 'Verificare il thread e definire il prossimo step operativo.',
        };

        $tone = $this->isSlaViolated() ? 'Critico' : ($this->isSlaAtRisk() ? 'Attenzione' : 'Stabile');

        return [
            'summary' => $summary,
            'next_action' => $nextAction,
            'tone' => $tone,
            'waiting_on' => $waitingOn,
        ];
    }
}
