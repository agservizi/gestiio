<?php

namespace App\Policies;

use App\Models\FatturaProforma;
use App\Models\User;

class FatturaProformaPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasPermissionTo('agente');
    }

    public function view(User $user, FatturaProforma $fattura): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('agente') && $this->owns($user, $fattura);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum()->canDelete();
    }

    public function emit(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum()->canEmit();
    }

    public function sendEmail(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum()->canSendEmail();
    }

    public function markPaid(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum()->canMarkPaid();
    }

    public function regenerate(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum()->canRegenerate();
    }

    public function updateIntestazione(User $user, FatturaProforma $fattura): bool
    {
        return $this->isAdmin($user) && $fattura->statusEnum() !== \App\Enums\FatturaProformaStatus::PAGATA;
    }

    protected function owns(User $user, FatturaProforma $fattura): bool
    {
        $intestazione = $fattura->intestazione;

        return $intestazione && (int) $intestazione->user_id === (int) $user->id;
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasPermissionTo('admin') || $user->hasRole('admin');
    }
}
