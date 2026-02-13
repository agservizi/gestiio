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
        Schema::create('prodotto_energia_illumia', function (Blueprint $table) {
            $table->id('contratto_energia_id');
            $table->timestamps();
            $table->json('fornitura_richiesta');
            $table->json('fasce_reperibilita');

            //DATI TECNICI ENERGIA ELETTRICA
            $table->string('attuale_fornitore_luce')->nullable();
            $table->string('pod')->nullable();
            $table->string('indirizzo_fornitura_luce')->nullable();
            $table->string('civico_fornitura_luce')->nullable();
            $table->string('comune_fornitura_luce')->nullable();
            $table->string('cap_fornitura_luce',5)->nullable();

            //DATI TECNICI GAS NATURALE
            $table->string('attuale_fornitore_gas')->nullable();
            $table->string('pdr')->nullable();
            $table->json('tipologia_uso_gas')->nullable();
            $table->string('codice_remi')->nullable();
            $table->string('indirizzo_fornitura_gas')->nullable();
            $table->string('civico_fornitura_gas')->nullable();
            $table->string('comune_fornitura_gas')->nullable();
            $table->string('cap_fornitura_gas',5)->nullable();

            //MODALITÀ DI PAGAMENTO E SPEDIZIONE FATTURA
            $table->string('modalita_pagamento_fattura');
            $table->string('intestatario_conto_corrente');
            $table->string('codice_fiscale_intestatario');
            $table->string('iban');
            $table->string('modalita_spedizione_fattura');
            $table->string('indirizzo_spedizione_fattura')->nullable();
            $table->string('civico_spedizione_fattura')->nullable();
            $table->string('comune_spedizione_fattura')->nullable();
            $table->string('cap_spedizione_fattura')->nullable();
            $table->string('c_o')->nullable();
            $table->string('virtu_titolo')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_energia_illumia');
    }
};
