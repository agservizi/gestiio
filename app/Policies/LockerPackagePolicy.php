<?php

namespace App\Policies;

use App\Http\Services\LockerAgentSubscriptionService;
use App\Models\LockerPackage;
use App\Models\User;

class LockerPackagePolicy
{
    public const PERMISSION = 'servizio_locker_point';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function view(User $user, LockerPackage $package): bool
    {
        return $this->canAccessPackage($user, $package);
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user);
    }

    public function update(User $user, LockerPackage $package): bool
    {
        return $this->canAccessPackage($user, $package);
    }

    public function delete(User $user, LockerPackage $package): bool
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

    public function intake(User $user, LockerPackage $package): bool
    {
        return $this->canAccessPackage($user, $package);
    }

    public function deliver(User $user, LockerPackage $package): bool
    {
        return $this->canAccessPackage($user, $package);
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

        return app(LockerAgentSubscriptionService::class)->hasActiveSubscription($user);
    }

    private function canAccessPackage(User $user, LockerPackage $package): bool
    {
        if (! $this->canAccess($user)) {
            return false;
        }

        if ($user->hasPermissionTo('admin')) {
            return true;
        }

        if (! $package->station_id) {
            return false;
        }

        $station = $package->relationLoaded('station')
            ? $package->station
            : $package->station()->first();

        return $station && (int) $station->user_id === (int) $user->id;
    }
}
