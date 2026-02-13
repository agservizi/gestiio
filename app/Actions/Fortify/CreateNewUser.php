<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\CodiceDestinatarioRule;
use App\Rules\CodiceFiscaleRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PasswordRules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Rules\Password;
use function App\getInputTelefono;
use function App\getInputUcfirst;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param array $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'nazione' => ['required'],
            'cookies-privacy-policy' => ['required'],


            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'confirmed',
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', new PasswordRules(), 'confirmed'],
        ])->validate();


        return $this->salvaDati(new User(), $input);
        /*
         *             'nome' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'codice_fiscale' => ['nullable', new CodiceFiscaleRule()],
            'nazione' => ['required'],
            'citta' => ['required_if:nazione,IT'],
            'citta_estera' => ['required_unless:nazione,IT',],
            'indirizzo' => ['required', 'string', 'max:255'],
            'cap' => ['required', 'string', 'max:10'],
            'cookies-privacy-policy' => ['required'],

         */
    }


    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'cognome' => 'App\getInputUcfirst',
            'nazione' => '',
            'email' => 'strtolower',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request[$campo];
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->name = $request['nome'];
        $model->password = Hash::make($request['password']);

        $model->save();
        return $model;
    }

}
