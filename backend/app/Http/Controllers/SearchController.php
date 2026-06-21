<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Resources\SnippetResource;
use App\Models\Snippet;
use App\Services\TagService;

class SearchController extends Controller
{
    public function __construct(private TagService $tagService) {}

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
