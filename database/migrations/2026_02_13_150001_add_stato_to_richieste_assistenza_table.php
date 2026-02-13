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
        if (!Schema::hasColumn('richieste_assistenza', 'stato')) {
            Schema::table('richieste_assistenza', function (Blueprint $table) {
                $table->string('stato')->nullable()->after('pin');
                $table->index('stato', 'idx_richieste_assistenza_stato');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('richieste_assistenza', 'stato')) {
            Schema::table('richieste_assistenza', function (Blueprint $table) {
                $table->dropIndex('idx_richieste_assistenza_stato');
                $table->dropColumn('stato');
            });
        }
    }
};
