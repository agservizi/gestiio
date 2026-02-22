<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->mediumText('logo_contenuto_base64')->nullable()->after('logo');
            $table->string('logo_mime_type', 100)->nullable()->after('logo_contenuto_base64');
        });
    }

    public function down(): void
    {
        Schema::table('tab_gestori_contratti_energia', function (Blueprint $table) {
            $table->dropColumn(['logo_contenuto_base64', 'logo_mime_type']);
        });
    }
};

