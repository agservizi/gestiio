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
        Schema::create('comparasemplice_esiti', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->boolean('chiedi_motivo')->default(0);
            $table->boolean('notifica_mail')->default(0);
            $table->boolean('notifica_whatsapp')->default(0);
            $table->boolean('attivo')->index();
            $table->string('esito_finale')->nullable();

        });


        Schema::create('comparasemplice', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('cellulare');

            $table->string('tipo_segnalazione')->index();

            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);

            $table->string('uid')->index();

        });

        \DB::table('comparasemplice_esiti')->insert(array(
            0 =>
                array(
                    'attivo' => 1,
                    'chiedi_motivo' => 0,
                    'colore_hex' => '#000000',
                    'created_at' => '2023-07-30 18:21:34',
                    'esito_finale' => 'in-lavorazione',
                    'id' => 'in-gestione',
                    'nome' => 'In Gestione',
                    'notifica_mail' => 0,
                    'notifica_whatsapp' => 0,
                    'updated_at' => '2023-07-30 18:21:34',
                ),
            1 =>
                array(
                    'attivo' => 1,
                    'chiedi_motivo' => 0,
                    'colore_hex' => '#000000',
                    'created_at' => '2023-07-30 18:21:55',
                    'esito_finale' => 'ko',
                    'id' => 'ko',
                    'nome' => 'Ko',
                    'notifica_mail' => 0,
                    'notifica_whatsapp' => 0,
                    'updated_at' => '2023-07-30 18:21:55',
                ),
            2 =>
                array(
                    'attivo' => 1,
                    'chiedi_motivo' => 0,
                    'colore_hex' => '#669c35',
                    'created_at' => '2023-07-30 18:21:46',
                    'esito_finale' => 'ok',
                    'id' => 'ok',
                    'nome' => 'Ok',
                    'notifica_mail' => 0,
                    'notifica_whatsapp' => 0,
                    'updated_at' => '2023-07-30 18:21:46',
                ),
        ));


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comparasemplice');
        Schema::dropIfExists('comparasemplice_esiti');
    }
};
