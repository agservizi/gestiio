<?php

namespace App\Models;

use App\Http\Funzioni\FunzioniAllegato;
use App\Http\Funzioni\ThumbnailGenerationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllegatoServizio extends Model
{
    use FunzioniAllegato;

    protected $table = 'allegati_tutti_servizi';

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

            $thumbnailGenerationService = new ThumbnailGenerationService();
            $thumbnailPath = $thumbnailGenerationService->generate($model->path_filename, $model->tipo_file, 500, 500);
            $model->thumbnail = $thumbnailPath;

        });

        static::deleting(function ($model) {
            \Storage::delete($model->path_filename);
            \Log::debug('deleting;');
            if ($model->thumbnail) {
                \Storage::delete($model->thumbnail);
            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */
    public function servizio()
    {
        return $this->morphTo();
    }


    public static function perBlade($uid, $allegatoServizioId, $allegatoServizioType, $perCliente = 0)
    {

        $allegatoServizioType = str_replace('_', '\\', $allegatoServizioType);
        $qb = self::where(function ($q) use ($uid, $allegatoServizioId, $allegatoServizioType) {
            if ($allegatoServizioId) {
                $q->where(function ($q) use ($allegatoServizioId, $allegatoServizioType) {
                    $q->where('allegato_id', $allegatoServizioId)->where('allegato_type', $allegatoServizioType);
                });
            } else {
                $q->where('uid', $uid);
            }

        });

        $qb->where('per_cliente', $perCliente);

        return $qb->get(['id', 'path_filename', 'dimensione_file', 'thumbnail'])->toArray();
    }
}
