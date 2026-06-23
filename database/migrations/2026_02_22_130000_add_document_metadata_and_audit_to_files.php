<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (! Schema::hasColumn('files', 'categoria_documentale')) {
                    $table->string('categoria_documentale', 80)->nullable()->after('tipo_file');
                    $table->index('categoria_documentale');
                }
                if (! Schema::hasColumn('files', 'tags_documentali')) {
                    $table->json('tags_documentali')->nullable()->after('categoria_documentale');
                }
            });
        }

        if (! Schema::hasTable('files_audit_logs')) {
            Schema::create('files_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->foreignId('cartella_id')->nullable()->constrained('files_cartelle')->nullOnDelete();
                $table->string('azione', 40);
                $table->string('filename_originale');
                $table->string('path_filename')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['azione', 'created_at']);
                $table->index(['file_id', 'created_at']);
                $table->index(['cartella_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('files_audit_logs');

        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (Schema::hasColumn('files', 'tags_documentali')) {
                    $table->dropColumn('tags_documentali');
                }
                if (Schema::hasColumn('files', 'categoria_documentale')) {
                    $table->dropColumn('categoria_documentale');
                }
            });
        }
    }
};
