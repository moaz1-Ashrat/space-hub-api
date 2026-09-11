<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'space_owner';
    }

    public function update(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }

    public function delete(User $user, Space $space): bool
    {
        return $user->id === $space->user_id;
    }
}
