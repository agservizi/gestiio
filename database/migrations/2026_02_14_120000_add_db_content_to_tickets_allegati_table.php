<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tickets_allegati')) {
            return;
        }

        Schema::table('tickets_allegati', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets_allegati', 'file_contenuto_base64')) {
                $table->longText('file_contenuto_base64')->nullable();
            }
            if (!Schema::hasColumn('tickets_allegati', 'mime_type')) {
                $table->string('mime_type', 120)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets_allegati')) {
            return;
        }

        Schema::table('tickets_allegati', function (Blueprint $table) {
            if (Schema::hasColumn('tickets_allegati', 'file_contenuto_base64')) {
                $table->dropColumn('file_contenuto_base64');
            }
            if (Schema::hasColumn('tickets_allegati', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
        });
    }
};
