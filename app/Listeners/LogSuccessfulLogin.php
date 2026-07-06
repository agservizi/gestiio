<?php

namespace App\Listeners;

use App\Models\RegistroLogin;
use App\Models\RegistroLoginImpersona;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        try {
            $registro = new RegistroLogin;
            $registro->user_id = $event->user->id;
            $registro->email = $event->user->email;
            $registro->riuscito = 1;
            $registro->remember = $event->remember;
            $registro->save();
        } catch (\Throwable $exception) {
            Log::warning('Registro login non salvato', [
                'user_id' => $event->user->id ?? null,
                'email' => $event->user->email ?? null,
                'error' => $exception->getMessage(),
            ]);

            $registro = null;
        }

        if (! Session::has('impersona')) {
            $user = $event->user;
            DB::table('users')->where('id', $user->id)->update(['ultimo_accesso' => Carbon::now()->format('Y-m-d H:i:s')]);
        } else {
            $impersona = new RegistroLoginImpersona;
            $impersona->user_id = Session::get('impersona');
            $impersona->registro_id = $registro?->id;
            $impersona->save();
        }
        if ($event->user->hasPermissionTo('admin')) {
            Session::put('admin', true);
        }
    }
}
