<?php

namespace App\Providers;

use App\Models\BillingDocument;
use App\Models\FatturaProforma;
use App\Models\LuggageDeposit;
use App\Models\ProduzioneOperatore;
use App\Models\Ticket;
use App\Policies\BillingDocumentPolicy;
use App\Policies\FatturaProformaPolicy;
use App\Policies\LuggageDepositPolicy;
use App\Policies\ProduzioneOperatorePolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ticket::class => TicketPolicy::class,
        LuggageDeposit::class => LuggageDepositPolicy::class,
        \App\Models\LockerPackage::class => \App\Policies\LockerPackagePolicy::class,
        FatturaProforma::class => FatturaProformaPolicy::class,
        ProduzioneOperatore::class => ProduzioneOperatorePolicy::class,
        BillingDocument::class => BillingDocumentPolicy::class,
        \App\Models\SendRequest::class => \App\Policies\SendRequestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
