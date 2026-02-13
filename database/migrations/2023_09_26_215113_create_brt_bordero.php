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
        Schema::create('brt_bordero', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::table('brt_spedizioni', function (Blueprint $table) {
            $table->foreignId('bordero_id')->nullable()->constrained('brt_bordero');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brt_bordero');
    }
};
