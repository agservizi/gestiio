<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agenti', function (Blueprint $table) {
            if (! Schema::hasColumn('agenti', 'openapi_email')) {
                $table->text('openapi_email')->nullable()->after('openapi_catasto_token');
            }
            if (! Schema::hasColumn('agenti', 'openapi_api_key')) {
                $table->text('openapi_api_key')->nullable()->after('openapi_email');
            }
        });
    }

    public function down()
    {
        Schema::table('agenti', function (Blueprint $table) {
            foreach (['openapi_api_key', 'openapi_email'] as $column) {
                if (Schema::hasColumn('agenti', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
