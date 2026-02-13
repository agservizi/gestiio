<?php

namespace App\Http\Responses;

use App\Http\Controllers\Backend\ContrattoTelefoniaController;
use App\Http\Controllers\Backend\DashboardController;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class TwoFactorLoginResponse implements LoginResponseContract
{

    public function toResponse($request)
    {

        // below is the existing response
        // replace this with your own code
        // the user can be located with Auth facade

        if ($request->has('backTo')) {
            return redirect($request->input('backTo'));
        }

        if (Auth::user()->hasAnyPermission(['admin', 'agente'])) {
            $redirectTo = action([DashboardController::class, 'show']);
        }elseif(Auth::user()->hasPermissionTo('supervisore')){
            $redirectTo = action([ContrattoTelefoniaController::class, 'index']);

        } else {
            $redirectTo = config('fortify.home');
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectTo);
    }

}
