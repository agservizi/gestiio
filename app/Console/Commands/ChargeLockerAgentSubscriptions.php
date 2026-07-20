<?php

namespace App\Console\Commands;

use App\Http\Services\LockerAgentSubscriptionService;
use Illuminate\Console\Command;

class ChargeLockerAgentSubscriptions extends Command
{
    protected $signature = 'locker:charge-agent-subscriptions';

    protected $description = 'Addebita il canone mensile Locker Point agli agenti abilitati';

    public function handle(LockerAgentSubscriptionService $service): int
    {
        $stats = $service->renewDueSubscriptions();

        $this->info(sprintf(
            'Canoni Locker Point: %d agenti · %d addebitati · %d sospesi',
            $stats['processed'],
            $stats['charged'],
            $stats['suspended']
        ));

        return self::SUCCESS;
    }
}
