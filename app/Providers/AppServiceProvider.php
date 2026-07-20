<?php

namespace App\Providers;

use App\Support\PerformanceTracker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Paginator::defaultView('vendor.pagination.metronic');
        if ($this->app->environment('local')) {
            Model::shouldBeStrict();
        }

        $this->app->bind(
            \App\Contracts\SendProviderInterface::class,
            \App\Http\Services\Send\ManualSendProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function ($query) {
            PerformanceTracker::recordQuery((float) $query->time);

            $slowQueryMs = (float) env('SLOW_QUERY_LOG_MS', 250);
            if ($query->time >= $slowQueryMs) {
                Log::info('slow_query', [
                    'time_ms' => round((float) $query->time, 2),
                    'sql' => $query->sql,
                ]);
            }
        });

        if (PHP_SAPI !== 'cli-server' && ! $this->app->runningInConsole()) {
            URL::forceScheme('https');
        }
    }
}
