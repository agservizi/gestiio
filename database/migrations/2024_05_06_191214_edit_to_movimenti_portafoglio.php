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
        Schema::table('movimenti_portafoglio', function (Blueprint $table) {
            $table->decimal('importo_prima')->nullable();
            $table->decimal('importo_dopo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimenti_portafoglio', function (Blueprint $table) {
            //
        });
    }
};
