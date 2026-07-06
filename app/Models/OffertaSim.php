<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffertaSim extends Model
{
    use HasFactory;

    protected $table = 'offerte_sim';

    public const NOME_SINGOLARE = 'offerta sim';

    public const NOME_PLURALE = 'offerte sim';

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function gestore(): BelongsTo
    {
        return $this->belongsTo(GestoreAttivazioniSim::class, 'gestore_id');
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
    public static function selected($id)
    {
        if ($id) {
            $record = self::find($id);
            if ($record) {
                return "<option value='$id' selected>{$record->nome}</option>";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
