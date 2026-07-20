<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('files')) {
            return;
        }

        Schema::table('files', function (Blueprint $table) {
            if (! Schema::hasColumn('files', 'seafile_path')) {
                $table->string('seafile_path', 1024)->nullable()->after('path_filename');
            }
            if (! Schema::hasColumn('files', 'seafile_imported_at')) {
                $table->timestamp('seafile_imported_at')->nullable()->after('seafile_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('files')) {
            return;
        }

        Schema::table('files', function (Blueprint $table) {
            if (Schema::hasColumn('files', 'seafile_imported_at')) {
                $table->dropColumn('seafile_imported_at');
            }
            if (Schema::hasColumn('files', 'seafile_path')) {
                $table->dropColumn('seafile_path');
            }
        });
    }
};
