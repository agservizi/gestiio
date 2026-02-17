<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chat_thread_users') && !Schema::hasColumn('chat_thread_users', 'muted_until')) {
            Schema::table('chat_thread_users', function (Blueprint $table) {
                $table->dateTime('muted_until')->nullable()->after('last_read_at');
                $table->index(['user_id', 'muted_until']);
            });
        }

        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'delivered_at')) {
                    $table->dateTime('delivered_at')->nullable()->after('reply_to_id');
                }
                if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                    $table->dateTime('edited_at')->nullable()->after('delivered_at');
                }
                if (!Schema::hasColumn('chat_messages', 'deleted_at')) {
                    $table->dateTime('deleted_at')->nullable()->after('edited_at');
                }
                if (!Schema::hasColumn('chat_messages', 'priority')) {
                    $table->unsignedTinyInteger('priority')->default(0)->after('deleted_at');
                }
                if (!Schema::hasColumn('chat_messages', 'forwarded_from_id')) {
                    $table->foreignId('forwarded_from_id')->nullable()->after('priority')->constrained('chat_messages')->nullOnDelete();
                }
            });

            Schema::table('chat_messages', function (Blueprint $table) {
                $table->index(['thread_id', 'id']);
                $table->index(['thread_id', 'priority']);
                $table->index(['thread_id', 'deleted_at']);
            });
        }

        if (Schema::hasTable('chat_message_attachments')) {
            Schema::table('chat_message_attachments', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_message_attachments', 'scan_status')) {
                    $table->string('scan_status', 20)->default('pending')->after('dimensione_file');
                }
                if (!Schema::hasColumn('chat_message_attachments', 'scan_note')) {
                    $table->string('scan_note')->nullable()->after('scan_status');
                }
                if (!Schema::hasColumn('chat_message_attachments', 'is_blocked')) {
                    $table->boolean('is_blocked')->default(false)->after('scan_note');
                }
            });
        }

        Schema::create('chat_message_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('chat_message_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['message_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('chat_message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['message_id', 'mentioned_user_id']);
            $table->index(['mentioned_user_id', 'created_at']);
        });

        Schema::create('chat_message_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('azione', 40);
            $table->text('old_text')->nullable();
            $table->text('new_text')->nullable();
            $table->timestamps();
            $table->index(['message_id', 'created_at']);
        });

        Schema::create('chat_quick_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titolo', 120);
            $table->text('contenuto');
            $table->boolean('is_global')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_global']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_quick_templates');
        Schema::dropIfExists('chat_message_audits');
        Schema::dropIfExists('chat_message_mentions');
        Schema::dropIfExists('chat_message_favorites');
        Schema::dropIfExists('chat_message_pins');

        if (Schema::hasTable('chat_message_attachments')) {
            Schema::table('chat_message_attachments', function (Blueprint $table) {
                if (Schema::hasColumn('chat_message_attachments', 'scan_status')) {
                    $table->dropColumn('scan_status');
                }
                if (Schema::hasColumn('chat_message_attachments', 'scan_note')) {
                    $table->dropColumn('scan_note');
                }
                if (Schema::hasColumn('chat_message_attachments', 'is_blocked')) {
                    $table->dropColumn('is_blocked');
                }
            });
        }

        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (Schema::hasColumn('chat_messages', 'forwarded_from_id')) {
                    $table->dropConstrainedForeignId('forwarded_from_id');
                }
                if (Schema::hasColumn('chat_messages', 'priority')) {
                    $table->dropColumn('priority');
                }
                if (Schema::hasColumn('chat_messages', 'deleted_at')) {
                    $table->dropColumn('deleted_at');
                }
                if (Schema::hasColumn('chat_messages', 'edited_at')) {
                    $table->dropColumn('edited_at');
                }
                if (Schema::hasColumn('chat_messages', 'delivered_at')) {
                    $table->dropColumn('delivered_at');
                }
            });
        }

        if (Schema::hasTable('chat_thread_users') && Schema::hasColumn('chat_thread_users', 'muted_until')) {
            Schema::table('chat_thread_users', function (Blueprint $table) {
                $table->dropColumn('muted_until');
            });
        }
    }
};
