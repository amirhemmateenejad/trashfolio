<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\SnippetResource;
use App\Http\Resources\TagResource;
use App\Models\Snippet;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $tags = $request->user()->tags()->orderBy('name')->get();

        return TagResource::collection($tags);
    }

    public function store(StoreTagRequest $request)
    {
        $data = $request->validated();
        $slug = Str::slug($data['name']);

        $tag = $request->user()->tags()->create([
            'name'  => $data['name'],
            'slug'  => $slug,
            'color' => $data['color'] ?? null,
        ]);

        return (new TagResource($tag))->response()->setStatusCode(201);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $this->authorize('update', $tag);

        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return new TagResource($tag);
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $tag->delete();

        return ['message' => 'deleted'];
    }

    public function attach(Request $request, Snippet $snippet, Tag $tag)
    {
        $this->authorize('update', $snippet);

        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'This tag does not belong to you.');
        }

        $snippet->tags()->syncWithoutDetaching([$tag->id]);

        return new SnippetResource($snippet->load('tags'));
    }

    public function detach(Request $request, Snippet $snippet, Tag $tag)
    {
        $this->authorize('update', $snippet);

        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'This tag does not belong to you.');
        }

        $snippet->tags()->detach($tag->id);

        return new SnippetResource($snippet->load('tags'));
    }
}
