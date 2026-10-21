<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telefonia_offerte')) {
            Schema::create('telefonia_offerte', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('gestore_id')->constrained('tab_gestori')->cascadeOnDelete();
                $table->foreignId('tipo_contratto_id')->nullable()->constrained('tipi_contratto')->nullOnDelete();
                $table->string('nome')->index();
                $table->string('commodity', 32)->default('fibra')->index(); // fibra|fwa|altro
                $table->decimal('prezzo_mese', 12, 2)->nullable();
                $table->date('valido_dal')->nullable();
                $table->date('valido_al')->nullable();
                $table->boolean('attivo')->default(true)->index();
                $table->text('note')->nullable();
                $table->string('source', 40)->default('manual')->index();
                $table->string('external_key', 80)->nullable()->unique();
                $table->string('source_url', 1000)->nullable();
                $table->timestamp('synced_at')->nullable();
            });
        }

        if (Schema::hasTable('contratti') && ! Schema::hasColumn('contratti', 'telefonia_offerta_id')) {
            Schema::table('contratti', function (Blueprint $table) {
                $table->unsignedBigInteger('telefonia_offerta_id')->nullable()->after('tipo_contratto_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contratti') && Schema::hasColumn('contratti', 'telefonia_offerta_id')) {
            Schema::table('contratti', function (Blueprint $table) {
                $table->dropColumn('telefonia_offerta_id');
            });
        }
        Schema::dropIfExists('telefonia_offerte');
    }
};
