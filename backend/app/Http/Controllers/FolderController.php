<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        request()->validate([
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:folders,id',
            'title' => 'required|string|max:255',
        ]);

        return Folder::create(request()->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Folder $folder)
    {
        return $folder->load(['children', 'snippets']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Folder $folder)
    {
        request()->validate([
            'title' => 'required|string|max:255',
        ]);

        $folder->update(request()->only('title'));

        return $folder;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Folder $folder)
    {
        $folder->delete();
        return response()->json(['message' => 'deleted']);
    }
}
