<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServizioMutuo extends Model
{
    use HasFactory;

    protected $table = "servizio_mutui";
    protected $primaryKey = 'servizio_id';


    public const NOME_SINGOLARE = "servizio mutuo";
    public const NOME_PLURALE = "servizio mutui";


    protected $casts = [
        'data_di_nascita' => 'datetime'
    ];

    public const FINALIITA = [
        'acquisto prima casa',
        'acquisto seconda casa',
        'acquisto in asta giudiziaria',
        'acquisto + ristrutturazione',
        'surroga (cambio mutuo spese zero)',
        'surroga + liquidità',
        'sostituzione + liquidità',
        'ristrutturazione',
        'liquidità',
    ];

    public const TIPI_TASSO = [
        'fisso', 'variabile', 'variabile con cap', 'variabile a rata costante', 'misto'
    ];

    public const DURATA = [
        5=>'5 anni', 7=>'7 anni', 10=>'10 anni', 12=>'12 anni', 15=>'15 anni', 20=>'20 anni', 25=>'25 anni', 30=>'30 anni', 35=>'35 anni', 40=>'40 anni'
    ];

    public const POSIZIONE_LAVORATIVA=[
      'dip. tempo indeterminato',
      'dip. tempo determinato',
      'autonomo con P.IVA',
      'altro impiego',
    ];

    public const STATO_RICERCA=[
        'firmato compromesso',
        'fatta proposta acquisto',
        'individuato immobile',
        'alla ricerca dell\' immobile',
        'immobile già di proprietà',
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

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
