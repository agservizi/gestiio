<?php

use App\Models\Agente;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        foreach (User::get() as $user) {
            $user->nome = $user->name;
            $user->save();
        }

        Agente::truncate();
        foreach (User::whereHas('permissions')->get() as $user) {
            $agente = new Agente;
            $agente->user_id = $user->id;
            $agente->codice_fiscale = $user->codice_fiscale;
            $agente->ragione_sociale = $user->ragione_sociale;
            $agente->listino_telefonia_id = $user->listino_telefonia_id;
            $agente->iban = $user->iban;
            $agente->save();

            $user->alias = $user->nominativo();
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
