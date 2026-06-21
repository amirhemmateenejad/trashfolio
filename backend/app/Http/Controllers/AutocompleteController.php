<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutocompleteRequest;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use OpenApi\Attributes as OA;

class AutocompleteController extends Controller
{
    /**
     * Return type-ahead suggestions for snippets, tags, and/or projects.
     */
    #[OA\Get(
        path: '/autocomplete',
        summary: 'Autocomplete suggestions',
        security: [['bearerAuth' => []]],
        tags: ['Autocomplete'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Autocomplete results keyed by type'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
