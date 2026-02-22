<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ContrattoTelefonia;

class ContrattoController extends Controller
{
    public function index()
    {
        $records = ContrattoTelefonia::delCliente()
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('Frontend.Contratto.index', [
            'records' => $records,
            'titoloPagina' => 'I tuoi contratti',
        ]);
    }
}
