<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('richieste_assistenza', function (Blueprint $table) {
            if (!$this->indexExists('richieste_assistenza', 'idx_cliente_id')) {
                $table->index('cliente_id', 'idx_cliente_id');
            }

            if (!$this->indexExists('richieste_assistenza', 'idx_prodotto_assistenza_id')) {
                $table->index('prodotto_assistenza_id', 'idx_prodotto_assistenza_id');
            }

            // Nota: Per full-text, se MySQL, aggiungi $table->fullText(['campo1', 'campo2']);
            // Ma poiché la ricerca è su cliente, meglio indici su clienti_assistenza
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('richieste_assistenza', function (Blueprint $table) {
            if ($this->indexExists('richieste_assistenza', 'idx_cliente_id')) {
                $table->dropIndex('idx_cliente_id');
            }

            if ($this->indexExists('richieste_assistenza', 'idx_prodotto_assistenza_id')) {
                $table->dropIndex('idx_prodotto_assistenza_id');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])) > 0;
    }
};
