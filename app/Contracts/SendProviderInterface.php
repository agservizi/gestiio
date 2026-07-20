<?php

namespace App\Contracts;

use App\Models\SendRequest;

interface SendProviderInterface
{
    public function isConfigured(): bool;

    /**
     * Placeholder for future authorized SEND integration.
     * Manual provider does not call external services.
     */
    public function processRequest(SendRequest $request): SendProviderResult;
}
