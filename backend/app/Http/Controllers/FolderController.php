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

            if ($parent->project_id !== $project->id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['parent_id' => ['Parent folder must belong to the same project.']],
                ], 422);
            }
        }

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
    public function update(Request $request, Folder $folder)
    {
        $this->authorize('update', $folder);

        $validated = $request->validate([
            'title'     => 'sometimes|required|string|max:255',
            'parent_id' => 'sometimes|nullable|exists:folders,id',
        ]);

        if (array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null) {
            $newParentId = $validated['parent_id'];

            if ($newParentId === $folder->id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['parent_id' => ['A folder cannot be its own parent.']],
                ], 422);
            }

            $newParent = Folder::findOrFail($newParentId);

            if ($newParent->project_id !== $folder->project_id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['parent_id' => ['Parent folder must belong to the same project.']],
                ], 422);
            }

            if ($folder->isAncestorOf($newParentId)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['parent_id' => ['Moving this folder here would create a cycle.']],
                ], 422);
            }
        }

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
