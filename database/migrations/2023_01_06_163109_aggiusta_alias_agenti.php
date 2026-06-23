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
        $agenti = Agente::get();
        foreach ($agenti as $agente) {
            $user = User::find($agente->user_id);
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
