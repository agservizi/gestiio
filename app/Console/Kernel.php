<?php

namespace App\Console;

use App\Console\Commands\BackfillAllegatiDbContent;
use App\Console\Commands\BackfillGestoriEnergiaCategorie;
use App\Console\Commands\BackfillGestoriEnergiaLoghiDb;
use App\Console\Commands\CreateDemoAgente;
use App\Console\Commands\DocumentiScadenzeReminder;
use App\Console\Commands\PollOpenApiVisure;
use App\Console\Commands\SyncTipoVisuraOpenApiHash;
use App\Console\Commands\TicketsAuditIntegrity;
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
        BackfillAllegatiDbContent::class,
        BackfillGestoriEnergiaCategorie::class,
        BackfillGestoriEnergiaLoghiDb::class,
        CreateDemoAgente::class,
        DocumentiScadenzeReminder::class,
        PollOpenApiVisure::class,
        SyncTipoVisuraOpenApiHash::class,
        TicketsAuditIntegrity::class,
    ];

    /**
     * Define the application's command schedule.
     *
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
