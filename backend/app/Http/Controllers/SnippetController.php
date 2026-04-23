<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use Illuminate\Http\Request;

class SnippetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Snippet::where('project_id', request('project_id'))
            ->latest()
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        request()->validate([
            'project_id' => 'required|exists:projects,id',
            'folder_id' => 'nullable|exists:folders,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        return Snippet::create(request()->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Snippet $snippet)
    {
        return $snippet->load('tags');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Snippet $snippet)
    {
        request()->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $snippet->update(request()->only('title', 'content'));

        return $snippet;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Snippet $snippet)
    {
        $snippet->delete();
        return response()->json(['message' => 'deleted']);
    }
}
