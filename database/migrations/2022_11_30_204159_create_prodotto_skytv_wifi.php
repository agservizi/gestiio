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
        Schema::create('prodotto_skytv_wifi', function (Blueprint $table) {
            $table->id('contratto_id');
            $table->timestamps();
            $table->string('codice_cliente')->nullable();
            $table->string('profilo');
            $table->string('codice_promozione')->nullable();
            $table->string('tipologia_cliente');
            $table->json('pacchetti_sky')->nullable();
            $table->json('servizi_opzionali')->nullable();
            $table->json('offerte_sky')->nullable();
            $table->json('canali_opzionali')->nullable();
            $table->json('servizio_decoder')->nullable();
            $table->string('tecnologia')->nullable();
            $table->char('sky_q_mini', 1)->nullable();
            $table->boolean('solo_smartcard')->default(0);
            $table->string('matricola_smartcard')->nullable();
            $table->string('metodo_pagamento_tv')->nullable();
            $table->string('frequenza_pagamento_tv')->nullable();


            $table->string('offerta');
            $table->boolean('modem_wifi_hub')->default(0);
            $table->boolean('ultra_wifi')->default(0);
            $table->unsignedTinyInteger('wifi_spot')->default(0);

            $table->json('pacchetti_voce')->nullable();
            $table->string('linea_telefonica');
            $table->string('numero_da_migrare')->nullable();
            $table->string('codice_migrazione_voce')->nullable();
            $table->string('codice_migrazione_dati')->nullable();
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
        Schema::dropIfExists('prodotto_skytv_wifi');
    }
};
