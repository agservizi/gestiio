<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoAssistenza extends Model
{
    use HasFactory;

    protected $table = "prodotti_assistenza";

    public const NOME_SINGOLARE = "prodotto assistenza";
    public const NOME_PLURALE = "prodotti assistenze";

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
    public static function selected($id)
    {
        if ($id) {
            $record = self::find($id);
            if ($record) {
                return '<option value="' . $record->id . '">' . $record->nome . '</option>';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
