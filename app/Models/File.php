<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $table = 'files';

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

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            $estensione = strtolower(pathinfo($model->filename_originale, PATHINFO_EXTENSION));
            $model->tipo_file = self::tipoFile($estensione);
        });

        static::deleting(function ($model) {
            if (!Str::of($model->path_filename)->is('test*')) {
                \Storage::delete($model->path_filename);
            }
        });
    }

    public static function perBlade($uid, $id)
    {
        $qb = self::where('uid', $uid);
        if ($id) {
            $qb->orWhere('contratto_id', $id);
        }
        return $qb->get()->toArray();
    }

    public function urlFile()
    {
        return '/storage' . $this->path_filename;
    }

    protected static function tipoFile($estensione)
    {

        if (in_array($estensione, ['3ds', 'aac', 'ai', 'avi', 'bmp', 'cad', 'cdr', 'css', 'dat', 'dll', 'dmg', 'doc', 'eps', 'fla', 'flv', 'gif', 'html', 'indd', 'iso', 'jpg', 'js', 'midi', 'mov', 'mp3', 'mpg', 'pdf', 'php', 'png', 'ppt', 'ps', 'psd', 'raw', 'sql', 'svg', 'tif', 'txt', 'wmv', 'xls', 'xml', 'zip'])) {
            return $estensione;
        }

        switch ($estensione) {
            case 'jpeg':
            case 'jpg':
                return 'jpg';

            case 'rtf':
            case 'docx':
                return 'doc';

            case 'rar':
                return 'zip';



            case 'xlsx':
                return 'xls';

            default:
                return 'sconosciuto';
        }

    }

}
