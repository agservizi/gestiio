<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Backend\Autenticazione2faController;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class Ensure2faAbilitata
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string|null  $redirectToRoute
     * @return Response|RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (Auth::id() !== 1 && ! $request->user()->two_factor_secret) {
            return Redirect::action([Autenticazione2faController::class, 'show'], '');
        }

        return $next($request);
    }
}
