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
        Schema::table('prodotto_energia_egea', function (Blueprint $table) {
            $table->string('cognome')->nullable();
            $table->string('nome');
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('scala', 10)->nullable();
            $table->string('interno', 10)->nullable();

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
        Schema::table('prodotto_energia_egea', function (Blueprint $table) {
            //
        });
    }
};
