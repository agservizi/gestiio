<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('tipi_visure', function (Blueprint $table) {
            if (!Schema::hasColumn('tipi_visure', 'openapi_hash_visura')) {
                $table->string('openapi_hash_visura')->nullable()->after('tipo_visura')->index();
            }
        });

        Schema::table('visure', function (Blueprint $table) {
            if (!Schema::hasColumn('visure', 'openapi_hash_visura')) {
                $table->string('openapi_hash_visura')->nullable()->after('uid')->index();
            }
            if (!Schema::hasColumn('visure', 'openapi_request_id')) {
                $table->string('openapi_request_id')->nullable()->after('openapi_hash_visura')->index();
            }
            if (!Schema::hasColumn('visure', 'openapi_stato_richiesta')) {
                $table->string('openapi_stato_richiesta')->nullable()->after('openapi_request_id')->index();
            }
            if (!Schema::hasColumn('visure', 'openapi_response')) {
                $table->longText('openapi_response')->nullable()->after('openapi_stato_richiesta');
            }
            if (!Schema::hasColumn('visure', 'openapi_documento_nome')) {
                $table->string('openapi_documento_nome')->nullable()->after('openapi_response');
            }
            if (!Schema::hasColumn('visure', 'openapi_documento_mime')) {
                $table->string('openapi_documento_mime', 120)->nullable()->after('openapi_documento_nome');
            }
            if (!Schema::hasColumn('visure', 'openapi_documento_dimensione')) {
                $table->unsignedBigInteger('openapi_documento_dimensione')->nullable()->after('openapi_documento_mime');
            }
            if (!Schema::hasColumn('visure', 'openapi_last_sync_at')) {
                $table->timestamp('openapi_last_sync_at')->nullable()->after('openapi_documento_dimensione');
            }
            if (!Schema::hasColumn('visure', 'openapi_documento_scaricato_at')) {
                $table->timestamp('openapi_documento_scaricato_at')->nullable()->after('openapi_last_sync_at');
            }
        });
    }

    public function down()
    {
        Schema::table('visure', function (Blueprint $table) {
            $toDrop = [
                'openapi_hash_visura',
                'openapi_request_id',
                'openapi_stato_richiesta',
                'openapi_response',
                'openapi_documento_nome',
                'openapi_documento_mime',
                'openapi_documento_dimensione',
                'openapi_last_sync_at',
                'openapi_documento_scaricato_at',
            ];

            foreach ($toDrop as $column) {
                if (Schema::hasColumn('visure', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tipi_visure', function (Blueprint $table) {
            if (Schema::hasColumn('tipi_visure', 'openapi_hash_visura')) {
                $table->dropColumn('openapi_hash_visura');
            }
        });
    }
};

