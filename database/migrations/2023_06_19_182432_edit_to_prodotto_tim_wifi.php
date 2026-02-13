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
        Schema::table('prodotto_tim_wifi', function (Blueprint $table) {
            $table->string('codice_migrazione')->nullable();
            $table->string('numero_telefonico')->nullable();
            $table->boolean('pagamento_bollettino');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prodotto_tim_wifi', function (Blueprint $table) {
            $table->dropColumn('codice_migrazione');
            $table->dropColumn('numero_telefonico');
        });
    }
};
