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
        Schema::create('tipi_visure', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->text('html')->nullable();
            $table->decimal('prezzo_cliente');
            $table->decimal('prezzo_agente');
            $table->boolean('abilitato')->index();

        });


        Schema::create('tab_esiti_visure', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->boolean('chiedi_motivo')->default(0);
            $table->boolean('notifica_mail')->default(0);
            $table->boolean('attivo')->index();
            $table->string('esito_finale')->nullable();
        });

        Schema::create('visure', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->foreignId('tipo_visura_id')->constrained('tipi_visure');
            $table->string('partita_iva');
            $table->string('ragione_sociale')->nullable();
            $table->string('citta')->nullable();

            $table->text('note')->nullable();
            $table->string('uid')->index();


            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);
            $table->decimal('provvigione_agente')->default(0);
            $table->decimal('provvigione_agenzia')->default(0);
            $table->decimal('prezzo_pratica');

        });


        Schema::create('visure_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('visura_id')->nullable()->constrained('visure')->cascadeOnDelete();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
            $table->string('thumbnail')->nullable();

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visure_allegati');
        Schema::dropIfExists('visure');
        Schema::dropIfExists('tab_esiti_visure');
        Schema::dropIfExists('tipi_visure');
    }
};
