<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Backend\Autenticazione2faController;
use Closure;
use Illuminate\Support\Facades\Redirect;

class Ensure2faAbilitata
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (env('APP_NAME') == 'gestiio' && \Auth::id() !== 1 && !$request->user()->two_factor_secret) {
            return Redirect::action([Autenticazione2faController::class, 'show'], '');
        }

        return $next($request);
    }
}
