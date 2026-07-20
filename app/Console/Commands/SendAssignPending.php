<?php

namespace App\Console\Commands;

use App\Http\Services\SendAssignmentService;
use Illuminate\Console\Command;

class SendAssignPending extends Command
{
    protected $signature = 'send:assign-pending';

    protected $description = 'Assegna automaticamente le pratiche SEND inviate senza supervisore';

    public function handle(SendAssignmentService $assignment): int
    {
        $count = $assignment->assignPending();

        $this->info("Assegnate {$count} pratiche SEND in attesa.");

        return self::SUCCESS;
    }
}
