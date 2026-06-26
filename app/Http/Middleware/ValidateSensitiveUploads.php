<?php

namespace App\Http\Middleware;

use App\Http\Services\SensitiveFileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ValidateSensitiveUploads
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->files->count() > 0) {
            $this->validateFiles($request->allFiles());
        }

        return $next($request);
    }

    protected function validateFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->validateFiles($file);
                continue;
            }

            if ($file instanceof UploadedFile) {
                app(SensitiveFileService::class)->validate($file);
            }
        }
    }
}
