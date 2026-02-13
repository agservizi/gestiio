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
        Schema::table('agenti_fasce_tipi_contratto', function (Blueprint $table) {
            $table->foreign('tipo_contratto_id')->references('id')->on('tipi_contratto')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agenti_fasce_tipi_contratto', function (Blueprint $table) {
            //
        });
    }
};
