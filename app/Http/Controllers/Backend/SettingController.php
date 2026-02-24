<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
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
        ]);
    }

    public function store(Request $request)
    {
        $rules = Setting::getValidationRules();
        $fallbackRules = [
            'blocco_contratti_verifica_cf_attivo' => 'nullable|in:0,1',
            'blocco_contratti_cf_morosita' => 'nullable|string',
            'blocco_contratti_cf_blacklist' => 'nullable|string',
            'blocco_contratti_cf_credit_check' => 'nullable|string',
        ];

        foreach ($fallbackRules as $key => $rule) {
            if (!array_key_exists($key, $rules)) {
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
