<?php

use App\Models\ContrattoTelefonia;
use App\Models\Ticket;
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
        $tickets = Ticket::get();
        foreach ($tickets as $ticket) {
            $contratto = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')->find($ticket->contratto_id);
            $ticket->servizio()->associate($contratto)->save();
        }



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
