<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordini_ebike', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_id')->constrained('users')->cascadeOnDelete();
            $table->string('stato')->default('in_attesa_pagamento');
            $table->decimal('totale', 10, 2)->default(0);
            $table->text('note')->nullable();

            // Pagamento (bonifico istantaneo)
            $table->string('cro_bonifico')->nullable();
            $table->date('data_bonifico_dichiarata')->nullable();
            $table->string('ricevuta_bonifico')->nullable();
            $table->foreignId('pagamento_confermato_da')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pagamento_confermato_at')->nullable();

            // Spedizione
            $table->string('corriere')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('spedito_at')->nullable();
            $table->date('scadenza_spedizione')->nullable();
            $table->timestamp('consegnato_at')->nullable();
            $table->boolean('sla_alert_inviato')->default(false);

            $table->string('annullato_motivo')->nullable();

            $table->timestamps();

            $table->index('stato');
            $table->index(['agente_id', 'stato']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordini_ebike');
    }
};
