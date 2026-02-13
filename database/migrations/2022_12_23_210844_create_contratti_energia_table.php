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

        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->string('model_prodotto')->nullable();
        });


        Schema::create('contratti_energia', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->foreignId('gestore_id')->constrained('tab_gestori_attivazioni_sim');
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('telefono');
            $table->string('codice_fiscale');
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');

            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('scala', 10)->nullable();
            $table->string('interno', 10)->nullable();


            $table->text('note')->nullable();
            $table->string('uid')->index();

            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);

            //Documento
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('rilasciato_da')->nullable();
            $table->date('data_rilascio')->nullable();
            $table->date('data_scadenza')->nullable();


            $table->unsignedBigInteger('prodotto_id')->nullable();
            $table->string('prodotto_type')->nullable();
            $table->index(['prodotto_id', 'prodotto_type']);


        });

        Schema::create('contratti_energia_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('contratto_energia_id')->nullable()->constrained('contratti_energia')->cascadeOnDelete();
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
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->dropColumn('model_prodotto');
        });

        Schema::dropIfExists('contratti_energia_allegati');
        Schema::dropIfExists('contratti_energia');
    }
};
