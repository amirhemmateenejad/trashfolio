<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Resolve a final list of tag IDs from explicit IDs and/or name strings.
     * Assumes tag_ids have already been validated as existing and user-owned.
     * tag_names are auto-created for the user if they don't exist.
     */
    public function resolveIds(User $user, array $tagIds = [], array $tagNames = []): array
    {
        $ids = array_map('intval', $tagIds);

        foreach ($tagNames as $name) {
            $slug = Str::slug($name);
            $tag = $user->tags()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
            $ids[] = $tag->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Resolve tag names from IDs belonging to the given user.
     * IDs belonging to other users are silently ignored.
     */
    public function resolveNamesForUser(User $user, array $tagIds): array
    {
        if (empty($tagIds)) {
            return [];
        }

        return Tag::whereIn('id', $tagIds)
            ->where('user_id', $user->id)
            ->pluck('name')
            ->toArray();
    }
}
