<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected array $permessiObsoleti = [
        'servizio_contratti_amex',
        'servizio_servizi_finanziari',
        'servizio_compara_semplice',
        'servizio_attivazioni_sim',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ids = Permission::whereIn('name', $this->permessiObsoleti)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        Permission::whereIn('id', $ids)->delete();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Migrazione distruttiva: rollback manuale non supportato.
    }
};
