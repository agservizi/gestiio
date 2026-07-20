<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('send_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('send_requests', 'prezzo_cliente')) {
                $table->decimal('prezzo_cliente', 10, 2)->default(5)->after('priority');
            }
            if (! Schema::hasColumn('send_requests', 'prezzo_agente')) {
                $table->decimal('prezzo_agente', 10, 2)->default(4)->after('prezzo_cliente');
            }
            if (! Schema::hasColumn('send_requests', 'movimento_portafoglio_id')) {
                $table->unsignedBigInteger('movimento_portafoglio_id')->nullable()->after('prezzo_agente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('send_requests', function (Blueprint $table) {
            foreach (['movimento_portafoglio_id', 'prezzo_agente', 'prezzo_cliente'] as $col) {
                if (Schema::hasColumn('send_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
