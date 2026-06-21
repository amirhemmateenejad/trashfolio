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
        return $this->ownsFolder($user, $folder);
    }

    public function create(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->ownsFolder($user, $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->ownsFolder($user, $folder);
    }

    public function restore(User $user, Folder $folder): bool
    {
        return $this->ownsFolder($user, $folder);
    }

    public function forceDelete(User $user, Folder $folder): bool
    {
        return $this->ownsFolder($user, $folder);
    }

    private function ownsFolder(User $user, Folder $folder): bool
    {
        // The folder's project may itself be soft-deleted; withTrashed() ensures it's loaded
        $project = $folder->project()->withTrashed()->first();

        return $project && $project->user_id === $user->id;
    }
}
