<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('agenti', function (Blueprint $table) {
            if (!Schema::hasColumn('agenti', 'portafoglio_visure')) {
                $table->decimal('portafoglio_visure')->default(0)->after('portafoglio_spedizioni');
            }
            if (!Schema::hasColumn('agenti', 'openapi_visure_token')) {
                $table->text('openapi_visure_token')->nullable()->after('portafoglio_visure');
            }
            if (!Schema::hasColumn('agenti', 'openapi_catasto_token')) {
                $table->text('openapi_catasto_token')->nullable()->after('openapi_visure_token');
            }
        });
    }

    public function down()
    {
        Schema::table('agenti', function (Blueprint $table) {
            $columns = ['portafoglio_visure', 'openapi_visure_token', 'openapi_catasto_token'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('agenti', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

