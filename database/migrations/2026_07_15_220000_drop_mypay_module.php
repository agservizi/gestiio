<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        'mypay.view',
        'mypay.create',
        'mypay.update',
        'mypay.generate',
        'mypay.submit_otp',
        'mypay.retry',
        'mypay.cancel',
        'mypay.download',
        'mypay.view_logs',
        'mypay.manage_configuration',
    ];

    private array $tables = [
        'mypay_callbacks',
        'mypay_job_logs',
        'mypay_practices',
        'mypay_automation_configs',
        'mypay_payment_types',
        'mypay_entities',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    public function down(): void
    {
        // Irreversible removal of MyPay module.
    }
};
