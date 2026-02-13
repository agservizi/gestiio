<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->string('testo_ricerca')->index();
            $table->dropIndex('contratti_energia_codice_contratto_index');
            $table->dropIndex('contratti_energia_denominazione_index');
        });

        foreach (\App\Models\ContrattoEnergia::withoutGlobalScope('filtroOperatore')->get() as $contratto) {
            $contratto->testo_ricerca = $contratto->denominazione . '|' . $contratto->codice_contratto;
            $contratto->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contratti_energia', function (Blueprint $table) {
            //
        });
    }
};
