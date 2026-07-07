<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodotti_ebike', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('sku')->unique();
            $table->text('descrizione')->nullable();
            $table->decimal('prezzo', 10, 2);
            $table->unsignedInteger('giacenza')->default(0);
            $table->string('immagine')->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodotti_ebike');
    }
};
