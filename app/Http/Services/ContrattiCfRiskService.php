<?php

namespace App\Http\Services;

use App\Models\Setting;

class ContrattiCfRiskService
{
    public const DOMAIN_TELEFONIA = 'telefonia';
    public const DOMAIN_ENERGIA = 'energia';

    private const STATUS_LABELS = [
        'morosita' => 'Morosita',
        'blacklist' => 'Blacklist',
        'credit_check' => 'Credit check negativo',
    ];

    public function check(?string $codiceFiscale, string $domain = self::DOMAIN_TELEFONIA, ?int $gestoreId = null): array
    {
        $domain = $this->normalizeDomain($domain);
        $normalized = $this->normalizeCodiceFiscale($codiceFiscale);
        $enabled = $this->isEnabled($domain);
        $gestoreId = $this->normalizeGestoreId($gestoreId);

        if ($normalized === '') {
            return [
                'domain' => $domain,
                'gestore_id' => $gestoreId,
                'enabled' => $enabled,
                'codice_fiscale' => '',
                'blocked' => false,
                'statuses' => [],
                'labels' => [],
                'message' => null,
            ];
        }

        $lists = $this->lists($domain, $gestoreId);
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
            'domain' => $domain,
            'gestore_id' => $gestoreId,
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

    public function isEnabled(string $domain = self::DOMAIN_TELEFONIA): bool
    {
        $domain = $this->normalizeDomain($domain);
        $domainKey = 'blocco_contratti_' . $domain . '_verifica_cf_attivo';
        $val = Setting::get($domainKey, null);
        if ($val === null || $val === '') {
            $val = Setting::get('blocco_contratti_verifica_cf_attivo', '0');
        }

        return in_array((string) $val, ['1', 'true', 'on'], true);
    }

    private function lists(string $domain, ?int $gestoreId): array
    {
        $domain = $this->normalizeDomain($domain);

        $morositaGlobal = $this->parseList(
            (string) $this->settingForDomain($domain, 'cf_morosita', 'blocco_contratti_cf_morosita')
        );
        $blacklistGlobal = $this->parseList(
            (string) $this->settingForDomain($domain, 'cf_blacklist', 'blocco_contratti_cf_blacklist')
        );
        $creditCheckGlobal = $this->parseList(
            (string) $this->settingForDomain($domain, 'cf_credit_check', 'blocco_contratti_cf_credit_check')
        );

        $morositaByGestore = $this->parsePerGestore(
            (string) Setting::get('blocco_contratti_' . $domain . '_cf_morosita_per_gestore', '')
        );
        $blacklistByGestore = $this->parsePerGestore(
            (string) Setting::get('blocco_contratti_' . $domain . '_cf_blacklist_per_gestore', '')
        );
        $creditCheckByGestore = $this->parsePerGestore(
            (string) Setting::get('blocco_contratti_' . $domain . '_cf_credit_check_per_gestore', '')
        );

        return [
            'morosita' => $this->resolveListForGestore($morositaGlobal, $morositaByGestore, $gestoreId),
            'blacklist' => $this->resolveListForGestore($blacklistGlobal, $blacklistByGestore, $gestoreId),
            'credit_check' => $this->resolveListForGestore($creditCheckGlobal, $creditCheckByGestore, $gestoreId),
        ];
    }

    private function settingForDomain(string $domain, string $suffix, string $legacyKey): string
    {
        $domainKey = 'blocco_contratti_' . $domain . '_' . $suffix;
        $value = Setting::get($domainKey, null);

        if ($value === null || $value === '') {
            $value = Setting::get($legacyKey, '');
        }

        return (string) $value;
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

    private function parsePerGestore(string $raw): array
    {
        $map = [];
        $lines = preg_split('/\R+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s*[:=|;]\s*/', $line, 2);
            if (!$parts || count($parts) < 1) {
                continue;
            }

            $gestoreId = $this->normalizeGestoreId(isset($parts[0]) ? (int) trim($parts[0]) : null);
            if ($gestoreId === null) {
                continue;
            }

            $cfRaw = $parts[1] ?? '';
            $map[$gestoreId] = $this->parseList((string) $cfRaw);
        }

        return $map;
    }

    private function resolveListForGestore(array $global, array $mapByGestore, ?int $gestoreId): array
    {
        if ($gestoreId !== null && array_key_exists($gestoreId, $mapByGestore)) {
            return $mapByGestore[$gestoreId];
        }

        return $global;
    }

    private function normalizeGestoreId(?int $gestoreId): ?int
    {
        if (!$gestoreId || $gestoreId < 1) {
            return null;
        }

        return $gestoreId;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if ($domain === self::DOMAIN_ENERGIA) {
            return self::DOMAIN_ENERGIA;
        }

        return self::DOMAIN_TELEFONIA;
    }
}
