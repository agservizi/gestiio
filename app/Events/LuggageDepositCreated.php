<?php

namespace App\Events;

use App\Models\LuggageDeposit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LuggageDepositCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public LuggageDeposit $deposit)
    {
    }
}
