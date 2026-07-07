<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifiche', function (Blueprint $table) {
            $table->string('immagine')->nullable()->after('testo');
        });
    }

    public function down(): void
    {
        Schema::table('notifiche', function (Blueprint $table) {
            $table->dropColumn('immagine');
        });
    }
};
