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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('agente_id')->nullable()->constrained('users');
            $table->string('oggetto');
            $table->string('tipo');
            $table->string('stato');
            $table->string('uid')->index();

        });
        Schema::create('tickets_messaggi', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('messaggio');
            $table->dateTime('letto')->nullable();
            $table->string('uid')->nullable()->index();

        });
        Schema::create('tickets_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('messaggio_id')->nullable()->constrained('tickets_messaggi')->cascadeOnDelete();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets_allegati');
        Schema::dropIfExists('tickets_messaggi');
        Schema::dropIfExists('tickets');
    }
};
