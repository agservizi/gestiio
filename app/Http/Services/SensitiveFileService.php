<?php

namespace App\Http\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SensitiveFileService
{
    public const PREFIX = 'sensitive://';

    public function store(UploadedFile $file, string $folder, array $context = []): array
    {
        $this->validate($file);

        $extension = $this->extension($file);
        $fileName = (string) Str::ulid().'.'.$extension;
        $folder = trim($folder, '/');
        $relativePath = $folder.'/'.$fileName;
        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw ValidationException::withMessages(['file' => 'File non leggibile.']);
        }

        Storage::disk($this->disk())->put($relativePath, $content);

        $payload = [
            'path' => self::PREFIX.$relativePath,
            'relative_path' => $relativePath,
            'filename' => $fileName,
            'original_name' => $this->safeOriginalName($file),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'sha256' => hash('sha256', $content),
            'base64' => config('security_files.store_base64_copy') ? base64_encode($content) : null,
        ];

        $this->audit('upload', $payload['path'], array_merge($context, [
            'original_name' => $payload['original_name'],
            'mime_type' => $payload['mime_type'],
            'size' => $payload['size'],
            'sha256' => $payload['sha256'],
        ]));

        return $payload;
    }

    public function validate(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'Upload non valido.']);
        }

        $maxBytes = ((int) config('security_files.max_kb', 51200)) * 1024;
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages(['file' => 'File troppo grande.']);
        }

        $extension = $this->extension($file);
        $allowed = array_map('strtolower', config('security_files.allowed_extensions', []));
        $blocked = array_map('strtolower', config('security_files.blocked_extensions', []));

        if (in_array($extension, $blocked, true) || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages(['file' => 'Tipo file non consentito.']);
        }

        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 4096);
        $normalizedHead = Str::lower($head);
        foreach ((array) config('security_files.blocked_signatures', []) as $signature) {
            $signature = (string) $signature;
            if ($signature === '') {
                continue;
            }

            if ($signature === 'MZ' && str_starts_with($head, 'MZ')) {
                throw ValidationException::withMessages(['file' => 'File bloccato per motivi di sicurezza.']);
            }

            if ($signature !== 'MZ' && Str::contains($normalizedHead, Str::lower($signature))) {
                throw ValidationException::withMessages(['file' => 'File bloccato per motivi di sicurezza.']);
            }
        }
    }

    public function exists(?string $path): bool
    {
        $resolved = $this->resolvePath($path);

        return Storage::disk($resolved['disk'])->exists($resolved['path']);
    }

    public function absolutePath(string $path): string
    {
        $resolved = $this->resolvePath($path);

        return Storage::disk($resolved['disk'])->path($resolved['path']);
    }

    public function mimeType(string $path): string
    {
        $resolved = $this->resolvePath($path);

        return Storage::disk($resolved['disk'])->mimeType($resolved['path']) ?: 'application/octet-stream';
    }

    public function delete(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }

        $resolved = $this->resolvePath($path);

        return Storage::disk($resolved['disk'])->delete($resolved['path']);
    }

    public function download(string $path, string $downloadName, array $context = []): BinaryFileResponse
    {
        abort_unless($this->exists($path), 404, 'Allegato non disponibile');

        $this->audit('download', $path, array_merge($context, ['filename' => $downloadName]));

        return response()->download($this->absolutePath($path), $downloadName, $this->secureHeaders());
    }

    public function inline(string $path, string $downloadName, array $context = []): BinaryFileResponse
    {
        abort_unless($this->exists($path), 404, 'Allegato non disponibile');

        $this->audit('preview', $path, array_merge($context, ['filename' => $downloadName]));

        return response()->file($this->absolutePath($path), array_merge($this->secureHeaders(), [
            'Content-Type' => $this->mimeType($path),
            'Content-Disposition' => 'inline; filename="'.addslashes($downloadName).'"',
        ]));
    }

    public function resolvePath(?string $path): array
    {
        $path = trim((string) $path);
        abort_if($path === '', 404, 'Allegato non disponibile');

        if (Str::startsWith($path, self::PREFIX)) {
            return [
                'disk' => $this->disk(),
                'path' => Str::after($path, self::PREFIX),
            ];
        }

        return [
            'disk' => config('filesystems.default', 'public'),
            'path' => ltrim($path, '/'),
        ];
    }

    public function secureHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'; sandbox",
        ];
    }

    protected function disk(): string
    {
        return (string) config('security_files.disk', 'sensitive');
    }

    protected function extension(UploadedFile $file): string
    {
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());
        $allowed = array_map('strtolower', config('security_files.allowed_extensions', []));
        $blocked = array_map('strtolower', config('security_files.blocked_extensions', []));
        $extension = in_array($clientExtension, $allowed, true) && ! in_array($clientExtension, $blocked, true)
            ? $clientExtension
            : strtolower($file->guessExtension() ?: $file->extension() ?: $clientExtension);

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    protected function safeOriginalName(UploadedFile $file): string
    {
        $name = trim((string) $file->getClientOriginalName());

        return Str::limit(str_replace(["\r", "\n", "\0"], '', $name), 180, '');
    }

    protected function audit(string $action, string $path, array $meta = []): void
    {
        Log::info('Sensitive file '.$action, [
            'user_id' => Auth::id(),
            'path' => $path,
            'meta' => $meta,
        ]);
    }
}
