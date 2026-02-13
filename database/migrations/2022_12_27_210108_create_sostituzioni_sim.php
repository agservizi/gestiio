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
        Schema::create('attivazioni_sim_sostituzioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('attivazione_sim_id')->constrained('attivazioni_sim');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('motivazione');
            $table->decimal('importo')->default(0);
            $table->string('seriale_vecchia_sim');
            $table->string('seriale_nuova_sim');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sostituzioni_sim');
    }
};
