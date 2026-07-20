<?php

namespace App\Console;

use App\Console\Commands\BackfillAllegatiDbContent;
use App\Console\Commands\BackfillGestoriEnergiaCategorie;
use App\Console\Commands\BackfillGestoriEnergiaLoghiDb;
use App\Console\Commands\CreateDemoAgente;
use App\Console\Commands\DocumentiScadenzeReminder;
use App\Console\Commands\PollOpenApiVisure;
use App\Console\Commands\ChargeLockerAgentSubscriptions;
use App\Console\Commands\ChargeLuggageAgentSubscriptions;
use App\Console\Commands\GeneraProformaFornitore;
use App\Console\Commands\SendAssignPending;
use App\Console\Commands\SendExpireStale;
use App\Console\Commands\SendLuggagePickupQrReminders;
use App\Console\Commands\SendMarkSlaBreaches;
use App\Console\Commands\SendRetentionPurge;
use App\Console\Commands\SyncOpenApiPlatformCredit;
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
        SyncOpenApiPlatformCredit::class,
        SyncTipoVisuraOpenApiHash::class,
        TicketsAuditIntegrity::class,
        SendLuggagePickupQrReminders::class,
        ChargeLuggageAgentSubscriptions::class,
        ChargeLockerAgentSubscriptions::class,
        SendMarkSlaBreaches::class,
        SendAssignPending::class,
        SendExpireStale::class,
        SendRetentionPurge::class,
        GeneraProformaFornitore::class,
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

        $schedule->command('visure:sync-openapi-credit')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('documenti:promemoria-scadenze --giorni=7 --limit=200')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('ebike:controlla-sla-spedizione')
            ->dailyAt('08:30')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('luggage:send-pickup-qr-reminders')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('luggage:charge-agent-subscriptions')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('locker:charge-agent-subscriptions')
            ->dailyAt('06:05')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('send:mark-sla-breaches')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('send:expire-stale')
            ->dailyAt('01:30')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('send:retention-purge')
            ->weeklyOn(1, '02:15')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('billing:genera-proforma-fornitore')
            ->monthlyOn(1, '06:30')
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
