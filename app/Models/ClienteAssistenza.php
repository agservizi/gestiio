<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteAssistenza extends Model
{
    use HasFactory;
    protected $table="clienti_assistenza";
    protected $fillable = [
        'nome',
        'cognome',
        'codice_fiscale',
        'email',
        'telefono',
    ];

    public const NOME_SINGOLARE = "cliente assistenza";
    public const NOME_PLURALE = "clienti assistenza";

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

    public function nominativo()
    {
        return $this->cognome . ' ' . $this->nome;
    }
    public static function selected($id)
    {
        if ($id) {
            $record = self::find($id);
            if ($record) {
                return '<option value="' . $record->id . '">' . $record->nominativo().' '.$record->codice_fiscale . '</option>';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
