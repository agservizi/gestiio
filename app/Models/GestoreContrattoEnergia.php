<?php

namespace App\Models;

use App\Http\MieClassiCache\CacheGestoriDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GestoreContrattoEnergia extends Model
{
    public const CATEGORIE_PRATICA = [
        'consumer' => 'Consumer',
        'business' => 'Business',
    ];

    protected $table = "tab_gestori_contratti_energia";

    public const NOME_SINGOLARE = "gestore contratti energia";
    public const NOME_PLURALE = "gestori contratti energia";


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
            if ($this->logo_contenuto_base64) {
                $mime = $this->logo_mime_type ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . $this->logo_contenuto_base64;
            }
            return '/images/logo-placeholder.png';
        }

        if ($this->logo_contenuto_base64) {
            $mime = $this->logo_mime_type ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . $this->logo_contenuto_base64;
        }

        $relativePath = ltrim((string) $this->logo, '/');
        if (!Storage::disk('public')->exists($relativePath) && Storage::exists('/' . $relativePath)) {
            $contenuto = Storage::get('/' . $relativePath);
            if ($contenuto !== null) {
                Storage::disk('public')->put($relativePath, $contenuto);
            }
        }

        return '/storage/' . $relativePath;
    }

    public function categoriaLabel(): string
    {
        return self::CATEGORIE_PRATICA[$this->categoria_pratica] ?? ucfirst((string) $this->categoria_pratica);
    }

    public function warningConfigurazioneProvvigioniBusiness(): ?string
    {
        if ($this->categoria_pratica !== 'business') {
            return null;
        }

        if ($this->importo_contratto_business === null) {
            return 'Manca importo contratto business';
        }

        if ($this->importo_pagamento_bollettino_business === null && $this->importo_pagamento_bollettino !== null) {
            return 'Manca importo bollettino business';
        }

        return null;
    }

}
