<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Gestore;
use App\Models\GestoreContrattoEnergia;
use App\Models\Setting;
use App\Models\SettingsAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $isControlliContrattiPage = $request->routeIs('controlli-contratti');
        $this->authorizeSettings($request, $isControlliContrattiPage ? 'controlli-contratti.view' : 'settings.view');

        return view('Backend.Setting.index', [
            'titoloPagina' => $isControlliContrattiPage ? 'Controlli contratti' : 'Impostazioni',
            'isControlliContrattiPage' => $isControlliContrattiPage,
            'gestoriTelefonia' => Gestore::query()->orderBy('nome')->get(['id', 'nome']),
            'gestoriEnergia' => GestoreContrattoEnergia::query()->orderBy('nome')->get(['id', 'nome']),
            'auditLogs' => Schema::hasTable('settings_audit_logs')
                ? SettingsAuditLog::with('utente')->orderByDesc('id')->limit(40)->get()
                : collect(),
            'settingSnapshot' => $this->settingSnapshot(),
        ]);
    }

    public function store(Request $request)
    {
        $scope = (string) $request->input('_settings_scope', 'all');
        $permission = $scope === 'controlli_contratti' ? 'controlli-contratti.update' : 'settings.update';
        $this->authorizeSettings($request, $permission);

        $fields = $this->fieldsForScope($scope);
        $rules = $fields->pluck('rules', 'name')->reject(fn ($rule) => is_null($rule))->toArray();
        $data = $this->validate($request, $rules);

        foreach ($fields as $field) {
            if (($field['type'] ?? null) === 'checkbox' && ! array_key_exists($field['name'], $data)) {
                $data[$field['name']] = '0';
            }
        }

        $data = $this->normalizeSettingsData($data);
        $this->validateBusinessRules($data);

        DB::transaction(function () use ($request, $data, $fields) {
            foreach ($data as $key => $val) {
                if (! $fields->contains('name', $key)) {
                    continue;
                }

                $oldValue = Setting::get($key, '');
                $type = Setting::getDataType($key);
                Setting::add($key, $val, $type);

                if ((string) $oldValue !== (string) $val) {
                    $this->auditSetting($request, $key, $oldValue, $val, 'update');
                }
            }
        });

        return redirect()->back()->with('status', 'Dati salvati');
    }

    public function export(Request $request)
    {
        $this->authorizeSettings($request, 'settings.export');

        return response()->streamDownload(function () {
            echo json_encode($this->settingSnapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'gestiio-settings-'.now()->format('Ymd-His').'.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function import(Request $request)
    {
        $this->authorizeSettings($request, 'settings.import');

        $data = $request->validate([
            'settings_json' => 'required|string|max:200000',
        ]);

        $decoded = json_decode($data['settings_json'], true);
        abort_unless(is_array($decoded), 422, 'JSON impostazioni non valido.');

        $validFields = $this->allFields();
        $values = collect($decoded)
            ->only($validFields->pluck('name')->all())
            ->toArray();

        $values = $this->normalizeSettingsData($values);
        $this->validateBusinessRules($values);

        DB::transaction(function () use ($request, $values) {
            foreach ($values as $key => $value) {
                $oldValue = Setting::get($key, '');
                Setting::add($key, $value, Setting::getDataType($key));
                $this->auditSetting($request, $key, $oldValue, $value, 'import');
            }
        });

        return redirect()->back()->with('status', 'Impostazioni importate');
    }

    public function rollback(Request $request, SettingsAuditLog $auditLog)
    {
        $this->authorizeSettings($request, 'settings.rollback');

        $currentValue = Setting::get($auditLog->setting_name, '');
        Setting::add($auditLog->setting_name, $auditLog->old_value, Setting::getDataType($auditLog->setting_name));
        $this->auditSetting($request, $auditLog->setting_name, $currentValue, $auditLog->old_value, 'rollback');

        return redirect()->back()->with('status', 'Impostazione ripristinata');
    }

    public function testCodiceFiscale(Request $request)
    {
        $this->authorizeSettings($request, 'settings.test');

        $data = $request->validate([
            'modulo' => 'required|in:telefonia,energia',
            'codice_fiscale' => 'required|string|max:32',
            'gestore_id' => 'nullable|integer',
        ]);

        $cf = $this->normalizeCodiceFiscale($data['codice_fiscale']);
        $modulo = $data['modulo'];
        $gestoreId = $data['gestore_id'] ?? null;

        $matches = [];
        foreach (['morosita', 'blacklist', 'credit_check'] as $reason) {
            $generalKey = "blocco_contratti_{$modulo}_cf_{$reason}";
            if (in_array($cf, $this->parseCodiciFiscali(Setting::get($generalKey, '')), true)) {
                $matches[] = "{$reason} generale";
            }

            $perGestoreKey = "blocco_contratti_{$modulo}_cf_{$reason}_per_gestore";
            $perGestore = $this->parsePerGestore(Setting::get($perGestoreKey, ''));
            if ($gestoreId && in_array($cf, $perGestore[(int) $gestoreId] ?? [], true)) {
                $matches[] = "{$reason} gestore {$gestoreId}";
            }
        }

        return redirect()->back()->with('test_cf_result', [
            'cf' => $cf,
            'modulo' => $modulo,
            'gestore_id' => $gestoreId,
            'bloccato' => count($matches) > 0,
            'motivi' => $matches,
        ]);
    }

    protected function authorizeSettings(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless($user && ($user->hasRole('admin') || $user->can('admin') || $user->can($permission)), 403);
    }

    protected function allFields(): Collection
    {
        return collect(config('setting_fields'))->pluck('elements')->flatten(1);
    }

    protected function fieldsForScope(string $scope): Collection
    {
        if ($scope === 'controlli_contratti') {
            return collect(Arr::get(config('setting_fields'), 'controlli_contratti.elements', []));
        }

        return $this->allFields();
    }

    protected function normalizeSettingsData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (Str::endsWith($key, '_verifica_cf_attivo')) {
                $data[$key] = (string) (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);

                continue;
            }

            if (Str::contains($key, '_cf_')) {
                $data[$key] = Str::endsWith($key, '_per_gestore')
                    ? $this->normalizePerGestore((string) $value)
                    : implode("\n", $this->parseCodiciFiscali((string) $value));
            }
        }

        return $data;
    }

    protected function validateBusinessRules(array $data): void
    {
        $telefoniaIds = Gestore::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $energiaIds = GestoreContrattoEnergia::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($data as $key => $value) {
            if (! Str::contains($key, '_cf_') || Str::endsWith($key, '_verifica_cf_attivo')) {
                continue;
            }

            if (Str::endsWith($key, '_per_gestore')) {
                $allowed = Str::contains($key, '_telefonia_') ? $telefoniaIds : $energiaIds;
                foreach ($this->parsePerGestore($value) as $gestoreId => $codici) {
                    abort_unless(in_array((int) $gestoreId, $allowed, true), 422, "Gestore non valido nella regola {$key}: {$gestoreId}");
                    foreach ($codici as $cf) {
                        abort_unless($this->isValidCodiceFiscale($cf), 422, "Codice fiscale non valido in {$key}: {$cf}");
                    }
                }

                continue;
            }

            foreach ($this->parseCodiciFiscali($value) as $cf) {
                abort_unless($this->isValidCodiceFiscale($cf), 422, "Codice fiscale non valido in {$key}: {$cf}");
            }
        }
    }

    protected function normalizePerGestore(?string $value): string
    {
        $rows = [];
        foreach (preg_split('/\R+/', (string) $value) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$gestoreId, $codici] = array_map('trim', explode(':', $line, 2));
            $gestoreId = (int) $gestoreId;
            if ($gestoreId <= 0) {
                continue;
            }

            $list = $this->parseCodiciFiscali($codici);
            if ($list !== []) {
                $rows[] = $gestoreId.': '.implode(',', $list);
            }
        }

        return implode("\n", $rows);
    }

    protected function parsePerGestore(?string $value): array
    {
        $result = [];
        foreach (preg_split('/\R+/', (string) $value) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$gestoreId, $codici] = array_map('trim', explode(':', $line, 2));
            $result[(int) $gestoreId] = $this->parseCodiciFiscali($codici);
        }

        return $result;
    }

    protected function parseCodiciFiscali(?string $value): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $value) ?: [])
            ->map(fn ($cf) => $this->normalizeCodiceFiscale((string) $cf))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeCodiceFiscale(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value) ?? '');
    }

    protected function isValidCodiceFiscale(string $value): bool
    {
        return (bool) preg_match('/^[A-Z0-9]{11,16}$/', $value);
    }

    protected function settingSnapshot(): array
    {
        return $this->allFields()
            ->pluck('name')
            ->mapWithKeys(fn ($name) => [$name => Setting::get($name, '')])
            ->all();
    }

    protected function auditSetting(Request $request, string $key, mixed $oldValue, mixed $newValue, string $action): void
    {
        if (! Schema::hasTable('settings_audit_logs')) {
            return;
        }

        SettingsAuditLog::create([
            'user_id' => $request->user()?->id,
            'setting_name' => $key,
            'old_value' => is_scalar($oldValue) || is_null($oldValue) ? (string) $oldValue : json_encode($oldValue),
            'new_value' => is_scalar($newValue) || is_null($newValue) ? (string) $newValue : json_encode($newValue),
            'action' => $action,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);
    }
}
