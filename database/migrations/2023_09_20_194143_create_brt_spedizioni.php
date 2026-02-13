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
        Schema::create('brt_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->string('tipo_porto', 3);
            $table->string('ragione_sociale_destinatario', 70);
            $table->string('indirizzo_destinatario', 35);
            $table->string('cap_destinatario', 9);
            $table->string('localita_destinazione', 35);
            $table->string('nazione_destinazione', 2);
            $table->string('mobile_referente_consegna', 16);
            $table->decimal('numero_pacchi', 2, 0);
            $table->decimal('peso_totale', 6, 1);
            $table->decimal('volume_totale', 5, 3);

            $table->string('pudo_id')->nullable();

            $table->string('nome_mittente');
            $table->string('email_mittente')->nullable();
            $table->string('mobile_mittente')->nullable();

            $table->decimal('contrassegno')->nullable();

            $table->text('response')->nullable();
            $table->text('labels')->nullable();

            $table->string('esito')->nullable();
            $table->string('esito_testo')->nullable();



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('brt_spedizioni');
    }
};
