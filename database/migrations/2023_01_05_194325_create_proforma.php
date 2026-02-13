<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fatture_proforma_intestazioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained('users');
            $table->string('denominazione');
            $table->string('codice_fiscale', 20)->nullable();
            $table->string('nazione')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 10)->nullable();
        });

        Schema::create('fatture_proforma', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data');
            $table->unsignedBigInteger('numero')->index();
            $table->foreignId('intestazione_id')->constrained('fatture_proforma_intestazioni');
            $table->decimal('totale_imponibile')->default(0);
            $table->decimal('aliquota_iva')->default(0);
            $table->decimal('totale_con_iva')->default(0);
        });

        Schema::create('fatture_proforma_righe', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('fattura_proforma_id')->constrained('fatture_proforma')->cascadeOnDelete();
            $table->string('descrizione');
            $table->string('classe')->nullable();
            $table->unsignedSmallInteger('quantita');
            $table->decimal('imponibile');
            $table->decimal('totale_imponibile')->storedAs('imponibile*quantita');
        });

        Schema::table('contratti', function (Blueprint $table) {
            $table->foreignId('fattura_proforma_id')->nullable()->constrained('fatture_proforma');
        });

        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->foreignId('fattura_proforma_id')->nullable()->constrained('fatture_proforma');
        });

        Schema::table('produzioni_operatori', function (Blueprint $table) {
            $table->foreignId('fattura_proforma_id')->nullable()->constrained('fatture_proforma');
        });



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('contratti', function (Blueprint $table) {
            $table->dropForeign('contratti_fattura_proforma_id_foreign');
        });

        Schema::table('contratti', function (Blueprint $table) {
            $table->dropColumn('fattura_proforma_id');
        });


        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->dropForeign('contratti_energia_fattura_proforma_id_foreign');
        });
        Schema::table('contratti_energia', function (Blueprint $table) {
            $table->dropColumn('fattura_proforma_id');
        });

        Schema::dropIfExists('fatture_proforma_righe');
        Schema::dropIfExists('fatture_proforma');
        Schema::dropIfExists('fatture_proforma_intestazioni');
    }
};
