<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings_audit_logs')) {
            Schema::create('settings_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('setting_name');
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->string('action', 40)->default('update');
                $table->ipAddress('ip')->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();

                $table->index(['setting_name', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ([
            'settings.view',
            'settings.update',
            'settings.export',
            'settings.import',
            'settings.rollback',
            'settings.test',
            'controlli-contratti.view',
            'controlli-contratti.update',
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_audit_logs');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->whereIn('name', [
                    'settings.view',
                    'settings.update',
                    'settings.export',
                    'settings.import',
                    'settings.rollback',
                    'settings.test',
                    'controlli-contratti.view',
                    'controlli-contratti.update',
                ])
                ->delete();
        }
    }
};
