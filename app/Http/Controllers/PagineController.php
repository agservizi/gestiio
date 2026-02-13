<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PagineController extends Controller
{
    public function show($pagina)
    {

        switch ($pagina) {
            case 'policies':

                return view('auth.policies');
        }
    }
}
