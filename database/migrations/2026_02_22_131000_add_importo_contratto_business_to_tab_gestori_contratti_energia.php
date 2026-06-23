<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->decimal('importo_contratto_business')->nullable()->after('importo_contratto');
        });
    }

    public function down(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->dropColumn('importo_contratto_business');
        });
    }
};
