<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Gestore;
use App\Models\GestoreContrattoEnergia;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $isControlliContrattiPage = request()->routeIs('controlli-contratti');

        return view('Backend.Setting.index', [
            'titoloPagina' => $isControlliContrattiPage ? 'Controlli contratti' : 'Impostazioni',
            'isControlliContrattiPage' => $isControlliContrattiPage,
            'gestoriTelefonia' => Gestore::query()->orderBy('nome')->get(['id', 'nome']),
            'gestoriEnergia' => GestoreContrattoEnergia::query()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request)
    {
        $rules = Setting::getValidationRules();
        $fallbackRules = [
            'blocco_contratti_telefonia_verifica_cf_attivo' => 'nullable|in:0,1',
            'blocco_contratti_telefonia_cf_morosita' => 'nullable|string',
            'blocco_contratti_telefonia_cf_blacklist' => 'nullable|string',
            'blocco_contratti_telefonia_cf_credit_check' => 'nullable|string',
            'blocco_contratti_telefonia_cf_morosita_per_gestore' => 'nullable|string',
            'blocco_contratti_telefonia_cf_blacklist_per_gestore' => 'nullable|string',
            'blocco_contratti_telefonia_cf_credit_check_per_gestore' => 'nullable|string',
            'blocco_contratti_energia_verifica_cf_attivo' => 'nullable|in:0,1',
            'blocco_contratti_energia_cf_morosita' => 'nullable|string',
            'blocco_contratti_energia_cf_blacklist' => 'nullable|string',
            'blocco_contratti_energia_cf_credit_check' => 'nullable|string',
            'blocco_contratti_energia_cf_morosita_per_gestore' => 'nullable|string',
            'blocco_contratti_energia_cf_blacklist_per_gestore' => 'nullable|string',
            'blocco_contratti_energia_cf_credit_check_per_gestore' => 'nullable|string',
        ];

        foreach ($fallbackRules as $key => $rule) {
            if (! array_key_exists($key, $rules)) {
                $rules[$key] = $rule;
            }
        }

        $data = $this->validate($request, $rules);

        $validSettings = array_keys($rules);

        foreach ($data as $key => $val) {
            if (in_array($key, $validSettings)) {
                Setting::add($key, $val, Setting::getDataType($key));
            }
        }

        return redirect()->back()->with('status', 'Dati salvati');
    }
}
