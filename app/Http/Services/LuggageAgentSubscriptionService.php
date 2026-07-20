<?php

namespace App\Http\Services;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Support\LuggageConfig;
use App\Models\Agente;
use App\Models\LuggageAgentSubscription;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\User;
use App\Policies\LuggageDepositPolicy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LuggageAgentSubscriptionService
{
    public function monthlyFee(): float
    {
        return max(0, LuggageConfig::agentMonthlyFee());
    }

    public function billingMonth(?Carbon $date = null): Carbon
    {
        return ($date ?? now())->copy()->startOfMonth()->startOfDay();
    }

    public function isPaidForMonth(User $user, ?Carbon $month = null): bool
    {
        if (! Schema::hasTable('luggage_agent_subscriptions')) {
            return false;
        }

        return LuggageAgentSubscription::query()
            ->where('user_id', $user->id)
            ->whereDate('billing_month', $this->billingMonth($month))
            ->exists();
    }

    public function hasActiveSubscription(User $user): bool
    {
        if ($user->hasPermissionTo('admin')) {
            return true;
        }

        if (! $user->hasPermissionTo(LuggageDepositPolicy::PERMISSION)) {
            return false;
        }

        if ($this->monthlyFee() <= 0) {
            return true;
        }

        return $this->isPaidForMonth($user);
    }

    public function ensureAccessGranted(User $user): bool
    {
        if ($user->hasPermissionTo('admin')) {
            return true;
        }

        if (! Agente::query()->where('user_id', $user->id)->exists()) {
            return false;
        }

        if ($this->monthlyFee() <= 0) {
            $user->givePermissionTo(LuggageDepositPolicy::PERMISSION);
            app(LuggageStationService::class)->ensureStation($user);

            return true;
        }

        if ($this->isPaidForMonth($user)) {
            if (! $user->hasPermissionTo(LuggageDepositPolicy::PERMISSION)) {
                $user->givePermissionTo(LuggageDepositPolicy::PERMISSION);
            }
            app(LuggageStationService::class)->ensureStation($user);

            return true;
        }

        if (! $this->chargeMonth($user)) {
            return false;
        }

        if (! $user->hasPermissionTo(LuggageDepositPolicy::PERMISSION)) {
            $user->givePermissionTo(LuggageDepositPolicy::PERMISSION);
        }
        app(LuggageStationService::class)->ensureStation($user);

        return true;
    }

    public function revokeAccess(User $user): void
    {
        if ($user->hasPermissionTo(LuggageDepositPolicy::PERMISSION)) {
            $user->revokePermissionTo(LuggageDepositPolicy::PERMISSION);
        }
    }

    public function chargeMonth(User $user, ?Carbon $month = null): bool
    {
        $fee = $this->monthlyFee();
        if ($fee <= 0) {
            return true;
        }

        $billingMonth = $this->billingMonth($month);

        if ($this->isPaidForMonth($user, $billingMonth)) {
            return true;
        }

        $agente = Agente::query()->where('user_id', $user->id)->first();
        if (! $agente) {
            return false;
        }

        if ((float) $agente->portafoglio_servizi < $fee) {
            return false;
        }

        $label = $billingMonth->translatedFormat('F Y');

        return (bool) DB::transaction(function () use ($user, $fee, $billingMonth, $label) {
            if ($this->isPaidForMonth($user, $billingMonth)) {
                return true;
            }

            $movimento = new MovimentoPortafoglio;
            $movimento->agente_id = $user->id;
            $movimento->importo = -$fee;
            $movimento->descrizione = 'Canone mensile Deposito Bagagli · '.$label;
            $movimento->portafoglio = TipiPortafoglioEnum::SERVIZI->value;
            $movimento->save();

            LuggageAgentSubscription::create([
                'user_id' => $user->id,
                'billing_month' => $billingMonth->toDateString(),
                'amount' => $fee,
                'movimento_portafoglio_id' => $movimento->id,
            ]);

            return true;
        });
    }

    public function suspendForNonPayment(User $user, ?Carbon $month = null): void
    {
        $billingMonth = $this->billingMonth($month);
        $this->revokeAccess($user);

        $fee = $this->monthlyFee();
        $label = $billingMonth->translatedFormat('F Y');
        $titolo = 'Deposito bagagli sospeso';
        $messaggio = 'Il servizio Deposito Bagagli è stato sospeso: portafoglio servizi insufficiente per il canone di '.importo($fee, true).' ('.$label.'). Ricarica il plafond e chiedi all\'admin di riattivare il servizio.';

        Notifica::notificaAdAgente($user, $titolo, $messaggio, 'warning');
        Notifica::notificaAdAdmin(
            $titolo,
            'Servizio Deposito Bagagli sospeso per <span class="fw-bold">'.$user->nominativo().'</span>: plafond servizi insufficiente ('.$label.').',
            'warning'
        );
    }

    /**
     * @return array{processed:int, charged:int, suspended:int}
     */
    public function renewDueSubscriptions(?Carbon $month = null): array
    {
        $stats = ['processed' => 0, 'charged' => 0, 'suspended' => 0];

        if ($this->monthlyFee() <= 0 || ! Schema::hasTable('luggage_agent_subscriptions')) {
            return $stats;
        }

        User::permission(LuggageDepositPolicy::PERMISSION)
            ->get()
            ->each(function (User $user) use ($month, &$stats) {
                if ($user->hasPermissionTo('admin')) {
                    return;
                }

                $stats['processed']++;

                if ($this->isPaidForMonth($user, $month)) {
                    return;
                }

                if ($this->chargeMonth($user, $month)) {
                    $stats['charged']++;

                    return;
                }

                $this->suspendForNonPayment($user, $month);
                $stats['suspended']++;
            });

        return $stats;
    }
}
