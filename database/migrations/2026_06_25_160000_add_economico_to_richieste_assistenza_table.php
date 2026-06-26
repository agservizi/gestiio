<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('richieste_assistenza', function (Blueprint $table) {
            if (! Schema::hasColumn('richieste_assistenza', 'tipo_operazione')) {
                $table->string('tipo_operazione', 80)->nullable()->after('prodotto_assistenza_id')->index();
            }

            if (! Schema::hasColumn('richieste_assistenza', 'importo_economico')) {
                $table->decimal('importo_economico', 10, 2)->nullable()->after('tipo_operazione');
            }

            if (! Schema::hasColumn('richieste_assistenza', 'economico_contabilizzato')) {
                $table->boolean('economico_contabilizzato')->default(false)->after('importo_economico')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('richieste_assistenza', function (Blueprint $table) {
            if (Schema::hasColumn('richieste_assistenza', 'economico_contabilizzato')) {
                $table->dropColumn('economico_contabilizzato');
            }

            if (Schema::hasColumn('richieste_assistenza', 'importo_economico')) {
                $table->dropColumn('importo_economico');
            }

            if (Schema::hasColumn('richieste_assistenza', 'tipo_operazione')) {
                $table->dropColumn('tipo_operazione');
            }
        });
    }
};
