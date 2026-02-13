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

        Schema::create('tab_esiti_attivazioni_sim', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->boolean('chiedi_motivo')->default(0);
            $table->boolean('notifica_mail')->default(0);
            $table->boolean('attivo')->index();
            $table->string('esito_finale')->nullable();

        });

        Schema::create('tab_gestori_attivazioni_sim', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->string('url')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('attivo')->index();
            $table->string('email_notifica_a_gestore')->nullable();
            $table->string('titolo_notifica_a_gestore')->nullable();
            $table->string('testo_notifica_a_gestore')->nullable();
            $table->boolean('includi_dati_contratto')->default(0);
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tab_gestori_attivazioni_sim');
        Schema::dropIfExists('tab_esiti_attivazioni_sim');
    }
};
