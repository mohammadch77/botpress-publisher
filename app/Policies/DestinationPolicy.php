<?php

namespace App\Policies;

use App\Models\Destination;
use App\Models\User;

class DestinationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Destination $destination): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $destination->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Destination $destination): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $destination->tenant_id;
    }

    public function delete(User $user, Destination $destination): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $destination->tenant_id;
    }
}
