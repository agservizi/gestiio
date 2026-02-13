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
        Schema::table('tab_gestori', function (Blueprint $table) {
            $table->string('email_notifica_a_gestore')->nullable();
            $table->string('titolo_notifica_a_gestore')->nullable();
            $table->string('testo_notifica_a_gestore')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tab_gestori', function (Blueprint $table) {
            $table->dropColumn('email_notifica_a_gestore');
            $table->dropColumn('titolo_notifica_a_gestore');
            $table->dropColumn('testo_notifica_a_gestore');
        });
    }
};
