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
        Schema::create('servizio_polizze_facile', function (Blueprint $table) {
            $table->id('servizio_id');
            $table->timestamps();
            $table->string('targa');
            $table->date('data_di_nascita');
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
        Schema::dropIfExists('servizio_polizze_facile');
    }
};
