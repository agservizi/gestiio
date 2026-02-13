<?php

namespace App\Http\Responses;

use App\Http\Controllers\Backend\ContrattoTelefoniaController;
use App\Http\Controllers\Backend\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{

    public function toResponse($request)
    {

        // below is the existing response
        // replace this with your own code
        // the user can be located with Auth facade

        if ($request->has('backTo')) {
            return redirect($request->input('backTo'));
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->hasAnyPermission(['admin', 'agente'])) {
            $redirectTo = action([DashboardController::class, 'show']);
        } elseif ($user && $user->hasPermissionTo('supervisore')) {
            $redirectTo = action([ContrattoTelefoniaController::class, 'index']);

        } else {
            $redirectTo = config('fortify.home');
        }


        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectTo);
    }

}
