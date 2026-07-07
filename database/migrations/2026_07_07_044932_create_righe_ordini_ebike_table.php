<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('righe_ordini_ebike', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordine_id')->constrained('ordini_ebike')->cascadeOnDelete();
            $table->foreignId('prodotto_id')->constrained('prodotti_ebike');
            $table->string('nome_prodotto');
            $table->unsignedInteger('quantita');
            $table->decimal('prezzo_unitario', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('righe_ordini_ebike');
    }
};
