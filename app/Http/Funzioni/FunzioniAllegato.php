<?php

namespace App\Http\Funzioni;

trait FunzioniAllegato
{
    public function urlFile()
    {
        return '/storage' . $this->path_filename;
    }

    public function urlThumbnail(): string|null
    {
        return $this->thumbnail ? '/storage' . $this->thumbnail : null;
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
