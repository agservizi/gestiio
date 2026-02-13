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
        Schema::create('prodotto_skytv', function (Blueprint $table) {
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


        Schema::table('contratti', function (Blueprint $table) {
            $table->string('comune_rilascio')->nullable()->after('rilasciato_da');
            $table->string('permesso_soggiorno_numero')->nullable()->after('cittadinanza');
            $table->date('permesso_soggiorno_scadenza')->nullable()->after('permesso_soggiorno_numero');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_skytv');
    }
};
