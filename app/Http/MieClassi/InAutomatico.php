<?php

namespace App\Http\MieClassi;

use App\Models\ContrattoTelefonia;
use App\Models\Notifica;
use App\Models\User;
use App\Notifications\NotificaScadenzaContrattoTelefonia;
use App\Notifications\NotificaSollecitoGestoreTelefonia;
use Illuminate\Support\Facades\Notification;

class InAutomatico
{
    public static function inviaNotificheSollecitoGestoriTelefonia()
    {

        $records = ContrattoTelefonia::query()
            ->withoutGlobalScope('filtroOperatore')
            ->whereHas('esito', function ($q) {
                $q->where('esito_finale', 'in-lavorazione');
            })
            ->whereHas('tipoContratto.gestore', function ($q) {
                $q->whereNotNull('email_notifica_sollecito');
            })
            ->whereDate('data', '<=', now()->subDays(10))
            ->whereNull('sollecito_gestore')
            ->with('tipoContratto.gestore')
            ->get();
        foreach ($records as $record) {
            $email = $record->tipoContratto->gestore->email_notifica_sollecito;
            Notification::route('mail', $email)->notify(new NotificaSollecitoGestoreTelefonia($record->id));
        }


    }

    public static function inviaNotificheScadenzaContrattiTelefonia()
    {

        $records = ContrattoTelefonia::query()
            ->withoutGlobalScope('filtroOperatore')
            ->with('tipoContratto')
            ->whereDate('data_reminder', '<=', now()->addDays(20))
            ->whereNull('reminder_inviato')
            ->get();
        foreach ($records as $record) {
            Notifica::notificaAdAdmin('Scadenza contratto telefonia', 'Il contratto ' . $record->tipoContratto->nome . ' di ' . $record->nominativo() . ' scadrà il ' . $record->data_reminder->addDays(20)->format('d/m/Y'));
            Notification::route('mail', $record->email)->notify(new NotificaScadenzaContrattoTelefonia($record->id));
            $record->reminder_inviato = now();
            $record->save();
        }


    }
}
