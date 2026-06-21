<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\SnippetResource;
use App\Models\Snippet;
use App\Services\TagService;
use OpenApi\Attributes as OA;

class SearchController extends Controller
{
    public function __construct(private TagService $tagService) {}

    /**
     * Full-text search across the user's snippets via Meilisearch.
     */
    #[OA\Get(
        path: '/search',
        summary: 'Search snippets',
        security: [['bearerAuth' => []]],
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'folder_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'language', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated search results'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(SearchRequest $request)
    {
        $data     = $request->validated();
        $user     = $request->user();
        $perPage  = $data['per_page'] ?? 20;
        $tagNames = $this->tagService->resolveNamesForUser($user, $data['tag_ids'] ?? []);

        $results = Snippet::search($data['q'])
            ->options(['filter' => $this->buildMeilisearchFilter($user->id, $data, $tagNames)])
            ->query(function ($query) use ($user, $data, $tagNames) {
                $query->whereHas('project', fn($q) => $q->where('user_id', $user->id))
                      ->with('tags');

                if (!empty($data['project_id'])) {
                    $query->where('project_id', $data['project_id']);
                }
                if (!empty($data['folder_id'])) {
                    $query->where('folder_id', $data['folder_id']);
                }
                if (!empty($data['language'])) {
                    $query->where('language', $data['language']);
                }
                if (!empty($data['tag_ids'])) {
                    if ($tagNames) {
                        $query->whereHas('tags', fn($q) => $q->whereIn('name', $tagNames));
                    } else {
                        $query->whereRaw('0 = 1');
                    }
                }
            })
            ->paginate($perPage);

        return SnippetResource::collection($results);
    }

    /**
     * Build a Meilisearch filter string from user ID and request parameters.
     *
     * @param int $userId
     * @param array $params
     * @param array $tagNames
     * @return string
     */
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
            $parts[] = 'language = "' . addslashes($params['language']) . '"';
        }
        if ($tagNames) {
            $list    = implode(', ', array_map(fn($n) => '"' . addslashes($n) . '"', $tagNames));
            $parts[] = "tags IN [{$list}]";
        }

        return implode(' AND ', $parts);
    }
}
