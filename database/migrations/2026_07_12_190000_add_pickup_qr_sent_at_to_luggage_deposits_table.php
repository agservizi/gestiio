<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('luggage_deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('luggage_deposits', 'pickup_qr_sent_at')) {
                $table->dateTime('pickup_qr_sent_at')->nullable()->after('checked_out_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('luggage_deposits', function (Blueprint $table) {
            if (Schema::hasColumn('luggage_deposits', 'pickup_qr_sent_at')) {
                $table->dropColumn('pickup_qr_sent_at');
            }
        });
    }
};
