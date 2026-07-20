<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('send_request_documents')) {
            return;
        }

        Schema::table('send_request_documents', function (Blueprint $table) {
            try {
                $table->dropForeign(['send_request_id']);
            } catch (\Throwable $e) {
                // FK già rimossa
            }
        });

        // MySQL: nullable senza doctrine/dbal
        DB::statement('ALTER TABLE send_request_documents MODIFY send_request_id BIGINT UNSIGNED NULL');

        Schema::table('send_request_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('send_request_documents', 'upload_uid')) {
                $table->string('upload_uid', 40)->nullable()->index()->after('send_request_id');
            }
            $table->foreign('send_request_id')
                ->references('id')
                ->on('send_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // rollback manuale non supportato
    }
};
