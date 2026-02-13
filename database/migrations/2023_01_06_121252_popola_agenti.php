<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        foreach (\App\Models\User::get() as $user) {
            $user->nome = $user->name;
            $user->save();
        }

        \App\Models\Agente::truncate();
        foreach (\App\Models\User::whereHas('permissions')->get() as $user) {
            $agente = new \App\Models\Agente();
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
