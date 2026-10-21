<?php

namespace App\Http\Services;

use App\Models\AppVersionRelease;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AppVersionService
{
    public function current(): string
    {
        return (string) config('configurazione.versione', '5.1');
    }

    public function ensureCurrentRegistered(?string $title = null, ?string $notes = null): ?AppVersionRelease
    {
        if (! Schema::hasTable('app_version_releases')) {
            return null;
        }

        $version = $this->normalize($this->current());
        $existing = AppVersionRelease::query()->where('version', $version)->first();
        if ($existing) {
            return $existing;
        }

        return AppVersionRelease::query()->create([
            'version' => $version,
            'title' => $title ?: ('Gestiio '.$version),
            'notes' => $notes,
            'is_major' => $this->looksMajor($version),
            'released_at' => now(),
        ]);
    }

    /**
     * Aggiorna config/configurazione.php e registra la release in DB.
     */
    public function release(
        string $version,
        ?string $title = null,
        ?string $notes = null,
        bool $isMajor = false,
        ?int $userId = null
    ): AppVersionRelease {
        $version = $this->normalize($version);
        $current = $this->normalize($this->current());
        if ($current !== $version) {
            $this->writeConfigVersion($version);
            config(['configurazione.versione' => $version]);
        }

        return AppVersionRelease::query()->updateOrCreate(
            ['version' => $version],
            [
                'title' => $title ?: ('Gestiio '.$version),
                'notes' => $notes,
                'is_major' => $isMajor || $this->looksMajor($version),
                'user_id' => $userId,
                'released_at' => now(),
            ]
        );
    }

    public function writeConfigVersion(string $version): void
    {
        $path = config_path('configurazione.php');
        $contents = File::get($path);
        $version = $this->normalize($version);

        if (preg_match("/'versione'\\s*=>\\s*'".preg_quote($version, '/')."'/", $contents)) {
            return;
        }

        $updated = preg_replace(
            "/('versione'\\s*=>\\s*)'[^']*'/",
            '$1\''.$version.'\'',
            $contents,
            1
        );

        if (! is_string($updated) || $updated === $contents) {
            throw new \RuntimeException('Impossibile aggiornare configurazione.versione in '.$path);
        }

        File::put($path, $updated);
    }

    public function normalize(string $version): string
    {
        $version = trim($version);
        $version = ltrim($version, 'vV');

        return $version;
    }

    public function looksMajor(string $version): bool
    {
        return (bool) preg_match('/^\d+\.0(\.0)?$/', $this->normalize($version));
    }
}
