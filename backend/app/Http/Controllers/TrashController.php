<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $userId = auth()->id();

        $projects = Project::onlyTrashed()
            ->where('user_id', $userId)
            ->get();

        $folders = Folder::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get();

        $snippets = Snippet::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get();

        return compact('projects', 'folders', 'snippets');
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
