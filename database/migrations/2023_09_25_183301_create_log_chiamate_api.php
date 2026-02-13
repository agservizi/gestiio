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
        Schema::create('log_chiamate_api', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('servizio');
            $table->string('url');
            $table->string('method');
            $table->text('request');
            $table->text('response')->nullable();
            $table->string('status')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_chiamate_api');
    }
};
