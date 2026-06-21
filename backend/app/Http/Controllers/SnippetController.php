<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSnippetsRequest;
use App\Http\Requests\StoreSnippetRequest;
use App\Http\Requests\UpdateSnippetRequest;
use App\Http\Resources\SnippetResource;
use App\Models\Project;
use App\Models\Snippet;
use App\Services\TagService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SnippetController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TagService $tagService) {}

    public function index(ListSnippetsRequest $request)
    {
        $perPage = min($request->integer('per_page', 20), 100);

        $snippets = Snippet::query()
            ->whereHas('project', fn($q) => $q->where('user_id', $request->user()->id))
            ->when($request->filled('folder_id'), fn($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('language'), fn($q) => $q->where('language', $request->string('language')))
            ->with('tags')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return SnippetResource::collection($snippets);
    }

    public function indexForProject(ListSnippetsRequest $request, Project $project)
    {
        $this->authorize('view', $project);

        $perPage = min($request->integer('per_page', 20), 100);

        $snippets = Snippet::query()
            ->where('project_id', $project->id)
            ->when($request->filled('folder_id'), fn($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('language'), fn($q) => $q->where('language', $request->string('language')))
            ->with('tags')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return SnippetResource::collection($snippets);
    }

    public function store(StoreSnippetRequest $request)
    {
        $data    = $request->validated();
        $project = Project::findOrFail($data['project_id']);

        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        // tag_ids ownership check (403 for foreign tags, consistent with attach/detach behavior)
        $this->assertTagsOwnedByUser($request->user(), $data['tag_ids'] ?? []);

        $snippet = Snippet::create([
            'project_id' => $data['project_id'],
            'folder_id'  => $data['folder_id'] ?? null,
            'title'      => $data['title'],
            'content'    => $data['content'],
            'language'   => $data['language'] ?? null,
        ]);

        $tagIds = $this->tagService->resolveIds($request->user(), $data['tag_ids'] ?? [], $data['tag_names'] ?? []);

        if ($tagIds) {
            $snippet->tags()->sync($tagIds);
        }

        return (new SnippetResource($snippet->load('tags')))->response()->setStatusCode(201);
    }

    public function show(Snippet $snippet)
    {
        $this->authorize('view', $snippet);

        return new SnippetResource($snippet->load('tags'));
    }

    public function update(UpdateSnippetRequest $request, Snippet $snippet)
    {
        $this->authorize('update', $snippet);

        $data = $request->validated();

        $this->assertTagsOwnedByUser($request->user(), $data['tag_ids'] ?? []);

        $snippet->update(array_filter([
            'title'     => $data['title'] ?? null,
            'content'   => $data['content'] ?? null,
            'language'  => $data['language'] ?? null,
            'folder_id' => array_key_exists('folder_id', $data) ? $data['folder_id'] : $snippet->folder_id,
        ], fn($v) => $v !== null));

        if (array_key_exists('folder_id', $data)) {
            $snippet->folder_id = $data['folder_id'];
            $snippet->save();
        }

        if (array_key_exists('tag_ids', $data) || array_key_exists('tag_names', $data)) {
            $tagIds = $this->tagService->resolveIds($request->user(), $data['tag_ids'] ?? [], $data['tag_names'] ?? []);
            $snippet->tags()->sync($tagIds);
        }

        return new SnippetResource($snippet->load('tags'));
    }

    public function destroy(Snippet $snippet)
    {
        $this->authorize('delete', $snippet);
        $snippet->delete();

        return ['message' => 'deleted'];
    }

    private function assertTagsOwnedByUser($user, array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        $foreignCount = \App\Models\Tag::whereIn('id', $tagIds)
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($foreignCount > 0) {
            abort(403, 'One or more tags do not belong to you.');
        }
    }
}
