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
        Schema::create('prodotto_skywifi', function (Blueprint $table) {
            $table->id('contratto_id');
            $table->timestamps();
            $table->string('codice_cliente')->nullable();
            $table->string('profilo');
            $table->string('codice_promozione')->nullable();
            $table->string('tipologia_cliente');

            $table->string('offerta');
            $table->boolean('modem_wifi_hub')->default(0);
            $table->boolean('ultra_wifi')->default(0);
            $table->unsignedTinyInteger('wifi_spot')->default(0);


            $table->json('pacchetti_voce')->nullable();

            $table->string('metodo_pagamento_internet')->nullable();
            $table->string('carta_di_credito_tipo')->nullable();
            $table->string('carta_di_credito_numero')->nullable();
            $table->string('carta_di_credito_cognome')->nullable();
            $table->string('carta_di_credito_nome')->nullable();
            $table->string('carta_di_credito_valida_al')->nullable();
            $table->string('sepa_banca')->nullable();
            $table->string('sepa_agenzia')->nullable();
            $table->string('sepa_iban')->nullable();
            $table->string('sepa_intestatario')->nullable();
            $table->string('sepa_via')->nullable();
            $table->string('sepa_codice_fiscale')->nullable();
            $table->boolean('consenso_1')->default(0);
            $table->boolean('consenso_2')->default(0);
            $table->boolean('consenso_3')->default(0);
            $table->boolean('consenso_4')->default(0);
            $table->boolean('consenso_5')->default(0);
            $table->boolean('consenso_6')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_skywifi');
    }
};
