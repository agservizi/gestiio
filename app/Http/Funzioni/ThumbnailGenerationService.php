<?php

namespace App\Http\Funzioni;

use Illuminate\Support\Facades\Storage;
use Image;
use Imagick;

class ThumbnailGenerationService
{
    public function generate($pdfPath, string $tipoFile, $width, $height): string|null
    {

        if (!in_array($tipoFile, ['pdf', 'immagine'])) {
            return null;
        }

        $hashName = pathinfo($pdfPath, PATHINFO_FILENAME);
        $cartellaThumbnails = '/thumbnails/';
        \File::ensureDirectoryExists(Storage::disk('public')->path($cartellaThumbnails));

        switch ($tipoFile) {
            case 'pdf':
                return $this->pdf($pdfPath, $hashName, $cartellaThumbnails);

            case 'immagine':
                return $this->immagine($pdfPath, $hashName, $cartellaThumbnails, $width, $height);

        }

        return null;
    }


    private function pdf($pdfPath, string $hashName, $cartellaThumbnails)
    {
        $thumbnailPath = null;
        try {
            $thumbnailPath = $cartellaThumbnails . $hashName . '.jpg';

            $source = Storage::disk('public')->path($pdfPath);
            $target = Storage::disk('public')->path($thumbnailPath);

            $image = new Imagick();
            $image->setResolution(160, 160); //180
            $image->readImage($source . "[0]");
            $image->setImageFormat('jpeg');
            $image->setImageCompression(imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality(10);
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(11);
            $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->writeimage($target);
            $image->clear();
            $image->destroy();
        } catch (\Exception $exception) {
            \Log::alert('Errore in ThumbnailGenerationService');
        }

        return $thumbnailPath;

    }

    private function immagine($path, string $hashName, $cartellaThumbnails, $width, $height)
    {
        $thumbnailPath = $cartellaThumbnails . $hashName . '.jpg';
        $source = Storage::disk('public')->path($path);
        $target = Storage::disk('public')->path($thumbnailPath);

        $img = Image::make($source)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($target);
        return $thumbnailPath;

    }

}
