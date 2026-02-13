<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CausaleTicket extends Model
{
    use HasFactory;

    protected $table = "tab_causali_tickets";

    public const NOME_SINGOLARE = "causale ticket";
    public const NOME_PLURALE = "causali ticket";

    public const SERVIZI = ['App\Models\ContrattoTelefonia' => 'Contratto telefonia', 'App\Models\SpedizioneBrt' => 'Spedizione BRT'];

    protected $fillable = [
        'servizio_type', 'descrizione_causale'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

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

    public function labelCausaleTicket()
    {


            return '<span class="badge badge-light-primary fw-bolder me-2">' .$this->descrizione_causale . '</span>';


    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
