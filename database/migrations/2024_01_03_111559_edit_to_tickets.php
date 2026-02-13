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
        Schema::table('tickets', function (Blueprint $table) {


            $table->string('da_tipo_utente')->nullable();
            $table->string('a_tipo_utente')->nullable();

            $table->unsignedBigInteger('servizio_id')->nullable();
            $table->string('servizio_type')->nullable();
            $table->index(['servizio_id', 'servizio_type']);

        });


        Schema::create('tickets_letture', function (Blueprint $table) {

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->boolean('messaggio_letto');
            $table->dateTime('data_lettura')->nullable();

            $table->index(['user_id', 'messaggio_letto']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {

        });
    }
};
