<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'ebike-b2b', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->first();
        if (! $adminRole) {
            return;
        }

        $permission = DB::table('permissions')
            ->where('name', 'ebike-b2b')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permission->id,
                'role_id' => $adminRole->id,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->where('name', 'ebike-b2b')->delete();
    }
};
