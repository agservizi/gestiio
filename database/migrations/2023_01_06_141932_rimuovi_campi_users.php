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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropForeign('users_listino_telefonia_id_foreign');
            $table->dropColumn('listino_telefonia_id');
            $table->dropColumn('paga_con_paypal');
            $table->dropColumn('iban');
            $table->dropColumn('partita_iva');
            $table->dropColumn('ragione_sociale');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
