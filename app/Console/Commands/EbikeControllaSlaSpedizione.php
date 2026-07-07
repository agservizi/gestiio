<?php

namespace App\Console\Commands;

use App\Models\Notifica;
use App\Models\OrdineEbike;
use App\Models\User;
use App\Notifications\NotificaEbikeSlaSuperato;
use Illuminate\Console\Command;

class EbikeControllaSlaSpedizione extends Command
{
    protected $signature = 'ebike:controlla-sla-spedizione';

    protected $description = 'Segnala gli ordini ebike con pagamento confermato e spedizione oltre il termine SLA';

    public function handle(): int
    {
        $records = OrdineEbike::query()
            ->with('agente')
            ->inSlaScaduta()
            ->get();

        if ($records->isEmpty()) {
            $this->info('Nessun ordine ebike fuori SLA.');

            return self::SUCCESS;
        }

        foreach ($records as $ordine) {
            $nominativo = $ordine->agente?->nominativo() ?? 'Agente #'.$ordine->agente_id;

            Notifica::notificaAdAdmin(
                'Ordine ebike fuori SLA spedizione',
                'L\'ordine ebike <span class="fw-bold">#'.$ordine->id.'</span> di <span class="fw-bold">'.$nominativo.'</span> ha superato i '
                    .OrdineEbike::GIORNI_SLA_SPEDIZIONE.' giorni per la spedizione.',
                'error'
            );

            $userAdmin = User::find(2);
            $userAdmin?->notify(new NotificaEbikeSlaSuperato($ordine));

            $ordine->sla_alert_inviato = true;
            $ordine->save();
        }

        $this->info('Alert SLA inviati: '.$records->count());

        return self::SUCCESS;
    }
}
