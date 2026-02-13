<?php

namespace App\Http\Controllers\Backend;

use App\Http\MieClassi\AlertMessage;
use App\Models\Documento;
use App\Models\DocumentoAcquistato;
use App\Models\DownloadGratuiti;
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

        return view('Backend.DatiUtente.editDatiUtente', [
            'record' => $this->currentUser(),
            'controller' => AreaPersonaleController::class
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



        }

        return redirect()->action([AreaPersonaleController::class, 'show']);
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


}
