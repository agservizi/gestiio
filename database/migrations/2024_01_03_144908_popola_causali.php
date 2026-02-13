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
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt','descrizione_causale' => 'Prenotazione ritiro']);
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt','descrizione_causale' => 'Danni']);
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt','descrizione_causale' => 'Richiesta info e spedizioni']);
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia','descrizione_causale' => 'Supporto tecnico']);
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia','descrizione_causale' => 'Amministrazione']);
        \App\Models\CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia','descrizione_causale' => 'Informazioni su contratto']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
