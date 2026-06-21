<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutocompleteRequest;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;

class AutocompleteController extends Controller
{
    public function __invoke(AutocompleteRequest $request)
    {
        $user  = $request->user();
        $q     = $request->validated('q');
        $types = $request->validated('types') ?? ['snippet', 'tag', 'project'];
        $limit = $request->validated('limit') ?? 5;

        $results = [];

        if (in_array('snippet', $types)) {
            $results['snippets'] = Snippet::query()
                ->whereHas('project', fn($pq) => $pq->where('user_id', $user->id))
                ->where('title', 'like', "%{$q}%")
                ->select(['id', 'title', 'language', 'project_id', 'folder_id'])
                ->orderBy('title')
                ->limit($limit)
                ->get();
        }

        if (in_array('tag', $types)) {
            $results['tags'] = Tag::query()
                ->where('user_id', $user->id)
                ->where('name', 'like', "%{$q}%")
                ->select(['id', 'name', 'slug', 'color'])
                ->orderBy('name')
                ->limit($limit)
                ->get();
        }

        if (in_array('project', $types)) {
            $results['projects'] = Project::query()
                ->where('user_id', $user->id)
                ->where('title', 'like', "%{$q}%")
                ->select(['id', 'title', 'description'])
                ->orderBy('title')
                ->limit($limit)
                ->get();
        }

        return $results;
    }
}
