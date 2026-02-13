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
        Schema::table('attivazioni_sim', function (Blueprint $table) {
            $table->boolean('giga_illimitati_superfibra')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attivazioni_sim', function (Blueprint $table) {
            $table->dropColumn('giga_illimitati_superfibra');

        });
    }
};
