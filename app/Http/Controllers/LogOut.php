<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SendOTP;
use Illuminate\Support\Facades\Auth;
use function redirect;
use function Session;

class LogOut
{

    public function logOut()
    {

        Auth::logout();
        return redirect('/login');

    }

    public function loginId($id)
    {
        Auth::loginUsingId($id);
        return redirect('/');
    }


    public function sendOtpEmail($id)
    {

        $user = User::find($id);
        if ($user && $user->two_factor_secret) {
            $user->notify(new SendOTP());
        }
        return ['success' => true];
    }

}
