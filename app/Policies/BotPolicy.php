<?php

namespace App\Policies;

use App\Models\Bot;
use App\Models\User;

class BotPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bot $bot): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $bot->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Bot $bot): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $bot->tenant_id;
    }

    public function delete(User $user, Bot $bot): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $bot->tenant_id;
    }
}
