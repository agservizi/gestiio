<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FornitoriLuceProvider
{
    private const CACHE_KEY = 'fornitori_luce_italia_from_gme';

    private const CACHE_TTL_SECONDS = 86400;

    private const SOURCE_URL = 'https://www.mercatoelettrico.org/it-it/Home/Accesso-ai-Mercati/Elettricita/PiattaformaContiEnergia/ElencoOperatoriPCE';

    public static function list(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (self::isValidList($cached)) {
            return $cached;
        }

        Cache::forget(self::CACHE_KEY);

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            $fornitori = self::fetchFromGme();
            if (self::isValidList($fornitori)) {
                return $fornitori;
            }

            return config('fornitori_luce_italia', []);
        });
    }

    private static function fetchFromGme(): array
    {
        try {
            $response = Http::timeout(15)
                ->retry(2, 300)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; GestiioBot/1.0)',
                    'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
                ])
                ->get(self::SOURCE_URL);

            if (! $response->ok()) {
                return [];
            }

            return self::parseOperatorNames((string) $response->body());
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function parseOperatorNames(string $html): array
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = preg_split('/\R/u', $text) ?: [];
        $names = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line ?? ''));
            if ($line === '' || mb_strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3) {
                continue;
            }

            $name = self::sanitizeName($parts[0] ?? '');
            if ($name === '' || mb_stripos($name, 'Numero di Operatori') !== false) {
                continue;
            }

            $names[$name] = $name;
        }

        ksort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    private static function sanitizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        // Esclude righe html/js o testo non fornitore.
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            return '';
        }
        if (preg_match('/[{}<>]/u', $name)) {
            return '';
        }
        if (preg_match('/\b(var|window|function|script|_paq)\b/i', $name)) {
            return '';
        }
        if (preg_match('/(Socio unico|GSE|D\.Lgs|cookie|privacy)/iu', $name)) {
            return '';
        }

        return $name;
    }

    private static function isValidList($items): bool
    {
        if (! is_array($items) || count($items) < 10) {
            return false;
        }

        foreach ($items as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                return false;
            }
            if (self::sanitizeName($value) === '') {
                return false;
            }
        }

        return true;
    }
}
