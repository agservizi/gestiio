<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('allegati_servizi')) {
            DB::table('allegati_servizi')
                ->whereIn('allegato_type', [
                    'App\\Models\\ServizioFinanziario',
                    'App\\Models\\Comparasemplice',
                ])
                ->delete();
        }

        Schema::dropIfExists('attivazioni_sim_sostituzioni');
        Schema::dropIfExists('attivazioni_sim_allegati');
        Schema::dropIfExists('attivazioni_sim');

        Schema::dropIfExists('servizio_prestiti');
        Schema::dropIfExists('servizio_polizze');
        Schema::dropIfExists('servizio_mutui');
        Schema::dropIfExists('servizio_polizze_facile');
        Schema::dropIfExists('servizi_finanziari');
        Schema::dropIfExists('tab_esiti_servizi');

        Schema::dropIfExists('comparasemplice');
        Schema::dropIfExists('comparasemplice_esiti');

        Schema::dropIfExists('segnalazioni');
        Schema::dropIfExists('tab_esiti_segnalazioni');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Migrazione distruttiva: rollback manuale non supportato.
    }
};
