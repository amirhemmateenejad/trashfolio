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
use OpenApi\Attributes as OA;

class TagController extends Controller
{
    use AuthorizesRequests;

    /**
     * List all tags for the authenticated user.
     */
    #[OA\Get(
        path: '/tags',
        summary: 'List user tags',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        responses: [
            new OA\Response(response: 200, description: 'List of tags'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $tags = $request->user()->tags()->orderBy('name')->get();

        return TagResource::collection($tags);
    }

    /**
     * Create a new tag for the authenticated user.
     */
    #[OA\Post(
        path: '/tags',
        summary: 'Create a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'color', type: 'string', example: '#ff0000'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tag created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    /**
     * Update an existing tag's name or color.
     */
    #[OA\Put(
        path: '/tags/{id}',
        summary: 'Update a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'color', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated tag'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    /**
     * Delete a tag (detaches from all snippets).
     */
    #[OA\Delete(
        path: '/tags/{id}',
        summary: 'Delete a tag',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);
        $tag->delete();

        return ['message' => 'deleted'];
    }

    /**
     * Attach a tag to a snippet.
     */
    #[OA\Post(
        path: '/snippets/{snippet}/tags/{tag}',
        summary: 'Attach tag to snippet',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'snippet', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated snippet with tags'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function attach(Request $request, Snippet $snippet, Tag $tag)
    {
        $this->authorize('update', $snippet);

        if ($tag->user_id !== $request->user()->id) {
            abort(403, 'This tag does not belong to you.');
        }

        $snippet->tags()->syncWithoutDetaching([$tag->id]);

        return new SnippetResource($snippet->load('tags'));
    }

    /**
     * Detach a tag from a snippet.
     */
    #[OA\Delete(
        path: '/snippets/{snippet}/tags/{tag}',
        summary: 'Detach tag from snippet',
        security: [['bearerAuth' => []]],
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'snippet', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated snippet with tags'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
