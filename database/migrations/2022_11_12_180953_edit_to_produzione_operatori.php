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
        Schema::table('produzioni_operatori', function (Blueprint $table) {
            $table->dropColumn('importo_totale');
        });


        Schema::table('produzioni_operatori', function (Blueprint $table) {
            $table->decimal('importo_totale')->storedAs('importo_ordini+importo_servizi_finanziari');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produzione_operatori', function (Blueprint $table) {
            //
        });
    }
};
