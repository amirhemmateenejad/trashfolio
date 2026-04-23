<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_id'  => 'nullable|exists:folders,id',
            'title'      => 'required|string|max:255',
        ]);

        $project = Project::findOrFail($validated['project_id']);

        if ($validated['parent_id'] ?? false) {
            $parent = Folder::findOrFail($validated['parent_id']);

            if ($parent->project->user_id !== $request->user()->id || $parent->project_id !== $project->id) {
                abort(403, 'Invalid parent folder');
            }
        }

        $project = Project::find($validated['project_id']);

        $this->authorize('create', [Folder::class, $project]);

        $folder = Folder::create([
            'project_id' => $project->id,
            'parent_id'  => $validated['parent_id'] ?? null,
            'title'      => $validated['title'],
        ]);

        return response()->json($folder, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Folder $folder)
    {
        $this->authorize('view', $folder);
        return $folder->load(['children', 'snippets']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,Folder $folder)
    {
        $this->authorize('update', $folder);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $folder->update($validated);

        return response()->json($folder);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Folder $folder)
    {
        $this->authorize('delete', $folder);
        $folder->delete();
        return response()->json(['message' => 'deleted']);
    }
}
