<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AreaUtenteController extends Controller
{
    public function show()
    {
        if (Auth::user()->hasAnyPermission(['admin', 'agente', 'supervisore','operatore'])) {
            return redirect()->action([DashboardController::class, 'show']);
        }

        return view('Frontend.AreaUtente.show');
    }
}
