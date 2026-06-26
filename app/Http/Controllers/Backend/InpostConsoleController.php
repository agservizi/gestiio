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
        $response = $this->callSafely(fn () => app(InpostService::class)->account());

        return view('Backend.InpostConsole.account', [
            'titoloPagina' => 'Account InPost',
            'response' => $response,
            'config' => $this->configSnapshot(),
        ]);
    }

    public function deposits(Request $request)
    {
        $response = $this->callSafely(fn () => app(InpostService::class)->deposits($request->only(['page', 'per_page', 'status'])));

        return view('Backend.InpostConsole.deposits', [
            'titoloPagina' => 'Deposits InPost',
            'response' => $response,
            'payload' => old('payload', "{}"),
            'config' => $this->configSnapshot(),
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

        $response = $this->callSafely(fn () => app(InpostService::class)->createDeposit($payload));

        return redirect()
            ->action([self::class, 'deposits'])
            ->with('deposit_response', $response)
            ->withInput(['payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]);
    }

    protected function callSafely(callable $callback): array
    {
        try {
            $response = $callback();

            return [
                'ok' => ! data_get($response, 'error') && (int) data_get($response, '_http_status', 200) < 400,
                'data' => $response,
                'message' => data_get($response, 'message'),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Console InPost: chiamata non riuscita', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'data' => [],
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function configSnapshot(): array
    {
        return [
            'base_url' => config('services.inpost.base_url'),
            'organization_id' => config('services.inpost.organization_id') ? 'configurato' : 'mancante',
            'client_id' => config('services.inpost.client_id') ? 'configurato' : 'mancante',
            'account_endpoint' => config('services.inpost.account_endpoint'),
            'deposits_endpoint' => config('services.inpost.deposits_endpoint'),
        ];
    }
}
