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
        Schema::create('brt_contatti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');

            $table->string('ragione_sociale_destinatario', 70)->index();
            $table->string('indirizzo_destinatario', 35)->nullable();
            $table->string('cap_destinatario', 9)->nullable();
            $table->string('localita_destinazione', 35)->nullable();
            $table->string('provincia_destinatario', 2)->nullable();
            $table->string('nazione_destinazione', 2)->nullable();
            $table->string('mobile_referente_consegna', 16)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brt_contatti');
    }
};
