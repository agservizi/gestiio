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
        Schema::create('prodotto_vodafone_fissa', function (Blueprint $table) {
            $table->id('contratto_id');
            $table->timestamps();
            $table->string('offerta');
            $table->string('tecnologia');
            $table->string('metodo_pagamento');
            $table->string('numero_da_migrare')->nullable();
            $table->string('gestore_linea_esistente')->nullable();
            $table->string('codice_migrazione')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prodotto_vodafone_fissa');
    }
};
