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
        Schema::table('mandati', function (Blueprint $table) {
            $table->dropForeign('mandati_gestore_id_foreign');
        });
        Schema::table('mandati', function (Blueprint $table) {
            $table->foreign('gestore_id')->references('id')->on('tab_gestori')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mandati', function (Blueprint $table) {
            //
        });
    }
};
