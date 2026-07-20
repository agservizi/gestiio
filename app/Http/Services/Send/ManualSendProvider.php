<?php

namespace App\Http\Services\Send;

use App\Contracts\SendProviderInterface;
use App\Contracts\SendProviderResult;
use App\Models\SendRequest;

/**
 * Il Supervisore lavora la pratica con gli strumenti ufficiali
 * per cui è autorizzato. Nessuna integrazione automatica SEND.
 */
class ManualSendProvider implements SendProviderInterface
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function processRequest(SendRequest $request): SendProviderResult
    {
        return SendProviderResult::manual();
    }
}
