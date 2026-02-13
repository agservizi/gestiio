<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioController extends Controller
{
    public function statusCallback(Request $request)
    {
        Log::notice(__FUNCTION__, $request->all());

        return __FUNCTION__;
    }
    public function messageCallback(Request $request)
    {
        Log::notice(__FUNCTION__, $request->all());
        return __FUNCTION__;
    }
}
