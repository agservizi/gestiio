<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'servizio_contratti_telefonia']);
        Permission::create(['name' => 'servizio_contratti_amex']);
        Permission::create(['name' => 'servizio_contratti_energia']);
        Permission::create(['name' => 'servizio_servizi_finanziari']);
        Permission::create(['name' => 'servizio_compara_semplice']);
        Permission::create(['name' => 'servizio_attivazioni_sim']);
        Permission::create(['name' => 'servizio_visure']);
        Permission::create(['name' => 'servizio_caf_patronato']);
        Permission::create(['name' => 'servizio_spedizioni']);
        Permission::create(['name' => 'servizio_documentazione']);
        Permission::create(['name' => 'servizio_ticket']);

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
