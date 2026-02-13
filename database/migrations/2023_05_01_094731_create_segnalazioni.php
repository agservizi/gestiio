<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::create('tab_esiti_segnalazioni', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->boolean('chiedi_motivo')->default(0);
            $table->boolean('notifica_mail')->default(0);
            $table->boolean('attivo')->index();
        });


        Schema::create('segnalazioni', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->string('nome_azienda');
            $table->string('partita_iva',50);
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap',5)->nullable();
            $table->string('telefono');
            $table->string('nome_referente');
            $table->string('cognome_referente');
            $table->string('email_referente');
            $table->string('fatturato')->nullable();
            $table->string('settore')->nullable();
            $table->string('provincia')->nullable();
            $table->string('forma_giuridica')->nullable();
        });



        \DB::table('tab_esiti_segnalazioni')->insert(array (
            0 =>
                array (
                    'id' => 'annullato',
                    'created_at' => '2023-05-01 10:26:25',
                    'updated_at' => '2023-05-01 10:26:25',
                    'nome' => 'Annullato',
                    'colore_hex' => '#ff4013',
                    'chiedi_motivo' => 0,
                    'notifica_mail' => 0,
                    'attivo' => 1,
                ),
            1 =>
                array (
                    'id' => 'gestito',
                    'created_at' => '2023-05-01 10:26:12',
                    'updated_at' => '2023-05-01 10:26:12',
                    'nome' => 'Gestito',
                    'colore_hex' => '#77bb41',
                    'chiedi_motivo' => 0,
                    'notifica_mail' => 0,
                    'attivo' => 1,
                ),
            2 =>
                array (
                    'id' => 'in-gestione',
                    'created_at' => '2023-05-01 10:25:48',
                    'updated_at' => '2023-05-01 10:25:48',
                    'nome' => 'In Gestione',
                    'colore_hex' => '#fec700',
                    'chiedi_motivo' => 0,
                    'notifica_mail' => 0,
                    'attivo' => 1,
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
        Schema::dropIfExists('tab_esiti_segnalazioni');
        Schema::dropIfExists('segnalazioni');
    }
};
