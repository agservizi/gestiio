<?php

namespace App\Http\Controllers;

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
