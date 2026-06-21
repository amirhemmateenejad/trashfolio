<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ─── J81: N+1 on snippet listing with tags ────────────────────────────────────

test('J81: snippet listing with tags does not N+1 as snippet count grows', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    // 5 snippets each with 2 tags
    Snippet::factory()->count(5)->for($project)->create()
        ->each(fn($s) => $s->tags()->attach(Tag::factory()->for($user)->count(2)->create()));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson('/api/snippets');
    $countAt5 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // 10 more snippets (15 total)
    Snippet::factory()->count(10)->for($project)->create()
        ->each(fn($s) => $s->tags()->attach(Tag::factory()->for($user)->count(2)->create()));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson('/api/snippets');
    $countAt15 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // N+1 would produce ~1 extra query per additional snippet (10 extra here).
    // Eager loading keeps the delta well below that.
    expect($countAt15)->toBeLessThan($countAt5 + 10);
});

test('J81b: project-scoped snippet listing also avoids N+1', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->count(5)->for($project)->create()
        ->each(fn($s) => $s->tags()->attach(Tag::factory()->for($user)->count(2)->create()));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson("/api/projects/{$project->id}/snippets");
    $countAt5 = count(DB::getQueryLog());
    DB::disableQueryLog();

    Snippet::factory()->count(10)->for($project)->create()
        ->each(fn($s) => $s->tags()->attach(Tag::factory()->for($user)->count(2)->create()));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson("/api/projects/{$project->id}/snippets");
    $countAt15 = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($countAt15)->toBeLessThan($countAt5 + 10);
});

// ─── J82: N+1 on folder tree listing ─────────────────────────────────────────

test('J82: folder show with children and snippets does not N+1', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $parent  = Folder::factory()->for($project)->create();

    // 3 child folders, each with 2 snippets
    Folder::factory()->count(3)->for($project)->create(['parent_id' => $parent->id])
        ->each(fn($f) => Snippet::factory()->count(2)->for($project)->create(['folder_id' => $f->id]));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson("/api/folders/{$parent->id}");
    $countWith3 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Add 5 more children with snippets
    Folder::factory()->count(5)->for($project)->create(['parent_id' => $parent->id])
        ->each(fn($f) => Snippet::factory()->count(2)->for($project)->create(['folder_id' => $f->id]));

    DB::enableQueryLog();
    $this->actingAs($user, 'sanctum')->getJson("/api/folders/{$parent->id}");
    $countWith8 = count(DB::getQueryLog());
    DB::disableQueryLog();

    // N+1 would produce ~1 extra query per additional child folder (5 extra here).
    // Eager loading keeps the delta well below that.
    expect($countWith8)->toBeLessThanOrEqual($countWith3 + 5);
});

// ─── J84: Full-text index migration verification ──────────────────────────────

test('J84: Meilisearch index settings are configured with required filterable attributes', function () {
    $config = config('scout.meilisearch.index-settings.snippets');

    expect($config)->toBeArray();

    $filterable = $config['filterableAttributes'] ?? [];
    expect($filterable)->toContain('user_id');
    expect($filterable)->toContain('project_id');
    expect($filterable)->toContain('folder_id');
    expect($filterable)->toContain('language');
    expect($filterable)->toContain('tags');

    $searchable = $config['searchableAttributes'] ?? [];
    expect($searchable)->toContain('title');
    expect($searchable)->toContain('content');

    $sortable = $config['sortableAttributes'] ?? [];
    expect($sortable)->toContain('created_at');
});

test('J84b: Snippet toSearchableArray includes all required index fields', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create(['language' => 'php']);
    $tag     = Tag::factory()->for($user)->create(['name' => 'test-tag']);
    $snippet->tags()->attach($tag->id);

    $array = $snippet->fresh(['tags', 'project'])->toSearchableArray();

    expect($array)->toHaveKeys(['id', 'user_id', 'project_id', 'folder_id', 'title', 'content', 'language', 'tags', 'created_at']);
    expect($array['user_id'])->toBe($user->id);
    expect($array['language'])->toBe('php');
    expect($array['tags'])->toContain('test-tag');
});

test('J84c: soft-deleted snippets are excluded from search index', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();

    expect($snippet->shouldBeSearchable())->toBeTrue();

    $snippet->delete();
    $deleted = Snippet::onlyTrashed()->find($snippet->id);

    expect($deleted->shouldBeSearchable())->toBeFalse();
});
