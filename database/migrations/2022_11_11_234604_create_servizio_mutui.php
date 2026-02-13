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
        Schema::create('servizio_mutui', function (Blueprint $table) {
            $table->id('servizio_id');
            $table->timestamps();
            $table->string('finalita');
            $table->string('tipo_di_tasso');
            $table->decimal('valore_immobile');
            $table->decimal('importo_del_mutuo');
            $table->unsignedTinyInteger('durata');
            $table->date('data_di_nascita');
            $table->string('posizione_lavorativa');
            $table->decimal('reddito_richiedenti');

            $table->string('comune_domicilio');
            $table->string('comune_immobile');
            $table->string('stato_ricerca_immobile');


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
        Schema::dropIfExists('servizio_mutui');
    }
};
