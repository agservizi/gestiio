<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratti', function (Blueprint $table) {
            $table->string('codice_contratto_interno', 32)->nullable()->after('codice_contratto')->index();
        });

        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->string('codice_contratto_interno', 32)->nullable()->after('codice_contratto')->index();
        });

        DB::table('contratti')
            ->whereNull('codice_contratto_interno')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('contratti')
                        ->where('id', $row->id)
                        ->update([
                            'codice_contratto_interno' => 'TEL'.str_pad((string) $row->id, 11, '0', STR_PAD_LEFT),
                        ]);
                }
            });

        DB::table('contratti_energia')
            ->whereNull('codice_contratto_interno')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('contratti_energia')
                        ->where('id', $row->id)
                        ->update([
                            'codice_contratto_interno' => 'OP'.str_pad((string) $row->id, 11, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('contratti', function (Blueprint $table) {
            $table->dropColumn('codice_contratto_interno');
        });

        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->dropColumn('codice_contratto_interno');
        });
    }
};
