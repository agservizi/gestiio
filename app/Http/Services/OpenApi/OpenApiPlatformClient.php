<?php

namespace App\Http\Services\OpenApi;

use App\Models\Agente;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Openapi\OauthClient;
use Throwable;

/**
 * Wallet OAuth Openapi.
 * - Credenziali .env: account piattaforma (sync catalogo / banner admin).
 * - Credenziali su Agente: wallet per-agente sulle create Visengine.
 * Bearer Visengine restano su Agente::openapi_visure_token / openapi_catasto_token.
 */
class OpenApiPlatformClient
{
    public const CACHE_KEY_CREDIT = 'openapi.platform.wallet_credit';

    public const CACHE_TTL_SECONDS = 60;

    public function hasCredentials(): bool
    {
        return filled($this->username()) && filled($this->apiKey());
    }

    public function username(): ?string
    {
        $value = trim((string) (
            config('services.openapi.username')
            ?: config('services.openapi.email')
            ?: ''
        ));

        return $value !== '' ? $value : null;
    }

    public function apiKey(): ?string
    {
        $value = trim((string) (
            config('services.openapi.api_key')
            ?: config('services.openapi.key')
            ?: ''
        ));

        return $value !== '' ? $value : null;
    }

    public function isSandbox(): bool
    {
        return (bool) config('services.openapi.sandbox', false);
    }

    public function oauthBaseUrl(): string
    {
        $configured = trim((string) config('services.openapi.oauth_base_url', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return $this->isSandbox()
            ? 'https://test.oauth.openapi.com'
            : 'https://oauth.openapi.com';
    }

    /**
     * @return OauthClient
     */
    public function oauthClient(): OauthClient
    {
        putenv('OPENAPI_OAUTH_URL='.$this->oauthBaseUrl());
        putenv('OPENAPI_OAUTH_TEST_URL='.$this->oauthBaseUrl());

        return new OauthClient($this->username() ?? '', $this->apiKey() ?? '', $this->isSandbox());
    }

    public function agenteHasOpenApiAccountCredentials(?Agente $agente): bool
    {
        if (! $agente) {
            return false;
        }

        return filled(trim((string) $agente->openapi_email))
            && filled(trim((string) $agente->openapi_api_key));
    }

    public function agentCacheKey(int $agenteId): string
    {
        return 'openapi.agent.'.$agenteId.'.wallet_credit';
    }

    /**
     * Saldo wallet Openapi piattaforma in EUR (null se non disponibile).
     */
    public function getWalletCredit(bool $fresh = false): ?float
    {
        if (! $this->hasCredentials()) {
            return null;
        }

        return $this->fetchWalletCredit(
            (string) $this->username(),
            (string) $this->apiKey(),
            self::CACHE_KEY_CREDIT,
            $fresh,
            ['scope' => 'platform']
        );
    }

    public function getWalletCreditForAgente(Agente $agente, bool $fresh = false): ?float
    {
        if (! $this->agenteHasOpenApiAccountCredentials($agente)) {
            return null;
        }

        return $this->fetchWalletCredit(
            trim((string) $agente->openapi_email),
            trim((string) $agente->openapi_api_key),
            $this->agentCacheKey((int) $agente->id),
            $fresh,
            ['scope' => 'agent', 'agente_id' => $agente->id]
        );
    }

    public function syncWalletCredit(): ?float
    {
        return $this->getWalletCredit(true);
    }

    public function syncWalletCreditForAgente(Agente $agente): ?float
    {
        return $this->getWalletCreditForAgente($agente, true);
    }

    /**
     * Gate legacy piattaforma: usato solo dove esplicitamente richiesto (non sulle create agente).
     * Se le credenziali account non sono configurate, il gate è disattivo.
     */
    public function hasSufficientCredit(float $minimumEuro = 0.01): bool
    {
        if (! $this->hasCredentials()) {
            Log::debug('OpenAPI platform credentials missing; skipping platform credit gate');

            return true;
        }

        $credit = $this->getWalletCredit();
        if ($credit === null) {
            return false;
        }

        return $credit >= $minimumEuro;
    }

    public function hasSufficientCreditForAgente(Agente $agente, float $minimumEuro = 0.01): bool
    {
        if (! $this->agenteHasOpenApiAccountCredentials($agente)) {
            return false;
        }

        $credit = $this->getWalletCreditForAgente($agente);
        if ($credit === null) {
            return false;
        }

        return $credit >= $minimumEuro;
    }

    public function cachedCreditForDisplay(): ?float
    {
        if (Cache::has(self::CACHE_KEY_CREDIT)) {
            $cached = Cache::get(self::CACHE_KEY_CREDIT);
            if (is_numeric($cached)) {
                return (float) $cached;
            }
        }

        return $this->getWalletCredit();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function fetchWalletCredit(
        string $username,
        string $apiKey,
        string $cacheKey,
        bool $fresh,
        array $context = []
    ): ?float {
        if (! $fresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_numeric($cached)) {
                return (float) $cached;
            }
        }

        try {
            $response = Http::withBasicAuth($username, $apiKey)
                ->acceptJson()
                ->timeout(20)
                ->get($this->oauthBaseUrl().'/wallet');

            if (! $response->successful()) {
                Log::warning('OpenAPI wallet credit fetch failed', array_merge($context, [
                    'status' => $response->status(),
                ]));

                return null;
            }

            $payload = $response->json();
            $credit = $this->extractCreditAmount($payload);
            if ($credit === null) {
                Log::warning('OpenAPI wallet credit payload unrecognized', $context);

                return null;
            }

            Cache::put($cacheKey, $credit, self::CACHE_TTL_SECONDS);

            return $credit;
        } catch (Throwable $e) {
            Log::warning('OpenAPI wallet credit exception', array_merge($context, [
                'error' => $e->getMessage(),
            ]));

            return null;
        }
    }

    /**
     * @param  mixed  $payload
     */
    protected function extractCreditAmount($payload): ?float
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach (['credit', 'balance', 'saldo', 'wallet', 'amount', 'importo'] as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (float) $payload[$key];
            }
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $this->extractCreditAmount($payload['data']);
        }

        if (isset($payload['wallet']) && is_array($payload['wallet'])) {
            return $this->extractCreditAmount($payload['wallet']);
        }

        return null;
    }
}
