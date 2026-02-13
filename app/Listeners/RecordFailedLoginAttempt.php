<?php

namespace App\Listeners;

use App\Models\RegistroLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RecordFailedLoginAttempt
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
        $registro = new RegistroLogin();
        $registro->email = $event->credentials['email'];
        $registro->user_id = $event->user ? $event->user->id : null;
        $registro->save();
    }
}
