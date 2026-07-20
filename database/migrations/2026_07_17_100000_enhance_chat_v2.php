<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nuovi campi thread: nome gruppo, flag gruppo, archiviazione
        if (Schema::hasTable('chat_threads')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                if (! Schema::hasColumn('chat_threads', 'name')) {
                    $table->string('name')->nullable()->after('created_by');
                }
                if (! Schema::hasColumn('chat_threads', 'is_group')) {
                    $table->boolean('is_group')->default(false)->after('name');
                }
                if (! Schema::hasColumn('chat_threads', 'archived_at')) {
                    $table->dateTime('archived_at')->nullable()->after('is_group');
                }
            });
        }

        // Indice fulltext per la ricerca messaggi (solo MySQL/MariaDB).
        // SQLite e altri driver non lo supportano: si ignora silenziosamente.
        if (Schema::hasTable('chat_messages') && $this->supportaFulltext()) {
            try {
                DB::statement('ALTER TABLE chat_messages ADD FULLTEXT chat_messages_messaggio_fulltext (messaggio)');
            } catch (\Throwable $e) {
                // Indice già presente o driver senza supporto: nessuna azione.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages') && $this->supportaFulltext()) {
            try {
                DB::statement('ALTER TABLE chat_messages DROP INDEX chat_messages_messaggio_fulltext');
            } catch (\Throwable $e) {
                // Indice inesistente: nessuna azione.
            }
        }

        if (Schema::hasTable('chat_threads')) {
            Schema::table('chat_threads', function (Blueprint $table) {
                foreach (['archived_at', 'is_group', 'name'] as $colonna) {
                    if (Schema::hasColumn('chat_threads', $colonna)) {
                        $table->dropColumn($colonna);
                    }
                }
            });
        }
    }

    protected function supportaFulltext(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
