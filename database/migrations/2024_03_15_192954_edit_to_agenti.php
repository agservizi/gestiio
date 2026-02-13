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
        Schema::table('agenti', function (Blueprint $table) {
            $table->decimal('portafoglio_servizi')->default(0);
            $table->decimal('portafoglio_spedizioni')->default(0);
        });

        \App\Models\User::with('agente')->get()->each(function ($q) {
            if ($q->agente) {
                $q->agente->portafoglio_spedizioni = $q->portafoglio;
                $q->agente->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('agenti', function (Blueprint $table) {
            //
        });
    }
};
