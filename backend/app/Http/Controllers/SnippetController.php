<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SnippetController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $request->validate([
            'folder_id' => 'nullable|integer|exists:folders,id',
            'language'  => 'nullable|string|max:50',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = min($request->integer('per_page', 20), 100);

        $snippets = Snippet::query()
            ->whereHas('project', fn($q) => $q->where('user_id', $request->user()->id))
            ->when($request->filled('folder_id'), fn($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('language'), fn($q) => $q->where('language', $request->string('language')))
            ->with('tags')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($snippets);
    }

    public function indexForProject(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'folder_id' => 'nullable|integer|exists:folders,id',
            'language'  => 'nullable|string|max:50',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = min($request->integer('per_page', 20), 100);

        $snippets = Snippet::query()
            ->where('project_id', $project->id)
            ->when($request->filled('folder_id'), fn($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('language'), fn($q) => $q->where('language', $request->string('language')))
            ->with('tags')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($snippets);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'folder_id'  => 'nullable|exists:folders,id',
            'title'      => 'required|string|max:255',
            'content'    => 'required|string',
            'language'   => 'nullable|string|max:50',
            'tag_ids'    => 'nullable|array',
            'tag_ids.*'  => 'integer|exists:tags,id',
            'tag_names'  => 'nullable|array',
            'tag_names.*' => 'string|max:50',
        ]);

        $project = Project::findOrFail($data['project_id']);

        if ($project->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!empty($data['folder_id'])) {
            $folder = Folder::findOrFail($data['folder_id']);
            if ($folder->project_id !== $project->id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['folder_id' => ['Folder does not belong to the specified project.']],
                ], 422);
            }
        }

        $snippet = Snippet::create([
            'project_id' => $data['project_id'],
            'folder_id'  => $data['folder_id'] ?? null,
            'title'      => $data['title'],
            'content'    => $data['content'],
            'language'   => $data['language'] ?? null,
        ]);

        $tagIds = $this->resolveTagIds($request->user(), $data);

        if (!empty($tagIds)) {
            $snippet->tags()->sync($tagIds);
        }

        return response()->json($snippet->load('tags'), 201);
    }

    public function show(Snippet $snippet)
    {
        $this->authorize('view', $snippet);

        return response()->json($snippet->load('tags'));
    }

    public function update(Request $request, Snippet $snippet)
    {
        $this->authorize('update', $snippet);

        $data = $request->validate([
            'title'      => 'sometimes|required|string|max:255',
            'content'    => 'sometimes|required|string',
            'language'   => 'nullable|string|max:50',
            'folder_id'  => 'nullable|exists:folders,id',
            'tag_ids'    => 'nullable|array',
            'tag_ids.*'  => 'integer|exists:tags,id',
            'tag_names'  => 'nullable|array',
            'tag_names.*' => 'string|max:50',
        ]);

        if (array_key_exists('folder_id', $data) && $data['folder_id'] !== null) {
            $folder = Folder::findOrFail($data['folder_id']);
            if ($folder->project_id !== $snippet->project_id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => ['folder_id' => ['Folder does not belong to the snippet\'s project.']],
                ], 422);
            }
        }

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
            $tagIds = $this->resolveTagIds($request->user(), $data);
            $snippet->tags()->sync($tagIds);
        }

        return response()->json($snippet->load('tags'));
    }

    public function destroy(Snippet $snippet)
    {
        $this->authorize('delete', $snippet);
        $snippet->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q'          => 'required|string|max:200',
            'project_id' => 'nullable|integer',
            'folder_id'  => 'nullable|integer',
            'tag_ids'    => 'nullable|array',
            'tag_ids.*'  => 'integer',
            'language'   => 'nullable|string|max:50',
        ]);

        $userId = $request->user()->id;
        $filters = ["user_id = $userId"];

        if ($request->project_id) {
            $filters[] = "project_id = {$request->project_id}";
        }
        if ($request->folder_id) {
            $filters[] = "folder_id = {$request->folder_id}";
        }

        $filterString = implode(' AND ', $filters);

        $results = Snippet::search($request->q, function ($meili, $query, $options) use ($filterString) {
            $options['filter'] = $filterString;
            return $meili->search($query, $options);
        })->paginate(20);

        return response()->json($results);
    }

    private function resolveTagIds($user, array $data): array
    {
        $tagIds = [];

        if (!empty($data['tag_ids'])) {
            $tags = Tag::whereIn('id', $data['tag_ids'])->get();

            foreach ($tags as $tag) {
                if ($tag->user_id !== $user->id) {
                    abort(403, 'One or more tags do not belong to you.');
                }
                $tagIds[] = $tag->id;
            }

            if (count($tagIds) !== count($data['tag_ids'])) {
                abort(422, 'One or more tag IDs are invalid.');
            }
        }

        if (!empty($data['tag_names'])) {
            foreach ($data['tag_names'] as $name) {
                $slug = \Illuminate\Support\Str::slug($name);
                $tag = $user->tags()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'slug' => $slug]
                );
                $tagIds[] = $tag->id;
            }
        }

        return array_unique($tagIds);
    }
}
