<?php

namespace App\Policies;

use App\Models\BillingDocument;
use App\Models\User;

class BillingDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin');
    }

    public function view(User $user, BillingDocument $document): bool
    {
        return $user->can('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('admin');
    }

    public function update(User $user, BillingDocument $document): bool
    {
        return $user->can('admin');
    }

    public function delete(User $user, BillingDocument $document): bool
    {
        return $user->can('admin') && ! $document->isPaid();
    }
}
