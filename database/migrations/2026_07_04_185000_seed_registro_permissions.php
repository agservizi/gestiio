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

        $permissions = [
            'registro.login.view',
            'registro.email.view',
            'registro.backup.view',
            'registro.backup.run',
            'registro.backup.download',
            'registro.licenze.view',
            'registro.info-sito.view',
            'registro.errori.view',
            'registro.upload.view',
            'registro.modifiche.view',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->first();
        if (! $adminRole) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $adminRole->id,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', [
                'registro.login.view',
                'registro.email.view',
                'registro.backup.view',
                'registro.backup.run',
                'registro.backup.download',
                'registro.licenze.view',
                'registro.info-sito.view',
                'registro.errori.view',
                'registro.upload.view',
                'registro.modifiche.view',
            ])
            ->delete();
    }
};
