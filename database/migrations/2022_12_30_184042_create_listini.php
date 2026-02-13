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
        Schema::create('listini', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('prodotto')->index();
            $table->string('nome');
            $table->boolean('attivo')->index();
        });

        $listino=new \App\Models\Listino();
        $listino->nome='Listino 1';
        $listino->attivo=1;
        $listino->prodotto='contratto-telefonia';
        $listino->save();

        Schema::create('listini_fasce_contratti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('listino_id')->constrained('listini');
            $table->foreignId('tipo_contratto_id')->constrained('tipi_contratto');
            $table->unsignedInteger('da_contratti');
            $table->unsignedInteger('a_contratti')->nullable();
            $table->decimal('importo_per_contratto')->nullable();
            $table->decimal('importo_bonus')->nullable();
            $table->decimal('importo_soglie_precedenti')->nullable();
        });




    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listini');
    }
};
