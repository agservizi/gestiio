<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contratti_energia')) {
            return;
        }

        Schema::table('contratti_energia', function (Blueprint $table) {
            if (! Schema::hasColumn('contratti_energia', 'link_attivazione_gestore')) {
                $table->text('link_attivazione_gestore')->nullable()->after('codice_contratto');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contratti_energia')) {
            return;
        }

        Schema::table('contratti_energia', function (Blueprint $table) {
            if (Schema::hasColumn('contratti_energia', 'link_attivazione_gestore')) {
                $table->dropColumn('link_attivazione_gestore');
            }
        });
    }
};
