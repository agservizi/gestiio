<?php

namespace App\Console\Commands;

use App\Http\Services\OpenApi\OpenApiPlatformClient;
use App\Models\Agente;
use Illuminate\Console\Command;

class SyncOpenApiPlatformCredit extends Command
{
    protected $signature = 'visure:sync-openapi-credit
                            {--fresh : Ignora cache e richiama subito Openapi}
                            {--agente= : ID record agenti: sync wallet Openapi di quell’agente}';

    protected $description = 'Sincronizza il credito wallet Openapi (piattaforma o singolo agente)';

    public function handle(OpenApiPlatformClient $client): int
    {
        $agenteId = $this->option('agente');
        if ($agenteId !== null && $agenteId !== '') {
            return $this->syncAgente($client, (int) $agenteId);
        }

        if (! $client->hasCredentials()) {
            $this->warn('Credenziali OPENAPI_EMAIL/OPENAPI_USERNAME + OPENAPI_API_KEY/OPENAPI_KEY non configurate.');

            return self::FAILURE;
        }

        $credit = $this->option('fresh')
            ? $client->syncWalletCredit()
            : $client->getWalletCredit();

        if ($credit === null) {
            $this->error('Impossibile leggere il credito Openapi piattaforma.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Credito Openapi piattaforma: € %.2f (cache %ds) — solo monitoraggio admin, non usato sulle create agente',
            $credit,
            OpenApiPlatformClient::CACHE_TTL_SECONDS
        ));

        return self::SUCCESS;
    }

    protected function syncAgente(OpenApiPlatformClient $client, int $agenteId): int
    {
        $agente = Agente::query()->find($agenteId);
        if (! $agente) {
            $this->error("Agente #{$agenteId} non trovato.");

            return self::FAILURE;
        }

        if (! $client->agenteHasOpenApiAccountCredentials($agente)) {
            $this->warn("Agente #{$agenteId}: openapi_email / openapi_api_key non configurate.");

            return self::FAILURE;
        }

        $credit = $client->syncWalletCreditForAgente($agente);
        if ($credit === null) {
            $this->error("Impossibile leggere il credito Openapi dell’agente #{$agenteId}.");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Credito Openapi agente #%d: € %.2f (cache %ds)',
            $agenteId,
            $credit,
            OpenApiPlatformClient::CACHE_TTL_SECONDS
        ));

        return self::SUCCESS;
    }
}
