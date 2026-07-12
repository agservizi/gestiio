<?php

namespace App\Policies;

use App\Models\LuggageDeposit;
use App\Models\User;

class LuggageDepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['admin', 'agente', 'supervisore', 'operatore']);
    }

    public function view(User $user, LuggageDeposit $deposit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, LuggageDeposit $deposit): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, LuggageDeposit $deposit): bool
    {
        return $user->hasPermissionTo('admin');
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasPermissionTo('admin');
    }

    public function checkIn(User $user, LuggageDeposit $deposit): bool
    {
        return $this->viewAny($user);
    }

    public function checkOut(User $user, LuggageDeposit $deposit): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $this->viewAny($user);
    }
}
