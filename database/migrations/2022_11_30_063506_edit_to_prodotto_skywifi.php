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
        Schema::table('prodotto_skywifi', function (Blueprint $table) {
            $table->string('linea_telefonica')->after('pacchetti_voce');
            $table->string('numero_da_migrare')->nullable()->after('linea_telefonica');
            $table->string('codice_migrazione_voce')->nullable()->after('numero_da_migrare');
            $table->string('codice_migrazione_dati')->nullable()->after('codice_migrazione_voce');
            $table->dropColumn('profilo');
            $table->dropColumn('codice_promozione');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prodotto_skywifi', function (Blueprint $table) {
            $table->dropColumn('linea_telefonica');
            $table->dropColumn('numero_da_migrare');
            $table->dropColumn('codice_migrazione_voce');
            $table->dropColumn('codice_migrazione_dati');
        });
    }
};
