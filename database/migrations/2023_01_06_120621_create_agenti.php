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
        Schema::create('agenti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained('users');
            $table->string('ragione_sociale')->nullable();
            $table->string('codice_fiscale', 20)->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('iban')->nullable();
            $table->string('visura_camerale')->nullable();

            $table->foreignId('listino_telefonia_id')->nullable()->constrained('listini');
            $table->boolean('paga_con_paypal')->default(0);


        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('nome')->after('name');
            $table->string('alias')->nullable()->after('email');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nome');
            $table->dropColumn('alias');
        });

        Schema::dropIfExists('agenti');
    }
};
