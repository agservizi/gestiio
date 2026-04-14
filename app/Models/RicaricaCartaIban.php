<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RicaricaCartaIban extends Model
{
    use HasFactory;

    protected $table = 'ricariche_carte_iban';

    public const NOME_SINGOLARE = 'iban';
    public const NOME_PLURALE = 'iban';

    protected $fillable = [
        'cognome',
        'nome',
        'codice_fiscale',
        'telefono',
        'email',
        'iban',
        'intestatario_iban',
        'carta',
        'note',
    ];

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    public function nominativo(): string
    {
        return $this->cognome . ' ' . $this->nome;
    }
}
