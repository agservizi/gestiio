<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'servizio_send',
        'send.access',
        'send.requests.view',
        'send.requests.view-own',
        'send.requests.view-all',
        'send.requests.create',
        'send.requests.update',
        'send.requests.delete',
        'send.requests.submit',
        'send.requests.assign',
        'send.requests.take-charge',
        'send.requests.request-integration',
        'send.requests.process',
        'send.requests.complete',
        'send.requests.reject',
        'send.requests.cancel',
        'send.requests.reopen',
        'send.documents.view',
        'send.documents.upload',
        'send.documents.download',
        'send.documents.delete',
        'send.notes.view-internal',
        'send.notes.create-internal',
        'send.audit.view',
        'send.reports.view',
        'send.settings.manage',
    ];

    /** Permessi tipici per supervisore SEND */
    private array $supervisorExtra = [
        'send.requests.take-charge',
        'send.requests.request-integration',
        'send.requests.process',
        'send.requests.complete',
        'send.requests.reject',
        'send.notes.view-internal',
        'send.notes.create-internal',
        'send.documents.upload',
        'send.documents.view',
        'send.documents.download',
        'send.audit.view',
    ];

    /** Permessi tipici per agente/operatore sportello */
    private array $operatorExtra = [
        'send.requests.view',
        'send.requests.view-own',
        'send.requests.create',
        'send.requests.update',
        'send.requests.submit',
        'send.requests.cancel',
        'send.documents.view',
        'send.documents.upload',
        'send.documents.download',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $this->attachToRole('admin', $this->permissions);
        $this->attachToRole('supervisore', array_merge(
            ['servizio_send', 'send.access', 'send.requests.view', 'send.requests.view-all'],
            $this->supervisorExtra
        ));
        $this->attachToRole('agente', array_merge(
            ['servizio_send', 'send.access'],
            $this->operatorExtra
        ));

        if (Schema::hasTable('send_settings')) {
            $defaults = [
                'module_enabled' => '1',
                'assignment_method' => 'least_open',
                'allow_manual_assignment' => '1',
                'default_priority' => 'normale',
                'max_upload_kb' => (string) config('send.max_upload_kb', 20480),
                'privacy_version' => (string) config('send.privacy_version', '2026-07-01'),
            ];
            foreach ($defaults as $key => $value) {
                DB::table('send_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
    }

    private function attachToRole(string $roleName, array $permissionNames): void
    {
        $role = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->first();
        if (! $role) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        foreach ($ids as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }
    }
};
