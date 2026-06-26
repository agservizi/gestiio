<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\AiAutomationService;
use Illuminate\Http\Request;

class N8nAiWebhookController extends Controller
{
    public function handle(Request $request, AiAutomationService $automation)
    {
        $expectedSecret = (string) config('services.n8n.callback_secret');
        if ($expectedSecret !== '') {
            abort_if(! hash_equals($expectedSecret, (string) $request->header('X-Gestiio-AI-Secret')), 403);
        }

        $suggestion = $automation->createSuggestionFromWebhook($request->all());

        return response()->json([
            'success' => true,
            'suggestion_id' => $suggestion->id,
        ]);
    }
}
