<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_contratti_telefonia']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_contratti_amex']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_contratti_energia']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_servizi_finanziari']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_compara_semplice']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_attivazioni_sim']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_visure']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_caf_patronato']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_spedizioni']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_documentazione']);
        \Spatie\Permission\Models\Permission::create(['name' => 'servizio_ticket']);

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
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
