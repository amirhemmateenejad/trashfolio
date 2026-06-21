<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use App\Models\Tag;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'q'          => 'required|string|max:200',
            'project_id' => 'nullable|integer|exists:projects,id',
            'folder_id'  => 'nullable|integer|exists:folders,id',
            'tag_ids'    => 'nullable|array',
            'tag_ids.*'  => 'integer|exists:tags,id',
            'language'   => 'nullable|string|max:50',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $user    = $request->user();
        $perPage = $validated['per_page'] ?? 20;

        // Resolve tag names for this user (ignores tag_ids that don't belong to them)
        $tagNames = [];
        if (!empty($validated['tag_ids'])) {
            $tagNames = Tag::whereIn('id', $validated['tag_ids'])
                ->where('user_id', $user->id)
                ->pluck('name')
                ->toArray();
        }

        $results = Snippet::search($validated['q'])
            // Meilisearch-specific filter string (used by MeilisearchEngine via ->options())
            ->options(['filter' => $this->buildMeilisearchFilter($user->id, $validated, $tagNames)])
            // SQL-side constraints: enforce ownership and optional filters for all drivers
            ->query(function ($query) use ($user, $validated, $tagNames) {
                $query->whereHas('project', fn($q) => $q->where('user_id', $user->id))
                    ->with('tags');

                if (!empty($validated['project_id'])) {
                    $query->where('project_id', $validated['project_id']);
                }

                if (!empty($validated['folder_id'])) {
                    $query->where('folder_id', $validated['folder_id']);
                }

                if (!empty($validated['language'])) {
                    $query->where('language', $validated['language']);
                }

                if (!empty($validated['tag_ids'])) {
                    if (!empty($tagNames)) {
                        $query->whereHas('tags', fn($q) => $q->whereIn('name', $tagNames));
                    } else {
                        // All requested tag_ids belong to other users — no results possible
                        $query->whereRaw('0 = 1');
                    }
                }
            })
            ->paginate($perPage);

        return response()->json($results);
    }

    private function buildMeilisearchFilter(int $userId, array $params, array $tagNames): string
    {
        $parts = ["user_id = {$userId}"];

        if (!empty($params['project_id'])) {
            $parts[] = "project_id = {$params['project_id']}";
        }

        if (!empty($params['folder_id'])) {
            $parts[] = "folder_id = {$params['folder_id']}";
        }

        if (!empty($params['language'])) {
            $escaped = addslashes($params['language']);
            $parts[] = "language = \"{$escaped}\"";
        }

        if (!empty($tagNames)) {
            $list = implode(', ', array_map(fn($n) => '"' . addslashes($n) . '"', $tagNames));
            $parts[] = "tags IN [{$list}]";
        }

        return implode(' AND ', $parts);
    }
}
