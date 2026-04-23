<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
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

        // Policy (بعداً اضافه می‌کنیم)
        // $this->authorize('restore', $model);

        $model->restore();

        return response()->json(['message' => 'restored']);
    }

    public function empty()
    {
        $userId = auth()->id();

        Project::onlyTrashed()
            ->where('user_id', $userId)
            ->get()->each->forceDelete();

        Folder::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get()->each->forceDelete();

        Snippet::onlyTrashed()
            ->whereHas('project', fn($q) => $q->where('user_id', $userId))
            ->get()->each->forceDelete();

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
