<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            $perm = DB::table('permissions')
                ->where('name', 'send.requests.view-branch')
                ->where('guard_name', 'web')
                ->first();

            if ($perm) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                }
                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                }
                DB::table('permissions')->where('id', $perm->id)->delete();
            }
        }

        if (! Schema::hasTable('send_settings')) {
            return;
        }

        $now = now();
        $defaults = [
            'prezzo_cliente' => (string) config('send.prezzo_cliente', 5),
            'prezzo_agente' => (string) config('send.prezzo_agente', 4),
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table('send_settings')->where('key', $key)->exists();
            if (! $exists) {
                DB::table('send_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'send.requests.view-branch', 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }
};
