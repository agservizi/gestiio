<?php

namespace App\Http\Controllers\Backend;

use App\Exports\EsportaClienti;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteAssistenza;
use Illuminate\Http\Request;

class EsportaController extends Controller
{
    public function esporta($cosa){
        $oggi=now()->format('d_m_Y');
        switch ($cosa){
            case 'clienti':
                return \Excel::download(new EsportaClienti(Cliente::select('nome','cognome','email')->whereNotNull('email')), "Clienti_al_$oggi.csv", \Maatwebsite\Excel\Excel::CSV);

            case 'clienti-assistenza':
                return \Excel::download(new EsportaClienti(ClienteAssistenza::select('nome','cognome','email')->whereNotNull('email')), "Clienti_assistenza_al_$oggi.csv", \Maatwebsite\Excel\Excel::CSV);
        }
    }
}
