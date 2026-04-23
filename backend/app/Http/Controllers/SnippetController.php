<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SnippetController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Snippet::class);

        $perPage = $request->integer('per_page', 50);

        $snippets = Snippet::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($snippets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $data = request()->validate([
            'project_id' => 'required_without:folder_id|exists:projects,id',
            'folder_id'  => 'required_without:project_id|exists:folders,id',
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
        ]);

        return Snippet::create($data);
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
