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
            $table->decimal('importo_servizi_finanziari')->default(0);
        });

        Schema::table('guadagni_agenzia', function (Blueprint $table) {
            $table->decimal('entrate_contratti')->default(0);
            $table->decimal('uscite_contratti')->default(0);
            $table->decimal('entrate_servizi')->default(0);
            $table->decimal('uscite_servizi')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produzioni_operatori', function (Blueprint $table) {
        });
    }
};
