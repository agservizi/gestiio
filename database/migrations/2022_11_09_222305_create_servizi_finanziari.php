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

        Schema::create('tab_esiti_servizi', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->boolean('chiedi_motivo')->default(0);
            $table->boolean('notifica_mail')->default(0);
            $table->boolean('notifica_whatsapp')->default(0);
            $table->boolean('attivo')->index();
            $table->string('esito_finale')->nullable();

        });


        Schema::create('servizi_finanziari', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('cellulare');
            $table->string('codice_fiscale');
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');

            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);

            //Prodotti
            $table->unsignedBigInteger('prodotto_id')->nullable();
            $table->string('prodotto_type')->nullable();
            $table->index(['prodotto_id', 'prodotto_type']);


        });


        Schema::create('servizio_prestiti', function (Blueprint $table) {
            $table->id('servizio_id');
            $table->timestamps();
            $table->decimal('importo_prestito');
            $table->unsignedTinyInteger('durata_prestito');
            $table->string('stato_civile');
            $table->string('immobile_residenza');
            $table->string('telefono_fisso');
            $table->boolean('prestiti_in_corso');
            $table->boolean('prestiti_in_passato');
            $table->string('motivazione_prestito');
            $table->unsignedTinyInteger('componenti_famiglia');
            $table->unsignedTinyInteger('componenti_famiglia_con_reddito');
            $table->string('lavoro');
            $table->string('datore_lavoro_intestazione')->nullable();
            $table->unsignedTinyInteger('mesi_anzianita_servizio')->nullable();
            $table->unsignedTinyInteger('anni_anzianita_servizio')->nullable();
            $table->string('indirizzo_lavoro')->nullable();
            $table->string('citta_lavoro')->nullable();
            $table->string('telefono_lavoro')->nullable();
            $table->string('titolo_studio')->nullable();
            $table->decimal('reddito_mensile');

            $table->foreign('servizio_id')->on('servizi_finanziari')->references('id')->onDelete('cascade');


        });

        //TARGA - DATA DI NASCITA - EMAIL - CELLULARE

        Schema::create('servizio_polizze', function (Blueprint $table) {
            $table->id('servizio_id');
            $table->timestamps();
            $table->string('targa');
            $table->date('data_di_nascita');

            $table->foreign('servizio_id')->on('servizi_finanziari')->references('id')->onDelete('cascade');


        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('servizio_prestiti');
        Schema::dropIfExists('servizio_polizze');
        Schema::dropIfExists('servizi_finanziari');
        Schema::dropIfExists('tab_esiti_servizi');
    }
};
