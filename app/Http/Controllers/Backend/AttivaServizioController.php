<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Models\User;
use App\Notifications\NotificaRichiestaAttivazioneServizi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class AttivaServizioController extends Controller
{
    public const ETICHETTE_SERVIZI = [
        'servizio_contratti_telefonia' => 'Contratti Telefonia',
        'servizio_contratti_amex' => 'Contratti Amex',
        'servizio_contratti_energia' => 'Contratti Energia',
        'servizio_servizi_finanziari' => 'Servizi Finanziari',
        'servizio_compara_semplice' => 'Compara Semplice',
        'servizio_attivazioni_sim' => 'Attivazioni SIM',
        'servizio_visure' => 'Visure Camerali',
        'servizio_caf_patronato' => 'CAF / Patronato',
        'servizio_spedizioni' => 'Spedizioni',
        'servizio_documentazione' => 'Documentazione',
        'servizio_ticket' => 'Ticket',
        'ebike-b2b' => 'Ebike B2B',
    ];

    public function show(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasServiziAttivi()) {
            return redirect('/');
        }

        return view('Backend.Dashboard.attivaServizio', [
            'servizi' => $this->serviziDisponibili(),
        ]);
    }

    public function richiedi(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasServiziAttivi()) {
            return redirect('/');
        }

        $serviziValidi = array_keys($this->serviziDisponibili());
        $selezionati = array_values(array_intersect((array) $request->input('servizi', []), $serviziValidi));

        if (empty($selezionati)) {
            (new AlertMessage)->messaggio('Seleziona almeno un servizio da attivare.', 'warning')->flash();

            return back();
        }

        $user->givePermissionTo($selezionati);

        User::permission('admin')->get()->each(function (User $admin) use ($user, $selezionati) {
            $admin->notify(new NotificaRichiestaAttivazioneServizi($user, $selezionati));
        });

        (new AlertMessage)->messaggio('Servizi attivati! Da ora puoi iniziare a lavorare.')->flash();

        return redirect('/');
    }

    protected function serviziDisponibili(): array
    {
        $nomi = Permission::where('id', '>', 3)->where('name', '<>', 'operatore')->pluck('name');

        return $nomi->mapWithKeys(fn ($nome) => [$nome => self::ETICHETTE_SERVIZI[$nome] ?? ucfirst(str_replace(['servizio_', '_', '-'], ['', ' ', ' '], $nome))])->all();
    }
}
