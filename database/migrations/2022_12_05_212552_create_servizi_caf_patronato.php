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

        Schema::create('tab_esiti_caf_patronato', function (Blueprint $table) {
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

        DB::table('tab_esiti_caf_patronato')->delete();

        DB::table('tab_esiti_caf_patronato')->insert([
            0 => [
                'id' => 'da-gestire',
                'created_at' => '2022-12-05 21:48:10',
                'updated_at' => '2022-12-05 21:48:10',
                'nome' => 'Da Gestire',
                'colore_hex' => '#be38f3',
                'chiedi_motivo' => 0,
                'notifica_mail' => 0,
                'notifica_whatsapp' => 0,
                'attivo' => 0,
                'esito_finale' => 'in-lavorazione',
            ],
            1 => [
                'id' => 'finalizzato',
                'created_at' => '2022-12-05 21:57:21',
                'updated_at' => '2022-12-05 21:57:21',
                'nome' => 'Finalizzato',
                'colore_hex' => '#77bb41',
                'chiedi_motivo' => 0,
                'notifica_mail' => 0,
                'notifica_whatsapp' => 0,
                'attivo' => 0,
                'esito_finale' => 'ok',
            ],
            2 => [
                'id' => 'pronto',
                'created_at' => '2022-12-05 21:58:34',
                'updated_at' => '2022-12-05 21:58:34',
                'nome' => 'Pronto',
                'colore_hex' => '#669c35',
                'chiedi_motivo' => 0,
                'notifica_mail' => 0,
                'notifica_whatsapp' => 0,
                'attivo' => 0,
                'esito_finale' => 'ok',
            ],
            3 => [
                'id' => 'richiesta-documenti',
                'created_at' => '2022-12-05 21:57:51',
                'updated_at' => '2022-12-05 21:57:51',
                'nome' => 'Richiesta Documenti',
                'colore_hex' => '#ffaa00',
                'chiedi_motivo' => 0,
                'notifica_mail' => 0,
                'notifica_whatsapp' => 0,
                'attivo' => 0,
                'esito_finale' => 'in-lavorazione',
            ],
        ]);

        Schema::create('tipi_caf_patronato', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome');
            $table->string('model')->nullable();
            $table->string('view')->nullable();
            $table->decimal('prezzo_cliente');
            $table->decimal('prezzo_agente');
            $table->string('tipo')->index()->comment('caf|patronato');
        });

        Schema::create('caf_patronato', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->date('data');
            $table->foreignId('agente_id')->constrained('users');
            $table->string('esito_id')->index();
            $table->foreignId('tipo_caf_patronato_id')->constrained('tipi_caf_patronato');
            $table->string('nome');
            $table->string('cognome');
            $table->string('email');
            $table->string('cellulare');
            $table->string('codice_fiscale');
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');

            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->text('note')->nullable();
            $table->string('uid')->index();

            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->string('motivo_ko')->nullable();
            $table->boolean('pagato')->default(0);
            $table->decimal('provvigione_agente')->default(0);
            $table->decimal('provvigione_agenzia')->default(0);
            $table->decimal('prezzo_pratica');

            // Prodotti
            $table->unsignedBigInteger('prodotto_id')->nullable();
            $table->string('prodotto_type')->nullable();
            $table->index(['prodotto_id', 'prodotto_type']);

        });

        Schema::create('caf_patronato_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('caf_patronato_id')->nullable()->constrained('caf_patronato')->cascadeOnDelete();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
        });

        /*
        Schema::create('caf_pat_isee', function (Blueprint $table) {
            $table->id('caf_patronato_id');
            $table->timestamps();


            $table->foreign('caf_patronato_id')->on('caf_patronato')->references('id')->onDelete('cascade');
        });
        */

        Schema::table('movimenti_portafoglio', function (Blueprint $table) {
            // Prodotti
            $table->unsignedBigInteger('prodotto_id')->nullable();
            $table->string('prodotto_type')->nullable();
            $table->index(['prodotto_id', 'prodotto_type']);
        });

        DB::table('tipi_caf_patronato')->delete();

        DB::table('tipi_caf_patronato')->insert([
            0 => [
                'created_at' => '2022-12-06 19:33:42',
                'id' => 1,
                'model' => null,
                'nome' => 'Isee',
                'prezzo_agente' => '3.50',
                'prezzo_cliente' => '7.00',
                'tipo' => 'caf',
                'updated_at' => '2022-12-06 20:37:29',
                'view' => 'Isee',
            ],
            1 => [
                'created_at' => '2022-12-06 20:05:16',
                'id' => 2,
                'model' => null,
                'nome' => 'Contratti Di Locazione',
                'prezzo_agente' => '62.50',
                'prezzo_cliente' => '75.00',
                'tipo' => 'caf',
                'updated_at' => '2022-12-06 21:26:07',
                'view' => 'ContrattoLocazione',
            ],
            2 => [
                'created_at' => '2022-12-06 20:35:44',
                'id' => 3,
                'model' => null,
                'nome' => 'Imu',
                'prezzo_agente' => '25.00',
                'prezzo_cliente' => '30.00',
                'tipo' => 'caf',
                'updated_at' => '2022-12-06 21:26:45',
                'view' => 'Imu',
            ],
            3 => [
                'created_at' => '2022-12-06 20:36:00',
                'id' => 4,
                'model' => null,
                'nome' => 'Tasi',
                'prezzo_agente' => '25.00',
                'prezzo_cliente' => '30.00',
                'tipo' => 'caf',
                'updated_at' => '2022-12-06 22:34:31',
                'view' => 'Tasi',
            ],
            4 => [
                'created_at' => '2022-12-06 20:36:51',
                'id' => 5,
                'model' => null,
                'nome' => '730',
                'prezzo_agente' => '24.00',
                'prezzo_cliente' => '30.00',
                'tipo' => 'caf',
                'updated_at' => '2022-12-06 22:06:11',
                'view' => '730',
            ],
            5 => [
                'created_at' => '2022-12-06 21:27:42',
                'id' => 6,
                'model' => null,
                'nome' => 'Naspi',
                'prezzo_agente' => '7.50',
                'prezzo_cliente' => '10.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:28:46',
                'view' => 'Naspi',
            ],
            6 => [
                'created_at' => '2022-12-06 21:28:19',
                'id' => 7,
                'model' => null,
                'nome' => 'Maternità Obbligatoria',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:30:44',
                'view' => 'MaternitaObbligatoria',
            ],
            7 => [
                'created_at' => '2022-12-06 21:28:43',
                'id' => 8,
                'model' => null,
                'nome' => 'Assegno Unico',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:34:20',
                'view' => 'AssegnoUnico',
            ],
            8 => [
                'created_at' => '2022-12-06 21:29:10',
                'id' => 9,
                'model' => null,
                'nome' => 'Bonus Nido',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:32:42',
                'view' => 'BonusAsilo',
            ],
            9 => [
                'created_at' => '2022-12-06 21:29:37',
                'id' => 10,
                'model' => null,
                'nome' => 'Pensione Anticipata',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:18:38',
                'view' => 'Pensioni',
            ],
            10 => [
                'created_at' => '2022-12-06 21:30:58',
                'id' => 11,
                'model' => null,
                'nome' => 'Assegno Sociale',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:23:14',
                'view' => 'AssegnoSociale',
            ],
            11 => [
                'created_at' => '2022-12-06 21:32:06',
                'id' => 12,
                'model' => null,
                'nome' => 'Assegno Ordinario/pensione Di Inabilita\' Inps',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '25.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:25:10',
                'view' => 'AssegnoOrdinario',
            ],
            12 => [
                'created_at' => '2022-12-06 21:33:19',
                'id' => 13,
                'model' => null,
                'nome' => 'Invalidita\' Civile- Indennita\' Di Accompagnamento Legge104/92 - Collocamento Mirato',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '25.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:27:18',
                'view' => 'Invalidita',
            ],
            13 => [
                'created_at' => '2022-12-06 21:40:22',
                'id' => 14,
                'model' => null,
                'nome' => 'Pensione Di Anzianita\'',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:18:48',
                'view' => 'Pensioni',
            ],
            14 => [
                'created_at' => '2022-12-06 21:40:59',
                'id' => 15,
                'model' => null,
                'nome' => 'Pensione Vecchiaia',
                'prezzo_agente' => '12.50',
                'prezzo_cliente' => '20.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:21:21',
                'view' => 'Pensioni',
            ],
            15 => [
                'created_at' => '2022-12-06 22:18:23',
                'id' => 16,
                'model' => null,
                'nome' => 'Pensione Di Reversibilità',
                'prezzo_agente' => '10.00',
                'prezzo_cliente' => '10.00',
                'tipo' => 'patronato',
                'updated_at' => '2022-12-06 22:21:32',
                'view' => 'PensioneReversibilita',
            ],
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('movimenti_portafoglio', function (Blueprint $table) {
            // Prodotti
            $table->dropIndex('movimenti_portafoglio_prodotto_id_prodotto_type_index');
            $table->dropColumn('prodotto_id')->nullable();
            $table->dropColumn('prodotto_type')->nullable();
        });

        Schema::dropIfExists('caf_patronato_allegati');
        Schema::dropIfExists('caf_pat_isee');
        Schema::dropIfExists('caf_patronato');
        Schema::dropIfExists('tipi_caf_patronato');
        Schema::dropIfExists('tab_esiti_caf_patronato');

    }
};
