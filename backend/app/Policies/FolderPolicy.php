<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Folder $folder): bool
    {
        return $folder->project->user_id === $user->id;
    }

    public function create(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function update(User $user, Folder $folder): bool
    {
        return $folder->project->user_id === $user->id;
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $folder->project->user_id === $user->id;
    }

    public function restore(User $user, Folder $folder): bool
    {
        return $folder->project->user_id === $user->id;
    }

    public function forceDelete(User $user, Folder $folder): bool
    {
        return false;
    }
}
