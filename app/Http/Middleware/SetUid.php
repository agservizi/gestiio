<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class SetUid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Session::exists('Uid')) {
            $Uid = Cookie::get('Uid');
            if ($Uid !== null) {
                Session::put('Uid', $Uid);
            } else {
                $Uid = uniqid('', true);
                Session::put('Uid', $Uid);
                Cookie::queue(Cookie::forever('Uid', $Uid));
            }
        }

        return $next($request);
    }
}
