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
        Schema::create('clienti_assistenza', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('cognome')->index();
            $table->string('codice_fiscale')->index();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
        });

        Schema::create('prodotti_assistenza', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex');
            $table->boolean('attivo')->index();
        });
        Schema::create('richieste_assistenza', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('cliente_id')->constrained('clienti_assistenza');
            $table->foreignId('prodotto_assistenza_id')->constrained('prodotti_assistenza');
            $table->string('nome_utente')->nullable();
            $table->string('password')->nullable();
            $table->string('pin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('richieste_assistenza');
        Schema::dropIfExists('prodotti_assistenza');
        Schema::dropIfExists('clienti_assistenza');
    }
};
