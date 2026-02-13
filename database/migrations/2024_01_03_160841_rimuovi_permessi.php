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
        \Spatie\Permission\Models\Permission::where('name','vedi_spedizioni')->delete();
        \Spatie\Permission\Models\Permission::where('name','vedi_caf_patronato')->delete();
        \Spatie\Permission\Models\Permission::where('name','vedi_contratti')->delete();
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
