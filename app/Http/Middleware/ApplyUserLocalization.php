<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class ApplyUserLocalization
{
    public function handle(Request $request, Closure $next)
    {
        $timezone = config('app.timezone', 'Europe/Rome');
        $dateFormat = 'd/m/Y';
        $numberFormat = 'it_IT';

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $timezone = $user->getExtra('fuso_orario') ?: $timezone;
            $dateFormat = $user->getExtra('formato_data') ?: $dateFormat;
            $numberFormat = $user->getExtra('formato_numeri_valuta') ?: $numberFormat;
        }

        config([
            'app.timezone' => $timezone,
            'app.user_date_format' => $dateFormat,
            'app.user_number_format' => $numberFormat,
        ]);

        date_default_timezone_set($timezone);

        View::share('userTimezone', $timezone);
        View::share('userDateFormat', $dateFormat);
        View::share('userNumberFormat', $numberFormat);

        return $next($request);
    }
}
