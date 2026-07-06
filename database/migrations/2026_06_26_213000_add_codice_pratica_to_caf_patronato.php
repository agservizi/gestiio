<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('caf_patronato', 'codice_pratica')) {
            Schema::table('caf_patronato', function (Blueprint $table) {
                $table->string('codice_pratica', 32)->nullable()->after('id')->unique();
            });
        }

        DB::table('caf_patronato')
            ->where(function ($query) {
                $query->whereNull('codice_pratica')->orWhere('codice_pratica', '');
            })
            ->select('id')
            ->chunkById(200, function ($records) {
                foreach ($records as $record) {
                    do {
                        $codice = 'CAF'.Str::upper((string) Str::ulid());
                    } while (DB::table('caf_patronato')->where('codice_pratica', $codice)->exists());

                    DB::table('caf_patronato')
                        ->where('id', $record->id)
                        ->update(['codice_pratica' => $codice]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('caf_patronato', 'codice_pratica')) {
            Schema::table('caf_patronato', function (Blueprint $table) {
                $table->dropUnique(['codice_pratica']);
                $table->dropColumn('codice_pratica');
            });
        }
    }
};
