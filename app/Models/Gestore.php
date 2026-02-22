<?php

namespace App\Models;

use App\Http\MieClassiCache\CacheGestoriDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Gestore extends Model
{
    use HasFactory;

    protected $table = "tab_gestori";

    public const NOME_SINGOLARE = "gestore";
    public const NOME_PLURALE = "gestori";


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        self::saved(function ($model) {
            CacheGestoriDashboard::forget();
        });

    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function tipiContratto()
    {
        return $this->hasMany(TipoContratto::class, 'gestore_id');
    }

    public function mandati()
    {
        return $this->hasMany(Mandato::class, 'gestore_id', 'id');
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

    public function immagineLogo()
    {
        if (!$this->logo) {
            return '/images/logo-placeholder.png';
        }

        $relativePath = ltrim((string) $this->logo, '/');

        // Migra al volo eventuali file salvati sul disk legacy.
        if (!Storage::disk('public')->exists($relativePath) && Storage::exists('/' . $relativePath)) {
            $contenuto = Storage::get('/' . $relativePath);
            if ($contenuto !== null) {
                Storage::disk('public')->put($relativePath, $contenuto);
            }
        }

        return '/storage/' . $relativePath;
    }

}
