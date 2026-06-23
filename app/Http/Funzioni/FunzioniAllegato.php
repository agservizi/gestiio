<?php

namespace App\Http\Funzioni;

use Illuminate\Support\Facades\Storage;

trait FunzioniAllegato
{
    public function urlFile()
    {
        return '/storage'.$this->path_filename;
    }

    public function urlThumbnail(): ?string
    {
        if ($this->thumbnail && Storage::disk('public')->exists(ltrim($this->thumbnail, '/'))) {
            return '/storage'.$this->thumbnail;
        }

        if ($this->tipo_file === 'immagine' && $this->path_filename && Storage::disk('public')->exists(ltrim($this->path_filename, '/'))) {
            return '/storage'.$this->path_filename;
        }

        return null;
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
