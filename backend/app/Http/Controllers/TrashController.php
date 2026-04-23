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
        $user = $request->user();
        $perPage = $request->integer('per_page', 20);
        $page = $request->integer('page', 1);

        $projects = Project::onlyTrashed()
            ->where('user_id', $user->id)
            ->get()->map(fn($x) => tap($x, fn() => $x->type = 'project'));

        $folders = Folder::onlyTrashed()
            ->where('user_id', $user->id)
            ->get()->map(fn($x) => tap($x, fn() => $x->type = 'folder'));

        $snippets = Snippet::onlyTrashed()
            ->where('user_id', $user->id)
            ->get()->map(fn($x) => tap($x, fn() => $x->type = 'snippet'));

        $combined = $projects
            ->merge($folders)
            ->merge($snippets)
            ->sortByDesc('deleted_at')
            ->values();

        $paginated = new LengthAwarePaginator(
            $combined->forPage($page, $perPage),
            $combined->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return response()->json($paginated);
    }

    public function restore($type, $id)
    {
        $model = $this->resolveModel($type)::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $model);

        $model->restore();

        return response()->json(['message' => 'restored']);
    }

    public function empty()
    {
        $userId = auth()->id();

        Project::onlyTrashed()
            ->where('user_id', $userId)
            ->get()->each(function($project){
                $this->authorize('forceDelete', $project);
                $project->forceDelete();
            });

        Folder::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get()->each(function($project){
                $this->authorize('forceDelete', $project);
                $project->forceDelete();
            });

        Snippet::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get()->each(function($project){
                $this->authorize('forceDelete', $project);
                $project->forceDelete();
            });

        return response()->json(['message' => 'trash emptied']);
    }

    private function resolveModel($type)
    {
        return match ($type) {
            'project' => Project::class,
            'folder' => Folder::class,
            'snippet' => Snippet::class,
            default => throw new NotFoundHttpException('invalid type'),
        };
    }
}
