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

        Schema::create('prodotto_energia_egea', function (Blueprint $table) {
            $table->id('contratto_energia_id');
            $table->timestamps();
            $table->boolean('chiede_esecuzione_anticipata')->default(0);
            $table->boolean('residente_fornitura')->default(0);

            //Indirizzo fatturazione
            $table->string('spedizione_fattura');
            $table->string('indirizzo_fatturazione')->nullable();
            $table->string('cap_fatturazione',5)->nullable();
            $table->string('comune_fatturazione')->nullable();
            $table->string('nominativo_residente_fatturazione')->nullable();

            //Mandato per addebito diretto SEPA - Dati bancari (da compilare in stampatello)
            $table->string('banca');
            $table->string('agenzia_filiale');
            $table->string('iban');
            $table->string('bic_swift');

            //Dati del punto fornitura di gas metano
            $table->string('tipo_attivazione_gas')->nullable();
            $table->string('pdr')->nullable();
            $table->string('matricola_contatore')->nullable();
            $table->string('cat_uso_arera')->nullable();
            $table->string('cabina_remi')->nullable();
            $table->string('tipologia_pdr')->nullable();
            $table->string('distributore_locale')->nullable();
            $table->string('soc_vendita_attuale_gas')->nullable();
            $table->string('mercato_attuale_gas')->nullable();
            $table->string('potenzialita_impianto')->nullable();
            $table->string('consumo_anno_termico')->nullable();

            //Dati del punto di fornitura di energia elettrica
            $table->string('tipo_attivazione_luce')->nullable();
            $table->string('pod')->nullable();
            $table->string('tipologia_uso')->nullable();
            $table->string('tensione')->nullable();
            $table->string('potenza_contrattuale')->nullable();
            $table->string('consumo_anno')->nullable();
            $table->string('mercato_provenienza_luce')->nullable();
            $table->string('soc_vendita_attuale_luce')->nullable();
            $table->string('mercato_attuale_luce')->nullable();

            //Indirizzo di fornitura
            $table->string('indirizzo_fornitura')->nullable();
            $table->string('cap_fornitura',5)->nullable();
            $table->string('comune_fornitura')->nullable();

            $table->string('dichiara_di_essere')->nullable();

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_energia_egea');
    }
};
