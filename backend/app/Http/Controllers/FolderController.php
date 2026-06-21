<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FolderController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreFolderRequest $request)
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $this->authorize('create', [Folder::class, $project]);

        $folder = Folder::create($request->validated());

        return (new FolderResource($folder))->response()->setStatusCode(201);
    }

    public function show(Folder $folder)
    {
        $this->authorize('view', $folder);

        return new FolderResource($folder->load(['children', 'snippets']));
    }

    public function update(UpdateFolderRequest $request, Folder $folder)
    {
        $this->authorize('update', $folder);

        $folder->update($request->validated());

        return new FolderResource($folder);
    }

    public function destroy(Folder $folder)
    {
        $this->authorize('delete', $folder);
        $folder->delete();

        return ['message' => 'deleted'];
    }
}
