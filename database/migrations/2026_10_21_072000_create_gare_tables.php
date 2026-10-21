<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gare')) {
            Schema::create('gare', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('canale', 20); // telefonia|energia
                $table->unsignedSmallInteger('anno');
                $table->unsignedTinyInteger('mese');
                $table->boolean('attivo')->default(true);
                $table->string('ambito', 20)->default('tutti'); // tutti|listino|agenti
                $table->unsignedBigInteger('listino_id')->nullable()->index();
                $table->unsignedBigInteger('gestore_id')->nullable()->index();
                $table->unsignedBigInteger('telefonia_offerta_id')->nullable()->index();
                $table->unsignedBigInteger('tipo_contratto_id')->nullable()->index();
                $table->string('tipo_attivazione', 80)->nullable();
                $table->boolean('is_business')->nullable();
                $table->timestamps();
                $table->index(['anno', 'mese', 'attivo']);
            });
        }

        if (! Schema::hasTable('gara_agente')) {
            Schema::create('gara_agente', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gara_id')->constrained('gare')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->index();
                $table->unique(['gara_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('gara_soglie')) {
            Schema::create('gara_soglie', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gara_id')->constrained('gare')->cascadeOnDelete();
                $table->unsignedInteger('da_valore');
                $table->unsignedInteger('a_valore')->nullable();
                $table->decimal('premio', 10, 2)->default(0);
                $table->timestamps();
                $table->index(['gara_id', 'da_valore']);
            });
        }

        if (! Schema::hasTable('gara_risultati')) {
            Schema::create('gara_risultati', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gara_id')->constrained('gare')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedSmallInteger('anno');
                $table->unsignedTinyInteger('mese');
                $table->unsignedInteger('valore_raggiunto')->default(0);
                $table->unsignedBigInteger('gara_soglia_id')->nullable()->index();
                $table->decimal('premio', 10, 2)->default(0);
                $table->unsignedBigInteger('fattura_proforma_id')->nullable()->index();
                $table->timestamp('liquidato_at')->nullable();
                $table->timestamps();
                $table->unique(['gara_id', 'user_id']);
                $table->index(['anno', 'mese', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gara_risultati');
        Schema::dropIfExists('gara_soglie');
        Schema::dropIfExists('gara_agente');
        Schema::dropIfExists('gare');
    }
};
