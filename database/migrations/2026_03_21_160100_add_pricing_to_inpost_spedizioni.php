<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('inpost_spedizioni', function (Blueprint $table) {
            $table->decimal('prezzo_spedizione', 10, 2)->nullable()->after('volume_totale');
            $table->string('tariffa')->nullable()->after('prezzo_spedizione');
            $table->boolean('scalato_portafoglio')->default(0)->after('tariffa');
        });
    }

    public function down()
    {
        Schema::table('inpost_spedizioni', function (Blueprint $table) {
            $table->dropColumn(['prezzo_spedizione', 'tariffa', 'scalato_portafoglio']);
        });
    }
};
