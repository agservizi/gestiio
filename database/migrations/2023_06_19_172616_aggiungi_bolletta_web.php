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
        Schema::table('prodotto_energia_enelconsumer', function (Blueprint $table) {
            $table->boolean('bolletta_web');
        });
        Schema::table('prodotto_energia_enelbusiness', function (Blueprint $table) {
            $table->boolean('bolletta_web');
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
