<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TicketsAuditIntegrity extends Command
{
    protected $signature = 'tickets:audit-integrity {--limit=30 : Numero massimo record da mostrare per sezione}';

    protected $description = 'Audit integrita ticket: causale/utente/assegnatario mancanti o non validi';

    public function handle(): int
    {
        $limit = max(1, (int)$this->option('limit'));

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->error('Connessione DB non disponibile. Verifica .env e whitelist host.');
            $this->line('Dettaglio: ' . $e->getMessage());
            return self::FAILURE;
        }

        $total = Ticket::count();
        $nullCausale = Ticket::whereNull('causale_ticket_id')->count();
        $invalidCausaleFk = Ticket::whereNotNull('causale_ticket_id')
            ->whereDoesntHave('causaleTicket')
            ->count();

        $nullUser = Ticket::whereNull('user_id')->count();
        $invalidUserFk = Ticket::whereNotNull('user_id')
            ->whereDoesntHave('utente')
            ->count();

        $nullAssegnatario = Ticket::whereNull('agente_id')->count();
        $invalidAssegnatarioFk = Ticket::whereNotNull('agente_id')
            ->whereDoesntHave('assegnatario')
            ->count();

        $this->info('Audit integrita tickets');
        $this->line('Totale tickets: ' . $total);
        $this->line('causale_ticket_id NULL: ' . $nullCausale);
        $this->line('causale_ticket_id non valida: ' . $invalidCausaleFk);
        $this->line('user_id NULL: ' . $nullUser);
        $this->line('user_id non valido: ' . $invalidUserFk);
        $this->line('agente_id NULL (non assegnato): ' . $nullAssegnatario);
        $this->line('agente_id non valido: ' . $invalidAssegnatarioFk);

        $this->newLine();
        $this->warn('Campione record senza causale (NULL):');
        $this->dumpRows(
            Ticket::query()
                ->whereNull('causale_ticket_id')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'uid', 'causale_ticket_id', 'user_id', 'agente_id'])
                ->toArray()
        );

        $this->newLine();
        $this->warn('Campione record con causale non valida:');
        $this->dumpRows(
            Ticket::query()
                ->whereNotNull('causale_ticket_id')
                ->whereDoesntHave('causaleTicket')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'uid', 'causale_ticket_id', 'user_id', 'agente_id'])
                ->toArray()
        );

        $this->newLine();
        $this->warn('Campione record con user_id non valido:');
        $this->dumpRows(
            Ticket::query()
                ->whereNotNull('user_id')
                ->whereDoesntHave('utente')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'uid', 'causale_ticket_id', 'user_id', 'agente_id'])
                ->toArray()
        );

        $this->newLine();
        $this->warn('Campione record con agente_id non valido:');
        $this->dumpRows(
            Ticket::query()
                ->whereNotNull('agente_id')
                ->whereDoesntHave('assegnatario')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'uid', 'causale_ticket_id', 'user_id', 'agente_id'])
                ->toArray()
        );

        return self::SUCCESS;
    }

    protected function dumpRows(array $rows): void
    {
        if (empty($rows)) {
            $this->line('Nessun record trovato.');
            return;
        }

        $this->table(['id', 'uid', 'causale_ticket_id', 'user_id', 'agente_id'], $rows);
    }
}
