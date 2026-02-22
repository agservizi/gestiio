<?php

namespace App\Http\Controllers\Backend;

use App\Http\MieClassi\AlertMessage;
use App\Models\Documento;
use App\Models\DocumentoAcquistato;
use App\Models\DownloadGratuiti;
use App\Models\RegistroLogin;
use App\Models\User;
use App\Notifications\NotificaAccountEliminatoAAdmin;
use App\Notifications\NotificaAccountEliminatoAUtente;
use App\Rules\CodiceFiscaleRule;
use App\Rules\ConfermaEliminaRule;
use App\Rules\IbanRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PasswordAttualeRule;
use App\Rules\TelefonoRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Storage;
use function App\getInputTelefono;
use function App\getInputUcwords;

class AreaPersonaleController extends Controller
{

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function metronic($cosa)
    {
        switch ($cosa) {
            case 'dark':
                if ($this->currentUser()->getExtra('darkMode')) {
                    $this->currentUser()->setExtra(['darkMode' => false]);
                } else {
                    $this->currentUser()->setExtra(['darkMode' => true]);
                }
                return redirect()->back();

            case 'aside':
                if ($this->currentUser()->getExtra('aside') == 'off') {
                    $this->currentUser()->setExtra(['aside' => 'on']);
                } else {
                    $this->currentUser()->setExtra(['aside' => 'off']);
                }
                return ['success' => true];
        }
    }

    public function show($cosa = null)
    {
        if (!$this->currentUser()->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore'])) {
            return redirect('/area-personale');
        }

        $recentLogin = RegistroLogin::where('user_id', $this->currentUser()->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('Backend.DatiUtente.editDatiUtente', [
            'record' => $this->currentUser(),
            'controller' => AreaPersonaleController::class,
            'recentLogin' => $recentLogin,
        ]);

    }

    public function update(Request $request, $cosa)
    {
        switch ($cosa) {
            case 'dati-utente':
                Validator::make($request->input(), [
                    'nome' => ['required', 'string', 'max:255'],
                    'cognome' => ['required', 'string', 'max:255'],
                    'telefono' => ['required', new TelefonoRule(), Rule::unique(User::class)->ignore(Auth::id())],
                    'codice_fiscale' => ['nullable', new CodiceFiscaleRule()],
                    'partita_iva' => ['nullable', new PartitaIvaRule()],
                    'iban' => ['nullable', new IbanRule()]
                ])->validate();

                $this->updateDatiUtente($request);
                $alert = new AlertMessage();
                $alert->messaggio('I tuoi dati sono stati aggiornati')->flash();
                break;


            case 'dati-email':
                Validator::make($request->input(), [
                    'email' => [
                        'required',
                        'string',
                        'email:rfc,dns',
                        'max:255',
                        'confirmed',
                        Rule::unique(User::class)->ignore(Auth::id()),
                    ]
                ])->validate();
                $this->updateEmail($request);
                $alert = new AlertMessage();
                $alert->messaggio('Il tuo indirizzo email è stato modificato in: ' . $request->input('email'))->titolo('Indirizzo email modificato')->flash();
                break;

            case 'dati-password':
                Validator::make($request->input(), [
                    'password_attuale' => new PasswordAttualeRule(),
                    'password' => $this->passwordRules(),
                ])->validate();
                $this->updatePassword($request);
                $alert = new AlertMessage();
                $alert->messaggio('La tua password è stata modificata ')->flash();
                break;

            case 'preferenze-notifiche':
                $this->updatePreferenzeNotifiche($request);
                $alert = new AlertMessage();
                $alert->messaggio('Preferenze notifiche aggiornate')->flash();
                break;

            case 'preferenze-locale':
                Validator::make($request->input(), [
                    'fuso_orario' => ['required', 'timezone'],
                    'formato_data' => ['required', Rule::in(['d/m/Y', 'Y-m-d'])],
                    'formato_numeri_valuta' => ['required', Rule::in(['it_IT', 'en_US'])],
                ])->validate();
                $this->updatePreferenzeLocale($request);
                $alert = new AlertMessage();
                $alert->messaggio('Preferenze locali aggiornate')->flash();
                break;

            case 'sicurezza-sessioni':
                Validator::make($request->input(), [
                    'password_sessioni' => [new PasswordAttualeRule()],
                ])->validate();
                Auth::logoutOtherDevices($request->input('password_sessioni'));
                $alert = new AlertMessage();
                $alert->messaggio('Tutte le altre sessioni sono state disconnesse')->flash();
                break;

            case 'openapi-credenziali':
                abort_unless($this->currentUser()->hasPermissionTo('agente') && $this->currentUser()->agente, 403);
                Validator::make($request->input(), [
                    'openapi_visure_token' => ['nullable', 'string', 'max:2048'],
                    'openapi_catasto_token' => ['nullable', 'string', 'max:2048'],
                ])->validate();

                $this->currentUser()->agente->openapi_visure_token = trim((string) $request->input('openapi_visure_token')) ?: null;
                $this->currentUser()->agente->openapi_catasto_token = trim((string) $request->input('openapi_catasto_token')) ?: null;
                $this->currentUser()->agente->save();

                $alert = new AlertMessage();
                $alert->messaggio('Credenziali OpenAPI visure aggiornate')->flash();
                break;



        }

        return redirect()->back();
    }




    protected function updateDatiUtente($request)
    {
        $user = $this->currentUser();
        $user->nome = getInputUcwords($request->input('nome'));
        $user->cognome = getInputUcwords($request->input('cognome'));
        $user->telefono = getInputTelefono($request->input('telefono'));
        //$user->codice_fiscale = strtoupper($request->input('codice_fiscale'));
        //$user->iban = $request->input('iban');
        $user->save();

    }

    protected function updateEmail($request)
    {
        $user = $this->currentUser();
        $user->email = $request->input('email');
        $user->save();

    }

    protected function updatePassword($request)
    {
        $user = $this->currentUser();
        $user->password = Hash::make($request->input('password'));
        $user->save();

    }

    protected function passwordRules()
    {
        return ['required', 'string', new Password, 'confirmed'];
    }

    public function exportDatiPersonali()
    {
        $user = $this->currentUser();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'utente' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'cognome' => $user->cognome,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'ultimo_accesso' => optional($user->ultimo_accesso)->toIso8601String(),
                'email_verificata_il' => optional($user->email_verified_at)->toIso8601String(),
                'ruoli' => $user->getRoleNames()->values()->all(),
                'permessi' => $user->getPermissionNames()->values()->all(),
                'extra' => $user->extra,
            ],
            'login_recenti' => RegistroLogin::where('user_id', $user->id)
                ->latest('id')
                ->limit(50)
                ->get(['created_at', 'email', 'ip', 'riuscito', 'remember', 'user_agent'])
                ->toArray(),
        ];

        $filename = 'dati-utente-' . $user->id . '-' . now()->format('Ymd_His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    protected function updatePreferenzeNotifiche(Request $request): void
    {
        $user = $this->currentUser();
        $user->setExtra([
            'notifiche_email_ticket' => $request->boolean('notifiche_email_ticket'),
            'notifiche_email_spedizioni' => $request->boolean('notifiche_email_spedizioni'),
            'notifiche_email_amministrative' => $request->boolean('notifiche_email_amministrative'),
            'notifiche_browser' => $request->boolean('notifiche_browser'),
        ]);
    }

    protected function updatePreferenzeLocale(Request $request): void
    {
        $user = $this->currentUser();
        $user->setExtra([
            'fuso_orario' => $request->input('fuso_orario'),
            'formato_data' => $request->input('formato_data'),
            'formato_numeri_valuta' => $request->input('formato_numeri_valuta'),
        ]);
    }


}
