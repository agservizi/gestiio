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
            $table->string('email_notifica_sollecito')->nullable();
        });
        foreach (\App\Models\Gestore::get() as $g) {
            $g->email_notifica_sollecito = $g->email_notifica_a_gestore;
            $g->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tab_gestori', function (Blueprint $table) {
            $table->dropColumn('email_notifica_sollecito');

        });
    }
};
