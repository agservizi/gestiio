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
        $agenti = \App\Models\Agente::get();
        foreach ($agenti as $agente) {
            $user = \App\Models\User::find($agente->user_id);
            $user->alias = $agente->ragione_sociale ?: $user->nominativo();
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
