<?php

namespace App\Http\Services;

use App\Models\Setting;

class ContrattiCfRiskService
{
    private const STATUS_LABELS = [
        'morosita' => 'Morosita',
        'blacklist' => 'Blacklist',
        'credit_check' => 'Credit check negativo',
    ];

    public function check(?string $codiceFiscale): array
    {
        $normalized = $this->normalizeCodiceFiscale($codiceFiscale);
        $enabled = $this->isEnabled();

        if ($normalized === '') {
            return [
                'enabled' => $enabled,
                'codice_fiscale' => '',
                'blocked' => false,
                'statuses' => [],
                'labels' => [],
                'message' => null,
            ];
        }

        $lists = $this->lists();
        $statuses = [];

        foreach ($lists as $status => $cfList) {
            if (in_array($normalized, $cfList, true)) {
                $statuses[] = $status;
            }
        }

        $labels = array_values(array_map(function (string $status) {
            return self::STATUS_LABELS[$status] ?? $status;
        }, $statuses));

        $blocked = $enabled && !empty($statuses);

        return [
            'enabled' => $enabled,
            'codice_fiscale' => $normalized,
            'blocked' => $blocked,
            'statuses' => $statuses,
            'labels' => $labels,
            'message' => $blocked
                ? 'Codice fiscale bloccato: ' . implode(', ', $labels) . '.'
                : null,
        ];
    }

    public function isEnabled(): bool
    {
        $val = Setting::get('blocco_contratti_verifica_cf_attivo', '0');
        return in_array((string) $val, ['1', 'true', 'on'], true);
    }

    private function lists(): array
    {
        return [
            'morosita' => $this->parseList((string) Setting::get('blocco_contratti_cf_morosita', '')),
            'blacklist' => $this->parseList((string) Setting::get('blocco_contratti_cf_blacklist', '')),
            'credit_check' => $this->parseList((string) Setting::get('blocco_contratti_cf_credit_check', '')),
        ];
    }

    private function parseList(string $raw): array
    {
        $items = preg_split('/[\s,;]+/', strtoupper($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $items = array_map(function (string $item) {
            return preg_replace('/[^A-Z0-9]/', '', trim($item)) ?: '';
        }, $items);

        return array_values(array_unique(array_filter($items)));
    }

    private function normalizeCodiceFiscale(?string $cf): string
    {
        $value = strtoupper(trim((string) $cf));
        return preg_replace('/[^A-Z0-9]/', '', $value) ?: '';
    }
}

