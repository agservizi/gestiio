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
        Schema::create('prodotto_skyglass', function (Blueprint $table) {
            $table->id('contratto_id');
            $table->timestamps();


            $table->string('dimensione');
            $table->string('colore_sky_glass');
            $table->boolean('accessori')->default(0);
            $table->string('colore_front_cover')->nullable();
            $table->string('sky_stream')->nullable();
            $table->boolean('installazione_a_muro')->default(0);
            $table->json('pacchetti_sky')->nullable();
            $table->json('pacchetti_netflix')->nullable();
            $table->json('servizi_opzionali')->nullable();

            $table->string('frequenza_pagamento_sky_glass')->nullable();
            $table->string('metodo_pagamento_sky_glass')->nullable();
            $table->string('metodo_pagamento_tv')->nullable();

            $table->foreign('contratto_id')->on('contratti')->references('id')->onDelete('cascade');


        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_skyglass');
    }
};
