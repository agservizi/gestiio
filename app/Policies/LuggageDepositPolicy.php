<?php

namespace App\Policies;

use App\Http\Services\LuggageAgentSubscriptionService;
use App\Models\LuggageDeposit;
use App\Models\User;

class LuggageDepositPolicy
{
    public const PERMISSION = 'servizio_deposito_bagagli';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function view(User $user, LuggageDeposit $deposit): bool
    {
        return $this->canAccessDeposit($user, $deposit);
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function update(User $user, LuggageDeposit $deposit): bool
    {
        return $this->canAccessDeposit($user, $deposit);
    }

    public function delete(User $user, LuggageDeposit $deposit): bool
    {
        return $user->hasPermissionTo('admin');
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasPermissionTo('admin');
    }

    public function manageStationSettings(User $user): bool
    {
        if ($user->hasPermissionTo('admin')) {
            return false;
        }

        return $this->canAccess($user);
    }

    public function manageStationApis(User $user): bool
    {
        return $user->hasPermissionTo('admin');
    }

    public function checkIn(User $user, LuggageDeposit $deposit): bool
    {
        return $this->canAccessDeposit($user, $deposit);
    }

    public function checkOut(User $user, LuggageDeposit $deposit): bool
    {
        return $this->canAccessDeposit($user, $deposit);
    }

    public function manage(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function viewReports(User $user): bool
    {
        return $user->hasPermissionTo('admin');
    }

    private function canAccess(User $user): bool
    {
        if ($user->hasPermissionTo('admin')) {
            return true;
        }

        if (! $user->hasPermissionTo(self::PERMISSION)) {
            return false;
        }

        return app(LuggageAgentSubscriptionService::class)->hasActiveSubscription($user);
    }

    private function canAccessDeposit(User $user, LuggageDeposit $deposit): bool
    {
        if (! $this->canAccess($user)) {
            return false;
        }

        if ($user->hasPermissionTo('admin')) {
            return true;
        }

        if (! $deposit->station_id) {
            return false;
        }

        $station = $deposit->relationLoaded('station')
            ? $deposit->station
            : $deposit->station()->first();

        return $station && (int) $station->user_id === (int) $user->id;
    }
}
