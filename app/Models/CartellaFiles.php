<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class CartellaFiles extends Model
{
    use NodeTrait;

    protected $table = 'files_cartelle';

    protected $casts = [
        'visibilita_ruoli' => 'array',
    ];

    public const NOME_SINGOLARE = 'cartella';

    public const NOME_PLURALE = 'cartelle';

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */
    public function files()
    {
        return $this->hasMany(File::class, 'cartella_id');
    }

    public function shareLinks()
    {
        return $this->hasMany(FileShareLink::class, 'cartella_id');
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
}
