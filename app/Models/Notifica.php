<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifica extends Model
{
    use HasFactory;

    protected $table = 'notifiche';

    public const NOME_SINGOLARE = 'notifica';

    public const NOME_PLURALE = 'notifiche';

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function letture()
    {
        return $this->hasMany(NotificaLettura::class, 'notifica_id', 'id');
    }

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public static function notificaAdAdmin($titolo, $testo, $tipo = 'info')
    {
        $notifica = new Notifica;
        $notifica->titolo = $titolo;
        $notifica->destinatario = 'admin';
        $notifica->testo = $testo;
        $notifica->tipo = $tipo;
        $notifica->save();

    }

    public static function notificaAdAgente(User $agente, $titolo, $testo, $tipo = 'info')
    {
        $notifica = new Notifica;
        $notifica->titolo = $titolo;
        $notifica->destinatario = 'agente';
        $notifica->user_id = $agente->id;
        $notifica->testo = $testo;
        $notifica->tipo = $tipo;
        $notifica->save();
    }
}
