<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('marketing_outreach_messages');
        Schema::dropIfExists('marketing_leads');
        Schema::dropIfExists('marketing_outreach_campaigns');
        Schema::dropIfExists('marketing_suppressions');

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('name', 'servizio_outreach_marketing')
            ->where('guard_name', 'web')
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
        // Irreversibile: ricreare via migration storiche se necessario.
    }
};
