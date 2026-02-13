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
        Schema::table('fatture_proforma_righe', function (Blueprint $table) {
            $table->dropColumn('totale_imponibile');

        });
        Schema::table('fatture_proforma_righe', function (Blueprint $table) {
            $table->decimal('totale_imponibile')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fatture_proforma_righe', function (Blueprint $table) {
            //
        });
    }
};
