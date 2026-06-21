<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;

// ─── K85: Root snippets (folder_id = null) list correctly ────────────────────

test('K85a: root snippets appear in listing when mixed with folder-placed snippets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['title' => 'root-snippet', 'folder_id' => null]);
    Snippet::factory()->for($project)->create(['title' => 'folder-snippet', 'folder_id' => $folder->id]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('root-snippet');
    expect($titles)->toContain('folder-snippet');
});

test('K85b: filtering by folder_id=null is not needed — all snippets appear without filter', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'root-a', 'folder_id' => null]);
    Snippet::factory()->for($project)->create(['title' => 'root-b', 'folder_id' => null]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('root-a');
    expect($titles)->toContain('root-b');
});

test('K85c: folder filter excludes root snippets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['title' => 'root-snippet', 'folder_id' => null]);
    Snippet::factory()->for($project)->create(['title' => 'folder-snippet', 'folder_id' => $folder->id]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/snippets?folder_id={$folder->id}")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('folder-snippet');
    expect($titles)->not->toContain('root-snippet');
});

test('K85d: root snippets have folder_id null in response', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $snippet = Snippet::factory()->for($project)->create(['folder_id' => null]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/snippets/{$snippet->id}")
        ->assertStatus(200)
        ->json('data');

    expect($data['folder_id'])->toBeNull();
});

// ─── K86: Moving folder with many nested children ────────────────────────────

test('K86a: moving a folder with nested children preserves tree integrity', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $root   = Folder::factory()->for($project)->create(['title' => 'root']);
    $child1 = Folder::factory()->for($project)->create(['title' => 'child1', 'parent_id' => $root->id]);
    $child2 = Folder::factory()->for($project)->create(['title' => 'child2', 'parent_id' => $root->id]);
    $grand1 = Folder::factory()->for($project)->create(['title' => 'grand1', 'parent_id' => $child1->id]);

    $dest = Folder::factory()->for($project)->create(['title' => 'destination']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$root->id}", ['parent_id' => $dest->id])
        ->assertStatus(200);

    expect($root->fresh()->parent_id)->toBe($dest->id);
    expect($child1->fresh()->parent_id)->toBe($root->id);
    expect($child2->fresh()->parent_id)->toBe($root->id);
    expect($grand1->fresh()->parent_id)->toBe($child1->id);
});

test('K86b: cannot move folder into its own descendant (cycle prevention)', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $parent = Folder::factory()->for($project)->create();
    $child  = Folder::factory()->for($project)->create(['parent_id' => $parent->id]);
    $grand  = Folder::factory()->for($project)->create(['parent_id' => $child->id]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$parent->id}", ['parent_id' => $grand->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('K86c: moving folder to top level (parent_id null) works', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $parent = Folder::factory()->for($project)->create();
    $child  = Folder::factory()->for($project)->create(['parent_id' => $parent->id]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$child->id}", ['parent_id' => null])
        ->assertStatus(200);

    expect($child->fresh()->parent_id)->toBeNull();
});

test('K86d: snippets inside moved folder retain their folder_id', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $folderA = Folder::factory()->for($project)->create();
    $folderB = Folder::factory()->for($project)->create();
    $snippet = Snippet::factory()->for($project)->create(['folder_id' => $folderA->id]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$folderA->id}", ['parent_id' => $folderB->id])
        ->assertStatus(200);

    expect($snippet->fresh()->folder_id)->toBe($folderA->id);
});

// ─── K87: Tag deletion when attached to many snippets ────────────────────────

test('K87a: deleting a tag removes it from all attached snippets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($user)->create();

    $snippets = Snippet::factory()->count(5)->for($project)->create();
    foreach ($snippets as $snippet) {
        $snippet->tags()->attach($tag->id);
    }

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tags/{$tag->id}")
        ->assertStatus(200);

    foreach ($snippets as $snippet) {
        expect($snippet->fresh()->tags)->toHaveCount(0);
    }
});

test('K87b: deleting a tag does not affect other tags on the same snippets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $tagA = Tag::factory()->for($user)->create(['name' => 'tag-a']);
    $tagB = Tag::factory()->for($user)->create(['name' => 'tag-b']);

    $snippet = Snippet::factory()->for($project)->create();
    $snippet->tags()->attach([$tagA->id, $tagB->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tags/{$tagA->id}")
        ->assertStatus(200);

    $remaining = $snippet->fresh()->tags->pluck('name');
    expect($remaining)->not->toContain('tag-a');
    expect($remaining)->toContain('tag-b');
});

test('K87c: tag pivot records are removed from database on tag deletion', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($user)->create();

    $snippet = Snippet::factory()->for($project)->create();
    $snippet->tags()->attach($tag->id);

    expect(\Illuminate\Support\Facades\DB::table('snippet_tag')->where('tag_id', $tag->id)->count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tags/{$tag->id}")
        ->assertStatus(200);

    expect(\Illuminate\Support\Facades\DB::table('snippet_tag')->where('tag_id', $tag->id)->count())->toBe(0);
});
