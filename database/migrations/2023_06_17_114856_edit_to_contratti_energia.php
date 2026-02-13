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
        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->dropColumn('cognome');
            $table->dropColumn('nome');
            $table->dropColumn('indirizzo');
            $table->dropColumn('citta');
            $table->dropColumn('cap');
            $table->dropColumn('scala');
            $table->dropColumn('interno');
            $table->dropColumn('tipo_documento');
            $table->dropColumn('numero_documento');
            $table->dropColumn('rilasciato_da');
            $table->dropColumn('data_rilascio');
            $table->dropColumn('data_scadenza');
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
