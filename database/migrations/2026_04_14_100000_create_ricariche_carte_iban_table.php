<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ricariche_carte_iban', function (Blueprint $table) {
            $table->id();
            $table->string('cognome');
            $table->string('nome');
            $table->string('codice_fiscale')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('iban', 34);
            $table->string('intestatario_iban')->nullable();
            $table->string('carta')->nullable()->comment('Tipo / numero carta prepagata');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ricariche_carte_iban');
    }
};
