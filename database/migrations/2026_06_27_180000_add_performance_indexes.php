<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caf_patronato', function (Blueprint $table) {
            $table->index('data');
            $table->index('created_at');
            $table->index(['agente_id', 'esito_finale', 'created_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('stato');
            $table->index('priorita');
            $table->index('resolution_due_at');
            $table->index('resolved_at');
            $table->index(['agente_id', 'stato']);
            $table->index(['agente_id', 'created_at']);
        });

        Schema::table('visure', function (Blueprint $table) {
            $table->index('data');
            $table->index(['agente_id', 'esito_finale', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('caf_patronato', function (Blueprint $table) {
            $table->dropIndex(['data']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['agente_id', 'esito_finale', 'created_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['stato']);
            $table->dropIndex(['priorita']);
            $table->dropIndex(['resolution_due_at']);
            $table->dropIndex(['resolved_at']);
            $table->dropIndex(['agente_id', 'stato']);
            $table->dropIndex(['agente_id', 'created_at']);
        });

        Schema::table('visure', function (Blueprint $table) {
            $table->dropIndex(['data']);
            $table->dropIndex(['agente_id', 'esito_finale', 'created_at']);
        });
    }
};
