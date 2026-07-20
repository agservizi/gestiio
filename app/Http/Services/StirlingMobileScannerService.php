<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StirlingMobileScannerService
{
    private function base(): string
    {
        return rtrim((string) config('services.stirling.url', 'http://stirling-pdf:8080'), '/').'/pdf-tools';
    }

    private function client()
    {
        return Http::timeout((int) config('services.stirling.timeout', 300))
            ->connectTimeout(10)
            ->acceptJson();
    }

    public function publicScanUrl(string $sessionId): string
    {
        $public = rtrim((string) config('services.stirling.public_url', ''), '/');
        if ($public === '') {
            $public = rtrim((string) config('app.url'), '/').'/pdf-tools';
        }

        return $public.'/mobile-scanner?session='.urlencode($sessionId);
    }

    public function createSession(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?: (string) Str::uuid();
        $response = $this->client()->post($this->base().'/api/v1/mobile-scanner/create-session/'.$sessionId);

        if (! $response->successful()) {
            throw new RuntimeException('Impossibile creare la sessione scanner: HTTP '.$response->status());
        }

        $data = $response->json() ?: [];
        $data['sessionId'] = $data['sessionId'] ?? $sessionId;
        $data['scanUrl'] = $this->publicScanUrl($data['sessionId']);

        return $data;
    }

    public function listFiles(string $sessionId): array
    {
        $response = $this->client()->get($this->base().'/api/v1/mobile-scanner/files/'.$sessionId);
        if (! $response->successful()) {
            throw new RuntimeException('Impossibile leggere i file scanner: HTTP '.$response->status());
        }

        $data = $response->json() ?: [];

        return [
            'sessionId' => $data['sessionId'] ?? $sessionId,
            'count' => (int) ($data['count'] ?? 0),
            'files' => array_values(array_map(function ($file) {
                return [
                    'filename' => (string) ($file['filename'] ?? $file['name'] ?? 'scan.bin'),
                    'size' => (int) ($file['size'] ?? 0),
                    'contentType' => (string) ($file['contentType'] ?? 'application/octet-stream'),
                ];
            }, $data['files'] ?? [])),
        ];
    }

    public function downloadToTemp(string $sessionId, string $filename): string
    {
        $safe = $this->safeFilename($filename);
        $dir = 'tmp/mobile-scan/'.$sessionId;
        Storage::disk('local')->makeDirectory($dir);
        $relative = $dir.'/'.$safe;
        $absolute = Storage::disk('local')->path($relative);

        if (is_file($absolute) && filesize($absolute) > 0) {
            return $relative;
        }

        $response = $this->client()
            ->withOptions(['stream' => false])
            ->get($this->base().'/api/v1/mobile-scanner/download/'.$sessionId.'/'.rawurlencode($filename));

        if (! $response->successful()) {
            throw new RuntimeException('Download scanner fallito: HTTP '.$response->status());
        }

        Storage::disk('local')->put($relative, $response->body());

        return $relative;
    }

    public function deleteRemoteSession(string $sessionId): void
    {
        try {
            $this->client()->delete($this->base().'/api/v1/mobile-scanner/session/'.$sessionId);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function deleteSession(string $sessionId): void
    {
        $this->deleteRemoteSession($sessionId);
        Storage::disk('local')->deleteDirectory('tmp/mobile-scan/'.$sessionId);
    }

    public function safeFilename(string $filename): string
    {
        $base = basename(str_replace(['\\', "\0"], '', $filename));
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?: 'scan.bin';

        return Str::limit($base, 180, '');
    }
}
