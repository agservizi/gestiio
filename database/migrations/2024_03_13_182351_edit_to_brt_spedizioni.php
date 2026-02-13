<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brt_spedizioni', function (Blueprint $table) {
            $table->boolean('scalato_portafoglio')->default(0);
        });

        \App\Models\SpedizioneBrt::withoutGlobalScope('filtroOperatore')->get()->each(function ($record) {
            $record->scalato_portafoglio = 1;
            $record->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brt_spedizioni', function (Blueprint $table) {
            //
        });
    }
};
