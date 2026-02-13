<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Notifica extends Model
{
    use HasFactory;

    protected $table = "notifiche";

    public const NOME_SINGOLARE = "notifica";
    public const NOME_PLURALE = "notifiche";

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function letture()
    {
        return $this->hasMany(NotificaLettura::class, 'notifica_id', 'id');
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
        $notifica = new Notifica();
        $notifica->titolo = $titolo;
        $notifica->destinatario = 'admin';
        $notifica->testo = $testo;
        $notifica->save();

    }
}
