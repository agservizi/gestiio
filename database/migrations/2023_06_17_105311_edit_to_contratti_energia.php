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
        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->string('codice_contratto')->nullable()->index();
            $table->string('denominazione')->nullable()->index()->after('gestore_id');
            $table->string('indirizzo_completo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contratti_energia', function (Blueprint $table) {
            //
        });
    }
};
