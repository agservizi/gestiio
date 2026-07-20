<?php

namespace App\Http\Services;

use App\Enums\SendApplicantType;
use App\Models\SendRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SendChecklistService
{
    public const LABELS = [
        'avviso_send' => 'Avviso SEND presente',
        'documento_destinatario' => 'Documento d\'identità del destinatario',
        'cf_destinatario' => 'Codice fiscale / tessera sanitaria del destinatario',
        'delega' => 'Delega presente',
        'documento_delegato' => 'Documento d\'identità del delegato',
        'cf_delegato' => 'Codice fiscale del delegato',
        'documento_rappresentante' => 'Documento d\'identità del rappresentante',
        'cf_rappresentante' => 'Codice fiscale del rappresentante',
        'dati_impresa' => 'Partita IVA / CF impresa e dati anagrafici',
        'poteri_rappresentanza' => 'Documentazione o autocertificazione poteri di rappresentanza',
        'identita_verificata' => 'Identità del richiedente verificata',
        'privacy' => 'Informativa privacy presa visione',
    ];

    public function syncForRequest(SendRequest $request): void
    {
        $type = $request->applicant_type instanceof SendApplicantType
            ? $request->applicant_type
            : SendApplicantType::from((string) $request->applicant_type);

        $required = $type->requiredChecklistCodes();
        $allCodes = array_unique(array_merge($required, ['identita_verificata', 'privacy']));

        $existing = $request->checklistItems()->get()->keyBy('code');

        foreach ($allCodes as $code) {
            if ($existing->has($code)) {
                $existing[$code]->update([
                    'label' => self::LABELS[$code] ?? $code,
                    'required' => in_array($code, $required, true) || in_array($code, ['identita_verificata', 'privacy'], true),
                ]);
                continue;
            }

            $request->checklistItems()->create([
                'code' => $code,
                'label' => self::LABELS[$code] ?? $code,
                'required' => in_array($code, $required, true) || in_array($code, ['identita_verificata', 'privacy'], true),
                'completed' => false,
            ]);
        }

        // Rimuovi voci non più applicabili (non completate)
        $request->checklistItems()
            ->whereNotIn('code', $allCodes)
            ->where('completed', false)
            ->delete();
    }

    public function markCompleted(SendRequest $request, array $codes, ?User $user = null): void
    {
        $user = $user ?: Auth::user();
        foreach ($codes as $code) {
            $item = $request->checklistItems()->where('code', $code)->first();
            if (! $item) {
                continue;
            }
            $item->update([
                'completed' => true,
                'completed_by' => $user?->id,
                'completed_at' => now(),
            ]);
        }
    }

    public function updateFromInput(SendRequest $request, array $completedCodes): void
    {
        $request->loadMissing('checklistItems');
        foreach ($request->checklistItems as $item) {
            $done = in_array($item->code, $completedCodes, true);
            $item->update([
                'completed' => $done,
                'completed_by' => $done ? Auth::id() : null,
                'completed_at' => $done ? now() : null,
            ]);
        }
    }

    /** @return list<string> */
    public function missingRequired(SendRequest $request): array
    {
        $request->loadMissing('checklistItems');

        return $request->checklistItems
            ->filter(fn ($i) => $i->required && ! $i->completed)
            ->pluck('label')
            ->values()
            ->all();
    }

    public function isComplete(SendRequest $request): bool
    {
        return count($this->missingRequired($request)) === 0;
    }
}
