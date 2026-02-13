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
        Schema::create('prodotto_energia_enelconsumer', function (Blueprint $table) {
            $table->id('contratto_energia_id');
            $table->timestamps();
            $table->boolean('residente_fornitura');


            //Indirizzo di fornitura
            $table->string('indirizzo_fornitura')->nullable();
            $table->string('nr_fornitura')->nullable();
            $table->string('scala_fornitura')->nullable();
            $table->string('interno_fornitura')->nullable();
            $table->string('cap_fornitura', 5)->nullable();
            $table->string('comune_fornitura')->nullable();

            //Indirizzo fatturazione
            $table->string('indirizzo_fatturazione')->nullable();
            $table->string('presso_fatturazione')->nullable();
            $table->string('nr_fatturazione')->nullable();
            $table->string('scala_fatturazione')->nullable();
            $table->string('interno_fatturazione')->nullable();
            $table->string('cap_fatturazione', 5)->nullable();
            $table->string('comune_fatturazione')->nullable();

            //Dati del punto di fornitura di energia elettrica
            $table->string('pod')->nullable();
            $table->boolean('provenienza_mercato_libero');
            $table->string('consumo_annuo_luce')->nullable();
            $table->string('potenza_contrattuale')->nullable();
            $table->string('attuale_societa_luce')->nullable();

            //Dati del punto fornitura di gas metano
            $table->string('pdr')->nullable();
            $table->string('consumo_annuo_gas')->nullable();
            $table->string('attuale_societa_gas')->nullable();
            $table->boolean('riscaldamento');
            $table->boolean('cottura_acqua_calda');

            $table->string('codice_destinatario')->nullable();
            $table->string('indirizzo_pec')->nullable();

            $table->string('modalita_pagamento')->nullable();
            $table->string('invio_fattura')->nullable();

            $table->string('titolare_cc')->nullable();
            $table->string('codice_fiscale_titolare')->nullable();
            $table->string('telefono_titolare')->nullable();

            $table->string('iban')->nullable();
            $table->string('bic_swift')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_energia_enelconsumer');
    }
};
