<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
    use AuthorizesRequests;

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

    public function restore(Request $request, string $type, int $id)
    {
        $model = $this->findTrashedOrFail($type, $id);
        $this->authorize('restore', $model);
        $model->restore();

        return ['message' => 'restored', 'type' => $type, 'id' => $id];
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $model = $this->findTrashedOrFail($type, $id);
        $this->authorize('forceDelete', $model);
        $model->forceDelete();

        return ['message' => 'permanently deleted', 'type' => $type, 'id' => $id];
    }

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
