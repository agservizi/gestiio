<?php

use App\Models\CausaleTicket;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt', 'descrizione_causale' => 'Prenotazione ritiro']);
        CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt', 'descrizione_causale' => 'Danni']);
        CausaleTicket::create(['servizio_type' => 'App\Models\SpedizioneBrt', 'descrizione_causale' => 'Richiesta info e spedizioni']);
        CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia', 'descrizione_causale' => 'Supporto tecnico']);
        CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia', 'descrizione_causale' => 'Amministrazione']);
        CausaleTicket::create(['servizio_type' => 'App\Models\ContrattoTelefonia', 'descrizione_causale' => 'Informazioni su contratto']);
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
