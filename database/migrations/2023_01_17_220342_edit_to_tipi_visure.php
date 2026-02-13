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
        Schema::table('tipi_visure', function (Blueprint $table) {
            $table->string('tipo_visura');
            $table->boolean('richiedi_allegati')->default(0);
        });


        Schema::table('visure', function (Blueprint $table) {
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('email')->nullable();
            $table->string('cellulare')->nullable();
            $table->string('codice_fiscale')->nullable();

            $table->string('indirizzo')->nullable();
            $table->string('cap', 5)->nullable();

            $table->dropColumn('partita_iva');

        });

        Schema::table('visure_allegati', function (Blueprint $table) {
            $table->boolean('per_cliente')->default(0)->index();

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipi_visure', function (Blueprint $table) {
            //
        });
    }
};
