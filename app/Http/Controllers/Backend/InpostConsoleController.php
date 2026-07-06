<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InpostConsoleController extends Controller
{
    public function account()
    {
        $service = app(InpostService::class);
        $response = $this->callSafely($service, fn () => $service->account());

        return view('Backend.InpostConsole.account', [
            'titoloPagina' => 'Account InPost',
            'response' => $response,
            'config' => $this->configSnapshot($service),
        ]);
    }

    public function deposits(Request $request)
    {
        $service = app(InpostService::class);
        $response = $this->callSafely($service, fn () => $service->deposits($request->only(['page', 'per_page', 'status'])));

        return view('Backend.InpostConsole.deposits', [
            'titoloPagina' => 'Deposits InPost',
            'response' => $response,
            'payload' => old('payload', "{}"),
            'config' => $this->configSnapshot($service),
        ]);
    }

    public function storeDeposit(Request $request)
    {
        $request->validate([
            'payload' => ['required', 'string'],
        ]);

        $payload = json_decode($request->input('payload'), true);
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['payload' => 'Payload JSON non valido.']);
        }

        $service = app(InpostService::class);
        $response = $this->callSafely($service, fn () => $service->createDeposit($payload));

        return redirect()
            ->action([self::class, 'deposits'])
            ->with('deposit_response', $response)
            ->withInput(['payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]);
    }

    protected function callSafely(InpostService $service, callable $callback): array
    {
        if (! $service->isConfigured()) {
            return [
                'ok' => false,
                'data' => [],
                'message' => 'Configurazione InPost incompleta: '.implode(', ', $service->missingConfiguration()),
                'missing_config' => $service->missingConfiguration(),
            ];
        }

        $capabilityMessage = $this->missingCapabilityMessage();
        if ($capabilityMessage !== null) {
            return [
                'ok' => false,
                'data' => [],
                'message' => $capabilityMessage,
                'missing_capability' => true,
            ];
        }

        try {
            $response = $callback();

            return [
                'ok' => ! data_get($response, 'error') && (int) data_get($response, '_http_status', 200) < 400,
                'data' => $response,
                'message' => data_get($response, 'message'),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Console InPost: chiamata remota non riuscita', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'data' => [],
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function configSnapshot(InpostService $service): array
    {
        return [
            'base_url' => config('services.inpost.base_url'),
            'organization_id' => config('services.inpost.organization_id') ? 'configurato' : 'mancante',
            'client_id' => config('services.inpost.client_id') ? 'configurato' : 'mancante',
            'client_secret' => config('services.inpost.client_secret') ? 'configurato' : 'mancante',
            'scope' => config('services.inpost.scope'),
            'ready' => $service->isConfigured() ? 'si' : 'no',
            'missing' => $service->missingConfiguration(),
            'account_endpoint' => config('services.inpost.account_endpoint'),
            'deposits_endpoint' => config('services.inpost.deposits_endpoint'),
        ];
    }

    protected function missingCapabilityMessage(): ?string
    {
        $route = request()->route()?->uri();
        $scope = (string) config('services.inpost.scope', '');

        if ($route === 'backend/inpost-account' && ! $this->scopeContainsAny($scope, ['account', 'organization'])) {
            return 'Credenziali InPost valide, ma API Account non abilitata sul client corrente. Scope disponibili: '.$scope;
        }

        if (str_starts_with((string) $route, 'backend/inpost-deposits') && ! $this->scopeContainsAny($scope, ['deposit'])) {
            return 'Credenziali InPost valide, ma API Deposits non abilitata sul client corrente. Scope disponibili: '.$scope;
        }

        return null;
    }

    protected function scopeContainsAny(string $scope, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($scope, $needle)) {
                return true;
            }
        }

        return false;
    }
}
