<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('caf_patronato', 'data');
        $this->addIndexIfMissing('caf_patronato', 'created_at');
        $this->addIndexIfMissing('caf_patronato', ['agente_id', 'esito_finale', 'created_at']);

        $this->addIndexIfMissing('tickets', 'stato');
        $this->addIndexIfMissing('tickets', 'priorita');
        $this->addIndexIfMissing('tickets', 'resolution_due_at');
        $this->addIndexIfMissing('tickets', 'resolved_at');
        $this->addIndexIfMissing('tickets', ['agente_id', 'stato']);
        $this->addIndexIfMissing('tickets', ['agente_id', 'created_at']);

        $this->addIndexIfMissing('visure', 'data');
        $this->addIndexIfMissing('visure', ['agente_id', 'esito_finale', 'created_at']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('caf_patronato', ['data']);
        $this->dropIndexIfExists('caf_patronato', ['created_at']);
        $this->dropIndexIfExists('caf_patronato', ['agente_id', 'esito_finale', 'created_at']);

        $this->dropIndexIfExists('tickets', ['stato']);
        $this->dropIndexIfExists('tickets', ['priorita']);
        $this->dropIndexIfExists('tickets', ['resolution_due_at']);
        $this->dropIndexIfExists('tickets', ['resolved_at']);
        $this->dropIndexIfExists('tickets', ['agente_id', 'stato']);
        $this->dropIndexIfExists('tickets', ['agente_id', 'created_at']);

        $this->dropIndexIfExists('visure', ['data']);
        $this->dropIndexIfExists('visure', ['agente_id', 'esito_finale', 'created_at']);
    }

    /**
     * @param  string|array  $columns
     */
    private function addIndexIfMissing(string $table, $columns): void
    {
        $indexName = $this->indexName($table, $columns);
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    /**
     * @param  array  $columns
     */
    private function dropIndexIfExists(string $table, array $columns): void
    {
        $indexName = $this->indexName($table, $columns);
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    /**
     * @param  string|array  $columns
     */
    private function indexName(string $table, $columns): string
    {
        return $table.'_'.implode('_', (array) $columns).'_index';
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return (int) ($result[0]->cnt ?? 0) > 0;
    }
};
