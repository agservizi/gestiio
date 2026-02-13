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
        Schema::create('prodotto_tim_wifi', function (Blueprint $table) {
            $table->id('contratto_id');
            $table->timestamps();


            $table->string('indirizzo_fattura')->nullable();
            $table->string('citta_fattura')->nullable();
            $table->string('cap_fattura')->nullable();

            $table->string('indirizzo_timcard')->nullable();
            $table->string('citta_timcard')->nullable();
            $table->string('cap_timcard')->nullable();

            $table->string('la_tua_linea_di_casa');
            $table->string('variazione_numero')->nullable();

            $table->boolean('linea_mobile_tim')->default(0);
            $table->boolean('linea_mobile_new')->default(0);
            $table->boolean('linea_mobile_abbonamento')->default(0);
            $table->boolean('linea_mobile_prepagato')->default(0);
            $table->string('linea_mobile_operatore')->nullable();
            $table->string('linea_mobile_abbinata_offerta')->nullable();
            $table->string('linea_mobile_cf_piva_attuale')->nullable();
            $table->string('linea_mobile_numero_seriale')->nullable();
            $table->boolean('linea_mobile_trasferimento_credito')->default(0);

            $table->string('la_tua_offerta');
            $table->string('opzione_inclusa')->nullable();
            $table->boolean('qualora')->default(0);
            $table->string('modem_tim')->nullable();
            $table->string('offerta_scelta')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_tim_wifi');
    }
};
