<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CafPatronato extends Model
{

    protected $table = "caf_patronato";

    public const NOME_SINGOLARE = "pratica caf patronato";
    public const NOME_PLURALE = "pratiche caf patronato";

    protected $casts = [
        'data' => 'datetime'
    ];


    public const ESITI = [
        'ko' => '#eb3662',
        'ok' => '#4dc682',
        'in-lavorazione' => '#009EF7'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('filtroOperatore', function (Builder $builder) {
            if (Auth::check() && Auth::user()->hasPermissionTo('agente')) {
                $builder->where('agente_id', Auth::id());
            }
        });

    }

    public static function puoModificareEsito()
    {
        return Auth::user()->hasAnyPermission(['admin', 'supervisore']);
    }

    public static function puoModificare()
    {
        return !Auth::user()->hasPermissionTo('supervisore');
    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente()
    {
        return $this->hasOne(User::class, 'id', 'agente_id');
    }

    public function allegati()
    {
        return $this->hasMany(AllegatoCafPatronato::class, 'caf_patronato_id');
    }
    public function allegatiPerCliente()
    {
        return $this->hasMany(AllegatoCafPatronato::class, 'caf_patronato_id')->where('per_cliente',1);
    }

    public function caricatoDa()
    {
        return $this->hasOne(User::class, 'id', 'caricato_da_user_id');
    }

    public function esito()
    {
        return $this->hasOne(EsitoCafPatronato::class, 'id', 'esito_id');
    }

    public function prodotto()
    {
        return $this->morphTo();
    }

    public function tipo()
    {
        return $this->hasOne(TipoCafPatronato::class,'id','tipo_caf_patronato_id');
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
    public function nominativo()
    {
        return $this->cognome . ' ' . $this->nome;
    }

    public function tipoProdottoBlade()
    {
        return str_replace('App\Models\CafPat', '', $this->prodotto_type);
    }

    public function labelPagato()
    {
        if ($this->pagato) return "<span class='badge badge-success' >Pagato</span>";
    }

    public function bulletEsitoFinale()
    {
        if ($this->esito_finale) {

            return '<span class="bullet bullet-vertical d-flex align-items-center min-h-20px mh-100 me-2" style=background-color:' . self::ESITI[$this->esito_finale] . ';"></span>';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public function tipoProdotto()
    {
        return str_replace('App\Models\\', '', $this->prodotto_type);
    }


}
