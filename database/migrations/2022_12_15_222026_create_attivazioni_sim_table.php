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
        Schema::create('attivazioni_sim', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->foreignId('gestore_id')->constrained('tab_gestori_attivazioni_sim');
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('cellulare');
            $table->string('codice_fiscale');
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');

            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->text('note')->nullable();
            $table->string('uid')->index();

            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);

            $table->string('codice_sim');
            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->date('data_scadenza');

        });

        Schema::create('attivazioni_sim_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('attivazioni_sim_id')->nullable()->constrained('attivazioni_sim')->cascadeOnDelete();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attivazioni_sim_allegati');
        Schema::dropIfExists('attivazioni_sim');
    }
};
