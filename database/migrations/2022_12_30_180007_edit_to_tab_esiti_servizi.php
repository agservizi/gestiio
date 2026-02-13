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
        Schema::table('tab_esiti_servizi', function (Blueprint $table) {
            $table->dropColumn('notifica_whatsapp');
        });
        Schema::table('tab_esiti_caf_patronato', function (Blueprint $table) {
            $table->dropColumn('notifica_whatsapp');
        });
        Schema::table('tab_esiti', function (Blueprint $table) {
            $table->dropColumn('notifica_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tab_esiti_servizi', function (Blueprint $table) {
            //
        });
    }
};
