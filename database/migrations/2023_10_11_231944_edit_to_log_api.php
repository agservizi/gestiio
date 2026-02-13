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
        Schema::table('log_chiamate_api', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_type')->nullable();
            $table->index(['service_id', 'service_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_api', function (Blueprint $table) {
            //
        });
    }
};
