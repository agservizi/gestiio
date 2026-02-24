<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (!Schema::hasColumn('files', 'parent_file_id')) {
                    $table->foreignId('parent_file_id')->nullable()->after('cartella_id')->constrained('files')->nullOnDelete();
                }
                if (!Schema::hasColumn('files', 'versione')) {
                    $table->unsignedInteger('versione')->default(1)->after('tipo_file');
                }
                if (!Schema::hasColumn('files', 'ocr_testo')) {
                    $table->longText('ocr_testo')->nullable()->after('tags_documentali');
                }
                if (!Schema::hasColumn('files', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('ocr_testo');
                    $table->index('expires_at');
                }
                if (!Schema::hasColumn('files', 'last_reminder_at')) {
                    $table->timestamp('last_reminder_at')->nullable()->after('expires_at');
                }
            });
        }

        if (Schema::hasTable('files_cartelle')) {
            Schema::table('files_cartelle', function (Blueprint $table) {
                if (!Schema::hasColumn('files_cartelle', 'visibilita_ruoli')) {
                    $table->json('visibilita_ruoli')->nullable()->after('nome');
                }
            });
        }

        if (!Schema::hasTable('files_versions')) {
            Schema::create('files_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
                $table->unsignedInteger('versione');
                $table->string('filename_originale');
                $table->string('path_filename');
                $table->unsignedBigInteger('dimensione_file');
                $table->string('tipo_file');
                $table->string('categoria_documentale', 80)->nullable();
                $table->json('tags_documentali')->nullable();
                $table->longText('ocr_testo')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['file_id', 'versione']);
            });
        }

        if (!Schema::hasTable('files_share_links')) {
            Schema::create('files_share_links', function (Blueprint $table) {
                $table->id();
                $table->string('token', 120)->unique();
                $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
                $table->foreignId('cartella_id')->nullable()->constrained('files_cartelle')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('password_hash')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedInteger('max_downloads')->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_access_at')->nullable();
                $table->timestamps();
                $table->index(['is_active', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('files_share_links');
        Schema::dropIfExists('files_versions');

        if (Schema::hasTable('files_cartelle')) {
            Schema::table('files_cartelle', function (Blueprint $table) {
                if (Schema::hasColumn('files_cartelle', 'visibilita_ruoli')) {
                    $table->dropColumn('visibilita_ruoli');
                }
            });
        }

        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                if (Schema::hasColumn('files', 'last_reminder_at')) {
                    $table->dropColumn('last_reminder_at');
                }
                if (Schema::hasColumn('files', 'expires_at')) {
                    $table->dropColumn('expires_at');
                }
                if (Schema::hasColumn('files', 'ocr_testo')) {
                    $table->dropColumn('ocr_testo');
                }
                if (Schema::hasColumn('files', 'versione')) {
                    $table->dropColumn('versione');
                }
                if (Schema::hasColumn('files', 'parent_file_id')) {
                    $table->dropConstrainedForeignId('parent_file_id');
                }
            });
        }
    }
};
