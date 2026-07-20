<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fatture_proforma', function (Blueprint $table) {
            $table->string('status', 20)->default('bozza')->after('totale_con_iva')->index();
        });

        // Storiche già emesse: non trattarle come bozze (delete/rigenera sbloccati)
        DB::table('fatture_proforma')->update(['status' => 'emessa']);
    }

    public function down(): void
    {
        Schema::table('fatture_proforma', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
