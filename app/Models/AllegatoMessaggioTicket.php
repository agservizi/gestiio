<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllegatoMessaggioTicket extends Model
{
    use HasFactory;

    protected $table = 'tickets_allegati';


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
            \Storage::delete($model->path_filename);
            \Log::debug('deleting;');
        });
    }

    public static function perBlade($uid, $id)
    {
        $qb = self::where('uid', $uid);
        if ($id) {
            $qb->orWhere('messaggio_id', $id);
        }
        return $qb->get()->toArray();
    }

    public function urlFile()
    {
        return '/storage' . $this->path_filename;
    }

    protected static function tipoFile($estensione)
    {

        switch ($estensione) {
            case 'png':
            case 'jpeg':
            case 'jpg':
                return 'immagine';

            case 'pdf':
                return 'pdf';

            default:
                return $estensione;
        }

    }


}
