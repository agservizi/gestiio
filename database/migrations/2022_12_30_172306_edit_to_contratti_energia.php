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
        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->decimal('provvigione_agente')->default(0)->after('pagato');
            $table->decimal('provvigione_agenzia')->default(0)->after('provvigione_agente');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contratti_energia', function (Blueprint $table) {
            //
        });
    }
};
