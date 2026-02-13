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
        Schema::table('tipi_caf_patronato', function (Blueprint $table) {
            $table->text('html')->nullable();
            $table->dropColumn('model');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipi_caf_patronato', function (Blueprint $table) {
            //
        });
    }
};
