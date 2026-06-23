<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inpost_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->foreignId('caricato_da_user_id')->nullable()->constrained('users');
            $table->string('delivery_type', 20)->default('point');
            $table->string('ragione_sociale_destinatario', 120);
            $table->string('indirizzo_destinatario', 120);
            $table->string('cap_destinatario', 20);
            $table->string('localita_destinazione', 120);
            $table->string('provincia_destinatario', 10)->nullable();
            $table->string('nazione_destinazione', 2)->default('IT');
            $table->string('email_destinatario')->nullable();
            $table->string('mobile_referente_consegna', 32);
            $table->decimal('numero_pacchi', 3, 0);
            $table->decimal('peso_totale', 8, 1);
            $table->decimal('volume_totale', 8, 3);
            $table->string('punto_inpost_id')->nullable();
            $table->string('punto_inpost_label')->nullable();
            $table->string('nome_mittente', 120);
            $table->string('email_mittente')->nullable();
            $table->string('mobile_mittente', 32)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response')->nullable();
            $table->json('labels')->nullable();
            $table->json('dati_colli')->nullable();
            $table->json('altri_dati')->nullable();
            $table->string('shipment_uuid')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('label_url')->nullable();
            $table->string('esito')->nullable();
            $table->string('esito_testo')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inpost_spedizioni');
    }
};
