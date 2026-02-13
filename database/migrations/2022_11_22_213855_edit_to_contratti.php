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
        Schema::table('contratti', function (Blueprint $table) {
            //Documento
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('rilasciato_da')->nullable();
            $table->date('data_rilascio')->nullable();
            $table->date('data_scadenza')->nullable();
            $table->foreignId('cittadinanza')->nullable();

            $table->string('civico')->nullable()->after('indirizzo');
            $table->string('nome_citofono')->nullable()->after('cap');
            $table->string('scala')->nullable()->after('nome_citofono');
            $table->string('piano')->nullable()->after('scala');

            $table->unsignedBigInteger('prodotto_id')->nullable();
            $table->string('prodotto_type')->nullable();
            $table->index(['prodotto_id', 'prodotto_type']);

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
            //
        });
    }
};
