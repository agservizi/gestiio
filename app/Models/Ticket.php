<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $table = "tickets";

    public const NOME_SINGOLARE = "ticket";
    public const NOME_PLURALE = "tickets";

    public const TIPI_TICKETS = [
        'supporto' => 'Supporto tecnico',
        'amministrazione' => 'Amministrazione',
        'info-contratto' => 'Informazioni su contratto',
    ];

    public const STATI_TICKETS = [
        'aperto' => ['testo' => 'Aperto', 'colore' => 'success', 'coloreHex' => '#E5FFF2'],
        'in_lavorazione' => ['testo' => 'In Lavorazione', 'colore' => 'danger', 'coloreHex' => '#E5FFF2'],
        'chiuso' => ['testo' => 'Chiuso', 'colore' => 'info', 'coloreHex' => '#E5FFF2'],
    ];


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */


    public function causaleTicket(): HasOne
    {
        return $this->hasOne(CausaleTicket::class,'id','causale_ticket_id');
    }

    public function lettura(): HasOne
    {
        return $this->hasOne(LetturaTicket::class, 'ticket_id')->where('user_id', \Auth::id());
    }

    public function messaggi()
    {
        return $this->hasMany(MessaggioTicket::class, 'ticket_id', 'id');
    }

    public function servizio()
    {
        return $this->morphTo();
    }

    public function utente()
    {
        return $this->hasOne(User::class, 'id', 'user_id',);
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */


    public function classeServizio()
    {
        return \Illuminate\Support\Str::of($this->servizio_type)->afterLast('\\')->value();
    }

    public function uidTicket()
    {
        return '#' . Str::substr($this->uid, -6);
    }

    public function labelStatoTicket()
    {

        $stato = self::STATI_TICKETS[$this->stato];
        if ($stato) {

            return '<span class="badge badge-' . $stato['colore'] . ' fw-bolder me-2">' . $stato['testo'] . '</span>';
        }

    }

    public function labelBoxStatoTicket()
    {

        $stato = self::STATI_TICKETS[$this->stato];
        if ($stato) {
            return '<div class="d-flex flex-center w-50px h-50px w-lg-75px h-lg-75px flex-shrink-0 rounded text-' . $stato['colore'] . ' bg-light-' . $stato['colore'] . ' fw-bolder">' . $stato['testo'] . '</div>';
        }

    }




    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
