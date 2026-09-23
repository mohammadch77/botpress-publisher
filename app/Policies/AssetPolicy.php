<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $asset->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $asset->tenant_id;
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $asset->tenant_id;
    }
}
