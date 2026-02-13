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
            $table->boolean('convergenza_mobile')->nullable();
            $table->boolean('easy_pay')->nullable();
            $table->string('smartphone_a_rate')->nullable();
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
            $table->dropColumn('convergenza_mobile');
            $table->dropColumn('smartphone_a_rate');
            $table->dropColumn('easy_pay');
        });
    }
};
