<?php

namespace App\Policies;

use App\Models\Snippet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SnippetPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Snippet $snippet): bool
    {
        return $snippet->owner_user_id === $user->id;
    }

    public function update(User $user, Snippet $snippet): bool
    {
        return $snippet->owner_user_id === $user->id;
    }

    public function delete(User $user, Snippet $snippet): bool
    {
        return $snippet->owner_user_id === $user->id;
    }

    public function restore(User $user, Snippet $snippet): bool
    {
        return $snippet->owner_user_id === $user->id;
    }

    public function forceDelete(User $user, Snippet $snippet): bool
    {
        return $snippet->owner_user_id === $user->id;
    }
}
