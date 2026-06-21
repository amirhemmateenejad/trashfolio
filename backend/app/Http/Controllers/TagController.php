<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Tag::class);

        return $request->user()->tags()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tag::class);

        $validated = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name']);

        if ($request->user()->tags()->where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['name' => ['A tag with this name already exists.']],
            ], 422);
        }

        $tag = $request->user()->tags()->create([
            'name'  => $validated['name'],
            'slug'  => $slug,
            'color' => $validated['color'] ?? null,
        ]);

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:50',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if (isset($validated['name'])) {
            $slug = \Illuminate\Support\Str::slug($validated['name']);

            $exists = $request->user()->tags()
                ->where('slug', $slug)
                ->where('id', '!=', $tag->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['name' => ['A tag with this name already exists.']],
                ], 422);
            }

            $validated['slug'] = $slug;
        }

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $tag->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function attach(Request $request, Snippet $snippet, Tag $tag)
    {
        $this->authorize('update', $snippet);

        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'This tag does not belong to you.');
        }

        $snippet->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json($snippet->load('tags'));
    }

    public function detach(Request $request, Snippet $snippet, Tag $tag)
    {
        $this->authorize('update', $snippet);

        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'This tag does not belong to you.');
        }

        $snippet->tags()->detach($tag->id);

        return response()->json($snippet->load('tags'));
    }
}
