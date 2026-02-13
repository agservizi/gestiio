<?php

namespace App\Models;

use App\Http\Funzioni\FunzioniContatti;
use App\Notifications\PasswordResetNotification;
use App\Notifications\VerifyEmail;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use  HasFactory, Notifiable, HasRoles, Billable, TwoFactorAuthenticatable;
    use FunzioniContatti;


    public const NOME_PLURALE = 'operatori';
    public const NOME_SINGOLARE = 'operatore';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cognome'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_accesso' => 'datetime',
        'invio_dati_accesso' => 'datetime',
        'extra' => 'array'
    ];


    public function routeNotificationForTwilio()
    {
        return $this->telefono;
    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function agente(): HasOne
    {
        return $this->hasOne(Agente::class, 'user_id');
    }

    public function contratti()
    {
        return $this->hasMany(ContrattoTelefonia::class, 'agente_id');
    }

    public function cafPatronato()
    {
        return $this->hasMany(CafPatronato::class, 'agente_id');
    }


    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */
    public function scopeAgente($builder)
    {
        return $builder->whereHas('permissions', function ($q) {
            $q->where('name', 'agente');
        });
    }

    public function scopeContrattiOggi($builder)
    {
        return $builder->whereHas('contratti', function ($q) {
            $q->whereDate('created_at', Carbon::today());
        });
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD RESET LOCALIZZATO
    |--------------------------------------------------------------------------
    */

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }


    /**********************
     * ALTRO
     **********************/
    public function nominativo()
    {
        return $this->nome . ' ' . $this->cognome;
    }

    public function denominazione()
    {
        return $this->name . ' ' . $this->cognome;
    }

    public function iniziali()
    {
        return $this->nome[0] . $this->cognome[0];
    }

    public function cogomeN()
    {
        return $this->cognome . ' ' . $this->nome[0];
    }

    public function aliasAgente()
    {
        return $this->alias;
    }

    public function codiceAgente()
    {
        return 'AG' . str_pad(($this->id - 1), 5, '0', STR_PAD_LEFT);
    }


    public static function selected($id)
    {
        if ($id) {
            $record = self::find($id);
            if ($record) {
                return "<option value='$id' selected>{$record->cognome} {$record->nome}</option>";
            }
        }
    }


    /***************************************************
     * Campo extra
     ***************************************************/


    public function setExtra($value)
    {

        $array = $this->extra;
        foreach ($value as $key => $val) {
            $array[$key] = $val;
        }
        $this->attributes['extra'] = json_encode($array);
        $this->save();

    }


    public function getExtra($key = null)
    {
        if ($key !== null && is_array($this->extra)) {
            if (array_key_exists($key, $this->extra)) {
                return $this->extra[$key];
            }
        }
        return null;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {

        $this->extra = ['invio_email_verifica' => Carbon::now()->format('d/m/Y H:m:s')];
        $this->save();
        $this->notify(new VerifyEmail());
    }

    public function userLevel($small, $user)
    {

        $livelli = ['agente', 'admin', 'supervisore', 'operatore'];

        foreach ($livelli as $livello) {
            if ($user->permissions->where('name', $livello)->first()) {
                return $this::labelLivelloOperatore($livello, $small);
            }

        }

    }


    public function labelLivelloOperatore($livello, $small = false)
    {
        if ($small) {
            $small = 'fs-8 px-4 py-3';
        } else {
            $small = '';
        }
        switch ($livello) {
            case 'admin':
                return '<span class="badge badge-info fw-bolder ' . $small . '">Admin</span>';

            case 'agente':
                return '<span class="badge badge-primary fw-bolder ' . $small . '">Agente</span>';

            case 'supervisore':
                return '<span class="badge badge-warning fw-bolder ' . $small . '">Supervisore</span>';

            case 'operatore':
                return '<span class="badge badge-warning fw-bolder ' . $small . '">Operatore</span>';

            case 'sospeso':
                return '<span class="badge badge-danger fw-bolder ' . $small . '">Sospeso</span>';

        }
    }


}
