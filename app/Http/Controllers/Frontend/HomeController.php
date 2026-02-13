<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function show()
    {
        return view('Frontend.Home.show');
    }
}
