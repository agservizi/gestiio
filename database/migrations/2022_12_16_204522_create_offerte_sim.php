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
        Schema::create('offerte_sim', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('gestore_id')->constrained('tab_gestori_attivazioni_sim');
            $table->string('nome')->index();
            $table->decimal('prezzo');
            $table->text('descrizione')->nullable();
            $table->boolean('attiva')->index();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offerte_sim');
    }
};
