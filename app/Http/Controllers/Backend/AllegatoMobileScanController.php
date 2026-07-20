<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\StirlingMobileScannerService;
use App\Http\Support\LuggageQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AllegatoMobileScanController extends Controller
{
    private const CACHE_TTL = 600; // 10 minuti, allineato a Stirling

    public function __construct(private StirlingMobileScannerService $scanner)
    {
    }

    public function createSession(Request $request)
    {
        try {
            $created = $this->scanner->createSession();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Scanner telefono non disponibile. Riprova tra poco.',
            ], 502);
        }

        $sessionId = (string) $created['sessionId'];
        $this->bindSession($sessionId, (int) $request->user()->id);

        $scanUrl = (string) $created['scanUrl'];

        return response()->json([
            'success' => true,
            'sessionId' => $sessionId,
            'scanUrl' => $scanUrl,
            'qrSvg' => LuggageQrCode::svg($scanUrl, 220),
            'expiresAt' => $created['expiresAt'] ?? null,
            'timeoutMs' => $created['timeoutMs'] ?? (self::CACHE_TTL * 1000),
        ]);
    }

    public function status(Request $request, string $sessionId)
    {
        $this->assertOwned($request, $sessionId);

        $meta = Cache::get($this->cacheKey($sessionId), []);
        $materialized = is_array($meta) && ! empty($meta['materialized']);

        // Dopo il materialize Stirling distrugge la sessione: servi solo i file locali.
        if ($materialized) {
            $this->touchSession($sessionId, (int) $request->user()->id, $meta);

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'materialized' => true,
                'count' => count($meta['files'] ?? []),
                'files' => $this->filesPayload($sessionId, $meta['files'] ?? []),
            ]);
        }

        try {
            $listed = $this->scanner->listFiles($sessionId);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Impossibile aggiornare lo stato dello scanner.',
            ], 502);
        }

        // Solo metadata finché non ci sono file: NON scaricare (altrimenti Stirling invalida la sessione
        // e il telefono mostra "Sessione non trovata").
        if (($listed['count'] ?? 0) < 1) {
            $this->touchSession($sessionId, (int) $request->user()->id, $meta);

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'materialized' => false,
                'count' => 0,
                'files' => [],
            ]);
        }

        // File arrivati dal telefono: scarica TUTTI subito in temp, poi la sessione Stirling può chiudersi.
        $stored = [];
        foreach ($listed['files'] as $file) {
            $filename = $file['filename'];
            $fileId = $this->scanner->safeFilename($filename);
            try {
                $this->scanner->downloadToTemp($sessionId, $filename);
                $stored[] = [
                    'id' => $fileId,
                    'filename' => $filename,
                    'size' => (int) ($file['size'] ?? 0),
                    'contentType' => (string) ($file['contentType'] ?? 'application/octet-stream'),
                ];
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $meta['materialized'] = true;
        $meta['files'] = $stored;
        $meta['user_id'] = (int) $request->user()->id;
        $this->touchSession($sessionId, (int) $request->user()->id, $meta);

        // Cleanup sessione Stirling (già tipicamente invalidata dal download).
        try {
            $this->scanner->deleteRemoteSession($sessionId);
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'sessionId' => $sessionId,
            'materialized' => true,
            'count' => count($stored),
            'files' => $this->filesPayload($sessionId, $stored),
        ]);
    }

    public function download(Request $request, string $sessionId, string $fileId)
    {
        $this->assertOwned($request, $sessionId);

        $safe = $this->scanner->safeFilename($fileId);
        $relative = 'tmp/mobile-scan/'.$sessionId.'/'.$safe;

        abort_unless(Storage::disk('local')->exists($relative), 404, 'File non trovato');

        $absolute = Storage::disk('local')->path($relative);
        $mime = mime_content_type($absolute) ?: 'application/octet-stream';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$safe.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, string $sessionId)
    {
        $this->assertOwned($request, $sessionId);
        try {
            $this->scanner->deleteSession($sessionId);
        } catch (\Throwable $e) {
            Storage::disk('local')->deleteDirectory('tmp/mobile-scan/'.$sessionId);
        }
        Cache::forget($this->cacheKey($sessionId));

        return response()->json(['success' => true]);
    }

    private function filesPayload(string $sessionId, array $files): array
    {
        return array_values(array_map(function (array $file) use ($sessionId) {
            return [
                'id' => $file['id'],
                'filename' => $file['filename'],
                'size' => $file['size'],
                'contentType' => $file['contentType'],
                'previewUrl' => action([self::class, 'download'], [$sessionId, $file['id']]),
            ];
        }, $files));
    }

    private function bindSession(string $sessionId, int $userId): void
    {
        Cache::put($this->cacheKey($sessionId), [
            'user_id' => $userId,
            'created_at' => now()->timestamp,
            'materialized' => false,
            'files' => [],
        ], self::CACHE_TTL);
    }

    private function touchSession(string $sessionId, int $userId, ?array $meta = null): void
    {
        $meta = is_array($meta) ? $meta : (Cache::get($this->cacheKey($sessionId)) ?: []);
        if (! is_array($meta) || empty($meta['user_id'])) {
            $this->bindSession($sessionId, $userId);

            return;
        }
        $meta['user_id'] = $userId;
        Cache::put($this->cacheKey($sessionId), $meta, self::CACHE_TTL);
    }

    private function assertOwned(Request $request, string $sessionId): void
    {
        abort_unless(preg_match('/^[a-f0-9-]{36}$/i', $sessionId), 404);

        $meta = Cache::get($this->cacheKey($sessionId));
        abort_unless(is_array($meta) && (int) ($meta['user_id'] ?? 0) === (int) $request->user()->id, 403);
    }

    private function cacheKey(string $sessionId): string
    {
        return 'allegato-mobile-scan:'.$sessionId;
    }
}
