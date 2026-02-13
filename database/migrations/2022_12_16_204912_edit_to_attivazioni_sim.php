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
        Schema::table('attivazioni_sim', function (Blueprint $table) {
            $table->string('mnp')->nullable();
            $table->foreignId('offerta_sim_id')->after('gestore_id')->constrained('offerte_sim');
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
            //
        });
    }
};
