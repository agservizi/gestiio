<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SendOTP;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;
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
        if (!$user || !$user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Utente non valido o autenticazione a due fattori non configurata.'
            ], 422);
        }

        if (!$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna email disponibile per questo utente.'
            ], 422);
        }

        try {
            app(SendOTP::class)->sendToUser($user);
        } catch (Throwable $exception) {
            Log::warning('Invio OTP email fallito', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invio OTP non disponibile al momento. Riprova tra poco.'
            ], 503);
        }

        return response()->json(['success' => true]);
    }

}
