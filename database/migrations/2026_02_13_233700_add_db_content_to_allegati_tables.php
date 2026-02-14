<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'contratti_allegati',
            'contratti_energia_allegati',
            'caf_patronato_allegati',
            'allegati_tutti_servizi',
            'visure_allegati',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'file_contenuto_base64')) {
                    $table->longText('file_contenuto_base64')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'mime_type')) {
                    $table->string('mime_type', 120)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'contratti_allegati',
            'contratti_energia_allegati',
            'caf_patronato_allegati',
            'allegati_tutti_servizi',
            'visure_allegati',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'file_contenuto_base64')) {
                    $table->dropColumn('file_contenuto_base64');
                }
                if (Schema::hasColumn($tableName, 'mime_type')) {
                    $table->dropColumn('mime_type');
                }
            });
        }
    }
};
