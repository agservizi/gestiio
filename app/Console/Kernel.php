<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        \App\Console\Commands\BackfillAllegatiDbContent::class,
        \App\Console\Commands\BackfillGestoriEnergiaCategorie::class,
        \App\Console\Commands\BackfillGestoriEnergiaLoghiDb::class,
        \App\Console\Commands\DocumentiScadenzeReminder::class,
        \App\Console\Commands\PollOpenApiVisure::class,
        \App\Console\Commands\SyncTipoVisuraOpenApiHash::class,
        \App\Console\Commands\TicketsAuditIntegrity::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('visure:poll-openapi --limit=100')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('documenti:promemoria-scadenze --giorni=7 --limit=200')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
