<?php

namespace App\Policies;

use App\Models\ProduzioneOperatore;
use App\Models\User;

class ProduzioneOperatorePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasPermissionTo('agente');
    }

    public function view(User $user, ProduzioneOperatore $produzione): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('agente') && (int) $produzione->user_id === (int) $user->id;
    }

    public function createProforma(User $user, ProduzioneOperatore $produzione): bool
    {
        return $this->isAdmin($user);
    }

    public function recalculate(User $user, ProduzioneOperatore $produzione): bool
    {
        return $this->isAdmin($user);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasPermissionTo('admin') || $user->hasRole('admin');
    }
}
