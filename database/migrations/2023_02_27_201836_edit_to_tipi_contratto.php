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
        Schema::table('tipi_contratto', function (Blueprint $table) {
            $table->unsignedTinyInteger('durata_contratto')->nullable();
            $table->dropColumn('colore_hex');
        });

        Schema::table('contratti', function (Blueprint $table) {
            $table->date('data_reminder')->nullable()->index();
            $table->date('reminder_inviato')->nullable()->index();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipi_contratto', function (Blueprint $table) {
            $table->dropColumn('durata_contratto');
        });
    }
};
