<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProjectController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return auth()->user()->projects()->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        request()->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return response()->json(
            auth()->user()->projects()->create(request()->only('title', 'description')),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        return $project->load(['folders', 'snippets']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Project $project)
    {
        $this->authorize('update', $project);

        request()->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update(request()->only('title', 'description'));
        return $project;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json(['message' => 'deleted']);
    }
}
