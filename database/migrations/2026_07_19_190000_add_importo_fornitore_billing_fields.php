<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipi_caf_patronato', function (Blueprint $table) {
            if (! Schema::hasColumn('tipi_caf_patronato', 'importo_fornitore')) {
                $table->decimal('importo_fornitore', 10, 2)->default(0)->after('prezzo_agente');
            }
        });

        Schema::table('caf_patronato', function (Blueprint $table) {
            if (! Schema::hasColumn('caf_patronato', 'importo_fornitore')) {
                $table->decimal('importo_fornitore', 10, 2)->default(0)->after('prezzo_pratica');
            }
        });

        Schema::table('send_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('send_requests', 'importo_fornitore')) {
                $table->decimal('importo_fornitore', 10, 2)->default(0)->after('prezzo_agente');
            }
        });

        $now = now();
        $defaultFornitore = (string) config('send.importo_fornitore', 0);
        $exists = DB::table('send_settings')->where('key', 'importo_fornitore')->exists();
        if (! $exists) {
            DB::table('send_settings')->insert([
                'key' => 'importo_fornitore',
                'value' => $defaultFornitore,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::create('billing_documents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('source', 40)->index(); // caf_monthly|send_monthly|agent_proforma
            $table->string('periodo', 7)->nullable()->index(); // YYYY_MM
            $table->string('idempotency_key', 80)->unique();
            $table->string('status', 30)->default('bozza')->index();
            $table->string('invoiceshelf_type', 20)->nullable(); // estimate|invoice
            $table->unsignedBigInteger('invoiceshelf_id')->nullable()->index();
            $table->string('unique_hash', 64)->nullable();
            $table->nullableMorphs('gestiio_subject');
            $table->decimal('totale', 12, 2)->default(0);
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_documents');

        Schema::table('send_requests', function (Blueprint $table) {
            if (Schema::hasColumn('send_requests', 'importo_fornitore')) {
                $table->dropColumn('importo_fornitore');
            }
        });

        Schema::table('caf_patronato', function (Blueprint $table) {
            if (Schema::hasColumn('caf_patronato', 'importo_fornitore')) {
                $table->dropColumn('importo_fornitore');
            }
        });

        Schema::table('tipi_caf_patronato', function (Blueprint $table) {
            if (Schema::hasColumn('tipi_caf_patronato', 'importo_fornitore')) {
                $table->dropColumn('importo_fornitore');
            }
        });

        DB::table('send_settings')->where('key', 'importo_fornitore')->delete();
    }
};
