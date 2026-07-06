<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifiche') && ! Schema::hasColumn('notifiche', 'user_id')) {
            Schema::table('notifiche', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('destinatario')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('richieste_spostamento_portafoglio')) {
            Schema::create('richieste_spostamento_portafoglio', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->foreignId('agente_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('portafoglio_da');
                $table->string('portafoglio_a');
                $table->decimal('importo', 10, 2);
                $table->string('descrizione');
                $table->string('stato')->default('in_attesa')->index();
                $table->timestamp('applicata_il')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('richieste_spostamento_portafoglio');

        if (Schema::hasTable('notifiche') && Schema::hasColumn('notifiche', 'user_id')) {
            Schema::table('notifiche', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
