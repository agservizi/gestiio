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
        Schema::create('prodotto_energia_enelbusiness', function (Blueprint $table) {
            $table->id('contratto_energia_id');
            $table->timestamps();


            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('scala', 10)->nullable();
            $table->string('interno', 10)->nullable();

            $table->string('partita_iva')->nullable();
            $table->string('forma_giuridica')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('fax')->nullable();

            //Referente
            $table->string('nome_cognome_referente')->nullable();
            $table->string('codice_fiscale_referente')->nullable();
            $table->string('telefono_referente')->nullable();

            //Indirizzo sede legale
            $table->string('indirizzo_sede')->nullable();
            $table->string('nr_sede')->nullable();
            $table->string('cap_sede', 5)->nullable();
            $table->string('comune_sede')->nullable();


            //Indirizzo fatturazione
            $table->string('c_o')->nullable();
            $table->string('indirizzo_fatturazione')->nullable();
            $table->string('nr_fatturazione')->nullable();
            $table->string('cap_fatturazione', 5)->nullable();
            $table->string('comune_fatturazione')->nullable();


            $table->string('codice_destinatario', 7)->nullable();
            $table->date('data_inizio_validita')->nullable();
            $table->date('data_fine_validita')->nullable();
            $table->string('cig')->nullable();
            $table->string('cup')->nullable();


            //Indirizzo di fornitura luce
            $table->string('pod')->nullable();
            $table->boolean('provenienza_mercato_libero');
            $table->boolean('uso_non_professionale_luce');
            $table->string('consumo_annuo_luce')->nullable();
            $table->string('potenza_contrattuale')->nullable();
            $table->string('livello_tensione')->nullable();
            $table->string('attuale_societa_luce')->nullable();

            $table->string('indirizzo_fornitura_luce')->nullable();
            $table->string('nr_fornitura_luce')->nullable();
            $table->string('cap_fornitura_luce', 5)->nullable();
            $table->string('comune_fornitura_luce')->nullable();


            //Dati del punto fornitura di gas metano
            $table->string('pdr')->nullable();
            $table->boolean('uso_non_professionale_gas');
            $table->string('attuale_societa_gas')->nullable();
            $table->string('profilo_consumo')->nullable();
            $table->string('posizione_contatore')->nullable();
            $table->unsignedSmallInteger('consumo_annuo')->nullable();
            $table->string('matricola_contatore')->nullable();

            $table->string('indirizzo_fornitura_gas')->nullable();
            $table->string('nr_fornitura_gas')->nullable();
            $table->string('cap_fornitura_gas', 5)->nullable();
            $table->string('comune_fornitura_gas')->nullable();

            $table->string('modalita_pagamento')->nullable();
            $table->string('invio_fattura')->nullable();


            $table->string('titolare_cc')->nullable();
            $table->string('codice_fiscale_titolare')->nullable();
            $table->string('cognome_nome_sottoscrittore')->nullable();
            $table->string('recapito_telefonico_titolare')->nullable();


            $table->string('iban')->nullable();
            $table->string('iban_sepa')->nullable();

            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('rilasciato_da')->nullable();
            $table->date('data_rilascio')->nullable();
            $table->date('data_scadenza')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_energia_enelbusiness');
    }
};
