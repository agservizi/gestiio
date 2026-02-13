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
        Schema::table('contratti_allegati', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();
        });
        Schema::table('contratti_energia_allegati', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();
        });
        Schema::table('attivazioni_sim_allegati', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();
        });
        Schema::table('caf_patronato_allegati', function (Blueprint $table) {
            $table->string('thumbnail')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contratti_allegati', function (Blueprint $table) {
            //
        });
    }
};
