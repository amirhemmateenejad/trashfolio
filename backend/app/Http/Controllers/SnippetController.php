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
            ->whereHas('project', fn($q) => $q->where('user_id', $request->user()->id))
            ->orWhereHas('folder.project', fn($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($snippets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $this->authorize('create', Snippet::class);

        $data = request()->validate([
            'project_id' => 'required_without:folder_id|exists:projects,id',
            'folder_id'  => 'required_without:project_id|exists:folders,id',
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
        ]);

        $snippet = Snippet::create($data);

        return $snippet->load('tags');
    }

    /**
     * Display the specified resource.
     */
    public function show(Snippet $snippet)
    {
        $this->authorize('view', $snippet);
        return $snippet->load('tags');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Snippet $snippet)
    {
        $this->authorize('update', $snippet);

        request()->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $snippet->update(request()->only('title', 'content'));

        return $snippet->load('tags');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Snippet $snippet)
    {
        $this->authorize('delete', $snippet);
        $snippet->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:200',
            'project_id' => 'nullable|integer',
            'folder_id'  => 'nullable|integer',
        ]);

        $userId = auth()->id();

        $filters = "user_id = $userId";

        if ($request->project_id) {
            $filters .= " AND project_id = {$request->project_id}";
        }

        if ($request->folder_id) {
            $filters .= " AND folder_id = {$request->folder_id}";
        }

        $results = Snippet::search($request->q, function ($meili, $query, $options) use ($filters) {
            $options['filter'] = $filters;
            return $meili->search($query, $options);
        })->paginate(20);

        return response()->json($results);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:100',
        ]);

        $userId = auth()->id();

        return Snippet::search($request->q, function ($meili, $query, $options) use ($userId) {
            $options['filter'] = "user_id = $userId";
            $options['limit'] = 5;
            return $meili->search($query, $options);
        })
            ->take(5)
            ->get()
            ->map(fn($s) => [
                'id'    => $s->id,
                'title' => $s->title,
            ]);
    }
}
