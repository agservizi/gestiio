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
        Schema::table('prodotto_energia_enelconsumer', function (Blueprint $table) {
            $table->boolean('voltura_ordinaria_contestuale');
            $table->boolean('voltura_mortis_causa');
        });

        Schema::table('prodotto_energia_enelbusiness', function (Blueprint $table) {
            $table->boolean('voltura_ordinaria_contestuale');
            $table->boolean('voltura_mortis_causa');
        });


        //modalita_pagamento ah dimenticavo puoi mettere su entrambi enel ed enel business le spunte *Voltura ordinaria contestuale e voltura mortis causa*?
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enel', function (Blueprint $table) {
            //
        });
    }
};
