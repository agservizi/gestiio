<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class Autenticazione2faController extends Controller
{
    public function show($cosa = null)
    {

        return view('Backend.DatiUtente.abilita2fa', [
            'record' => Auth::user(),
            'controller' => AreaPersonaleController::class,
            'titoloPagina' => 'Autenticazione a due fattori richiesta'
        ]);

    }
}
