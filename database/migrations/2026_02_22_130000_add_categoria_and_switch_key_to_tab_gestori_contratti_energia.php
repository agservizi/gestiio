<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->string('categoria_pratica', 20)->nullable()->after('model_prodotto')->index();
            $table->string('switch_key', 100)->nullable()->after('categoria_pratica')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->dropColumn(['categoria_pratica', 'switch_key']);
        });
    }
};

