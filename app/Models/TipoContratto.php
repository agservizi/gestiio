<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoContratto extends Model
{

    protected $table = "tipi_contratto";

    public const NOME_SINGOLARE = "tipo contratto";
    public const NOME_PLURALE = "tipi contratto";

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function contratti()
    {
        return $this->hasMany(ContrattoTelefonia::class, 'tipo_contratto_id', 'id');
    }

    public function gestore()
    {
        return $this->hasOne(Gestore::class, 'id', 'gestore_id');
    }

    public function mandati()
    {
        return $this->hasMany(Mandato::class, 'gestore_id', 'gestore_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeSoloAttivi(Builder $query): Builder
    {
        return $query->where('attivo',1);
    }

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
