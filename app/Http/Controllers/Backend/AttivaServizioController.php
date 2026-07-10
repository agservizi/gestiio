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
            'richiestaInviata' => (bool) $request->session()->get($this->sessionKey($user)),
        ]);
    }

    public function richiedi(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasServiziAttivi()) {
            return redirect('/');
        }

        $sessionKey = $this->sessionKey($user);
        if ($request->session()->get($sessionKey)) {
            (new AlertMessage)->messaggio('Richiesta già inviata: attendi che un amministratore attivi i tuoi servizi.', 'info')->flash();

            return back();
        }

        $serviziValidi = array_keys($this->serviziDisponibili());
        $richiesti = array_values(array_intersect((array) $request->input('servizi', []), $serviziValidi));

        User::permission('admin')->get()->each(function (User $admin) use ($user, $richiesti) {
            $admin->notify(new NotificaRichiestaAttivazioneServizi($user, $richiesti));
        });

        $request->session()->put($sessionKey, now()->toDateTimeString());

        (new AlertMessage)->messaggio('Richiesta inviata! Un amministratore attiverà i tuoi servizi al più presto.')->flash();

        return back();
    }

    protected function serviziDisponibili(): array
    {
        $nomi = Permission::where('id', '>', 3)->where('name', '<>', 'operatore')->pluck('name');

        return $nomi->mapWithKeys(fn ($nome) => [$nome => self::ETICHETTE_SERVIZI[$nome] ?? ucfirst(str_replace(['servizio_', '_', '-'], ['', ' ', ' '], $nome))])->all();
    }

    protected function sessionKey(User $user): string
    {
        return 'servizi_richiesta_inviata_'.$user->id;
    }
}
