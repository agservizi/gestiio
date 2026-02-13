<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Funzioni\FunzioniNoleggiatore;
use App\Models\Agente;
use App\Models\Noleggiatore;
use App\Models\User;
use App\Notifications\NotificaRegistrazioneAgente;
use App\Rules\CodiceDestinatarioRule;
use App\Rules\CodiceFiscaleRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use SoapClient;

class RegistratiController extends Controller
{

    public function post(Request $request)
    {

        $request->validate([
            'ragione_sociale' => ['nullable', 'string', 'max:255'],
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'codice_fiscale' => ['nullable', new CodiceFiscaleRule()],
            'partita_iva' => ['nullable', new PartitaIvaRule()],
            'citta' => ['required'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'cap' => ['required', 'string', 'max:10'],
            'telefono' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),

            ],
            'pec' => ['nullable', 'email'],
            'codice_destinatario' => ['nullable', new CodiceDestinatarioRule($request->input('nazione'))],
            'visura_camerale' => ['nullable', 'file'],
            'password' => ['required', 'string', new PasswordRules(), 'confirmed'],

        ]);
        \DB::beginTransaction();
        $user = new User();
        $this->salvaDatiUtente($user, $request);
        $this->salvaDatiAgente(new Agente(), $request, $user);
        $user->givePermissionTo('agente');
        \DB::commit();
        \Auth::login($user);

        dispatch(function () use ($user) {

User::find(2)->notify(new NotificaRegistrazioneAgente($user));


        })->afterResponse();

        return redirect()->action([RegistratiController::class, 'show']);
    }

    public function show()
    {
        return view('auth.registratoAgente');
    }

    /**
     * @param User $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiUtente($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->password = \Hash::make('password');
        }

        //Ciclo su campi
        $campi = [
            'nome' => 'App\getInputUcfirst',
            'cognome' => 'App\getInputUcfirst',
            'email' => 'strtolower',
            'telefono' => 'App\getInputTelefono',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        $model->save();


        return $model;
    }

    /**
     * @param Agente $model
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    protected function salvadatiAgente($model, $request, $user)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->user_id = $user->id;
        }

        //Ciclo su campi
        $campi = [
            'ragione_sociale' => '',
            'codice_fiscale' => 'strtoupper',
            'partita_iva' => '',
            'indirizzo' => '',
            'cap' => '',
            'citta' => '',
            'iban' => 'strtoupper',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        $model->save();

        $user->alias = $model->ragione_sociale ?: ($user->cognome . ' ' . $user->nome);
        $user->save();

        if ($request->file('visura_camerale')) {

            $tmpFile = $request->file('visura_camerale');
            $extensione = $tmpFile->getClientOriginalExtension();
            $filename = hexdec(uniqid()) . '.' . $extensione;
            $cartella = config('configurazione.visure_camerali.cartella');
            $tmpFile->storeAs($cartella, $filename);
            $model->visura_camerale = $cartella . '/' . $filename;
            $model->save();

        }

        return $model;
    }

    /**
     * @param $request
     * @return array
     */
    public function verificaPIvaEu(Request $request)
    {
        $countryCode = $request->input('nazione_id', 'IT');
        $vatNo = $request->input('partita_iva');

        if (!$vatNo) {
            return ['success' => false, 'html' => base64_encode('<strong>Devi specificare la partita iva</strong>')];
        }

        try {
            $client = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
            $res = $client->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $vatNo,
            ]);


            return ['success' => true, 'partita_iva' => $vatNo, 'res' => $res];
        } catch (\Exception $exception) {
            $html = 'Si è verificato un errore: ' . $exception->getMessage();
            return ['success' => false, 'html' => $html];

        }

    }


}
