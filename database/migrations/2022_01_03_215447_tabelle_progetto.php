<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TabelleProgetto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('portafoglio')->default(0);
        });


        Schema::create('licenze', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->text('nome');
            $table->string('venditore');
            $table->string('numero_licenza');
            $table->date('data_acquisto');
            $table->text('url')->nullable();
            $table->text('download')->nullable();
        });


        Schema::create('tab_esiti', function (Blueprint $table) {
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

        Schema::create('tab_motivi_ko', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->unsignedBigInteger('conteggio')->default(0)->index();
        });


        Schema::create('tab_gestori', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->string('colore_hex')->nullable();
            $table->string('url')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('attivo')->index();
        });

        Schema::create('tipi_contratto', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
            $table->foreignId('gestore_id')->constrained('tab_gestori');
            $table->string('colore_hex');
            $table->boolean('attivo')->index();
        });

        Schema::create('mandati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('gestore_id')->constrained('tab_gestori');
            $table->foreignId('agente_id')->constrained('users');
            $table->boolean('attivo')->default(0);
        });

        Schema::create('agenti_fasce_tipi_contratto', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('agente_id')->constrained('users');
            $table->foreignId('tipo_contratto_id')->constrained('tipi_contratto');
            $table->unsignedInteger('da_ordini');
            $table->unsignedInteger('a_ordini')->nullable();
            $table->decimal('importo_per_ordine')->nullable();
            $table->decimal('importo_bonus')->nullable();
            $table->decimal('importo_soglie_precedenti')->nullable();

        });


        Schema::create('clienti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('codice_fiscale');
            $table->string('ragione_sociale')->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->string('natura_giuridica', 20)->nullable();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('visura')->nullable();
        });


        Schema::create('contratti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('data')->index();
            $table->string('codice_cliente')->nullable()->index();
            $table->string('codice_contratto')->nullable()->index();
            $table->foreignId('agente_id')->constrained('users');
            $table->foreignId('tipo_contratto_id')->constrained('tipi_contratto');
            $table->string('esito_id')->index();
            $table->string('esito_finale')->nullable()->index();
            $table->char('mese_pagamento', 7)->nullable()->index();
            $table->foreignId('cliente_id')->nullable()->constrained('clienti');
            $table->string('codice_fiscale');
            $table->string('ragione_sociale')->nullable();
            $table->string('partita_iva', 20)->nullable();
            $table->string('natura_giuridica', 20)->nullable();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email')->nullable();
            $table->string('telefono');
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->text('note')->nullable();
            $table->string('uid')->index();
            $table->string('iban')->nullable();
            $table->string('carta_di_credito')->nullable();
            $table->string('carta_di_credito_scadenza',5)->nullable();
            $table->string('carta_di_credito_cvc')->nullable();
            $table->boolean('pagato')->default(0);
            $table->string('motivo_ko')->nullable();





        });

        Schema::create('contratti_allegati', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('contratto_id')->nullable()->constrained('contratti')->cascadeOnDelete();
            $table->string('uid')->nullable()->index();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
        });


        Schema::create('produzioni_operatori', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedSmallInteger('anno')->index();
            $table->unsignedSmallInteger('mese')->index();
            $table->unsignedSmallInteger('conteggio_ordini_ok')->default(0);
            $table->unsignedSmallInteger('conteggio_ordini_ko')->default(0);
            $table->smallInteger('conteggio_ordini_in_lavorazione')->storedAs('conteggio_ordini-conteggio_ordini_ko-conteggio_ordini_ok');
            $table->unsignedSmallInteger('conteggio_ordini')->default(0);
            $table->unsignedSmallInteger('conteggio_ordini_in_pagamento')->default(0);
            $table->unsignedSmallInteger('conteggio_rid')->default(0);
            $table->decimal('totale_ore_mese')->default(0);
            $table->decimal('importo_ordini')->default(0);
            $table->decimal('importo_ore_lavorate')->default(0);
            $table->decimal('importo_rid')->default(0);
            $table->decimal('guadagno')->nullable();
            $table->foreignId('piano_retribuzione_id')->index()->nullable();
            $table->decimal('importo_totale')->storedAs('importo_ordini+importo_ore_lavorate+importo_rid');
            $table->text('log_calcolo')->nullable();
            $table->foreign('user_id')->on('users')->references('id')->onDelete('cascade');
            $table->json('dettaglio_tipi_contratto')->nullable();
        });

        Schema::create('files_cartelle', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nome')->index();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('cartella_id')->nullable()->constrained('files_cartelle')->cascadeOnDelete();
            $table->string('filename_originale');
            $table->string('path_filename');
            $table->unsignedBigInteger('dimensione_file');
            $table->string('tipo_file');
        });

        Schema::create('abi_cab', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('abi', 5);
            $table->string('cab', 5);
            $table->string('banca');
            $table->string('filiale');
        });

        Schema::create('visure_camerali', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->foreignId('contratto_id')->constrained('contratti');
            $table->string('tipo');
            $table->string('richiesta_id');
            $table->string('stato_richiesta');
            $table->json('allegati')->nullable();
            $table->json('response');
            $table->decimal('prezzo');
        });

        Schema::create('movimenti_portafoglio', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->constrained('users');
            $table->decimal('importo');
            $table->string('descrizione');
        });

        Schema::create('pagamenti', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('agente_id')->index();
            $table->string('servizio')->index();
            $table->string('transaction_id');
            $table->string('descrizione');
            $table->string('status');
            $table->decimal('importo');
            $table->string('valuta', 3)->nullable();
            $table->text('response');

        });

        Schema::create('registro_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('data')->index();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->text('headers')->nullable();
            $table->longText('attachments')->nullable();
        });

        Schema::create('guadagni_agenzia', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedSmallInteger('anno')->index();
            $table->unsignedSmallInteger('mese')->index();
            $table->decimal('entrate')->default(0);
            $table->decimal('uscite')->default(0);
            $table->decimal('utile')->default(0);
            $table->json('dettaglio_tipi_contratto')->nullable();

        });

        Schema::create('notifiche', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('destinatario')->index();
            $table->text('testo');
        });
        Schema::create('notifiche_letture', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('notifica_id')->constrained('notifiche')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        ///
    }
}
