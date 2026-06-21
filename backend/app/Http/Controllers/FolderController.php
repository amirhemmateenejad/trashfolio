<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

class FolderController extends Controller
{
    use AuthorizesRequests;

    /**
     * Create a new folder within a project.
     */
    #[OA\Post(
        path: '/folders',
        summary: 'Create a folder',
        security: [['bearerAuth' => []]],
        tags: ['Folders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['project_id', 'title'],
                properties: [
                    new OA\Property(property: 'project_id', type: 'integer'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'parent_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Folder created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreFolderRequest $request)
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $this->authorize('create', [Folder::class, $project]);

        $folder = Folder::create($request->validated());

        return (new FolderResource($folder))->response()->setStatusCode(201);
    }

    /**
     * Get a single folder with its children and snippets.
     */
    #[OA\Get(
        path: '/folders/{id}',
        summary: 'Get a folder',
        security: [['bearerAuth' => []]],
        tags: ['Folders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Folder detail'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Folder $folder)
    {
        $this->authorize('view', $folder);

        return new FolderResource($folder->load(['children.snippets', 'snippets']));
    }

    /**
     * Update an existing folder.
     */
    #[OA\Put(
        path: '/folders/{id}',
        summary: 'Update a folder',
        security: [['bearerAuth' => []]],
        tags: ['Folders'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'parent_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Updated folder'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateFolderRequest $request, Folder $folder)
    {
        $this->authorize('update', $folder);

        $folder->update($request->validated());

        return new FolderResource($folder);
    }

    /**
     * Soft-delete a folder (cascades to children and snippets).
     */
    #[OA\Delete(
        path: '/folders/{id}',
        summary: 'Delete a folder',
        security: [['bearerAuth' => []]],
        tags: ['Folders'],
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
    public function destroy(Folder $folder)
    {
        $this->authorize('delete', $folder);
        $folder->delete();

        return ['message' => 'deleted'];
    }
}
