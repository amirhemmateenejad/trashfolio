<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all soft-deleted items (projects, folders, snippets) for the user.
     */
    #[OA\Get(
        path: '/trash',
        summary: 'List trashed items',
        security: [['bearerAuth' => []]],
        tags: ['Trash'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of trashed items'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $user    = $request->user();
        $perPage = min($request->integer('per_page', 20), 100);
        $page    = max($request->integer('page', 1), 1);

        $projects = Project::onlyTrashed()
            ->where('user_id', $user->id)
            ->get()
            ->toBase()
            ->map(fn($p) => $this->formatItem($p, 'project'));

        // Folders/snippets resolve ownership through project (which may also be trashed)
        $folders = Folder::onlyTrashed()
            ->whereHas('project', fn($q) => $q->withTrashed()->where('user_id', $user->id))
            ->get()
            ->toBase()
            ->map(fn($f) => $this->formatItem($f, 'folder'));

        $snippets = Snippet::onlyTrashed()
            ->whereHas('project', fn($q) => $q->withTrashed()->where('user_id', $user->id))
            ->get()
            ->toBase()
            ->map(fn($s) => $this->formatItem($s, 'snippet'));

        $combined = $projects
            ->merge($folders)
            ->merge($snippets)
            ->sortByDesc('deleted_at')
            ->values();

        $paginated = new LengthAwarePaginator(
            $combined->forPage($page, $perPage)->values(),
            $combined->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $paginated;
    }

    /**
     * Restore a soft-deleted item by type and ID.
     */
    #[OA\Post(
        path: '/trash/{type}/{id}/restore',
        summary: 'Restore a trashed item',
        security: [['bearerAuth' => []]],
        tags: ['Trash'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['project', 'folder', 'snippet'])),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item restored'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function restore(Request $request, string $type, int $id)
    {
        $model = $this->findTrashedOrFail($type, $id);
        $this->authorize('restore', $model);
        $model->restore();

        return ['message' => 'restored', 'type' => $type, 'id' => $id];
    }

    /**
     * Permanently delete a trashed item by type and ID.
     */
    #[OA\Delete(
        path: '/trash/{type}/{id}',
        summary: 'Permanently delete a trashed item',
        security: [['bearerAuth' => []]],
        tags: ['Trash'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['project', 'folder', 'snippet'])),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item permanently deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Request $request, string $type, int $id)
    {
        $model = $this->findTrashedOrFail($type, $id);
        $this->authorize('forceDelete', $model);
        $model->forceDelete();

        return ['message' => 'permanently deleted', 'type' => $type, 'id' => $id];
    }

    /**
     * Permanently delete all trashed items belonging to the authenticated user.
     */
    #[OA\Delete(
        path: '/trash',
        summary: 'Empty trash',
        security: [['bearerAuth' => []]],
        tags: ['Trash'],
        responses: [
            new OA\Response(response: 200, description: 'Trash emptied'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function empty(Request $request)
    {
        $user = $request->user();

        Project::onlyTrashed()->where('user_id', $user->id)->get()
            ->each(function ($project) {
                $this->authorize('forceDelete', $project);
                $project->forceDelete();
            });

        Folder::onlyTrashed()
            ->whereHas('project', fn($q) => $q->withTrashed()->where('user_id', $user->id))
            ->get()
            ->each(function ($folder) {
                $this->authorize('forceDelete', $folder);
                $folder->forceDelete();
            });

        Snippet::onlyTrashed()
            ->whereHas('project', fn($q) => $q->withTrashed()->where('user_id', $user->id))
            ->get()
            ->each(function ($snippet) {
                $this->authorize('forceDelete', $snippet);
                $snippet->forceDelete();
            });

        return ['message' => 'trash emptied'];
    }

    /**
     * Find a soft-deleted model by type and ID or throw 404.
     *
     * @param string $type
     * @param int $id
     * @return Project|Folder|Snippet
     */
    private function findTrashedOrFail(string $type, int $id): Project|Folder|Snippet
    {
        $class = match ($type) {
            'project' => Project::class,
            'folder'  => Folder::class,
            'snippet' => Snippet::class,
            default   => throw new NotFoundHttpException('Invalid trash type.'),
        };

        return $class::onlyTrashed()->findOrFail($id);
    }

    /**
     * Format a model into a unified trash item array.
     *
     * @param Project|Folder|Snippet $model
     * @param string $type
     * @return array
     */
    private function formatItem(Project|Folder|Snippet $model, string $type): array
    {
        return [
            'type'       => $type,
            'id'         => $model->id,
            'title'      => $model->title,
            'deleted_at' => $model->deleted_at,
        ];
    }
}
