<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;

// ─── G73: End-to-end cross-user isolation across all resources ────────────────

test('G73a: user A cannot read user B\'s project', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $project = Project::factory()->for($userB)->create();

    $this->actingAs($userA, 'sanctum')
        ->getJson("/api/projects/{$project->id}")
        ->assertStatus(403);
});

test('G73b: user A cannot read user B\'s folder', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $project = Project::factory()->for($userB)->create();
    $folder  = Folder::factory()->for($project)->create();

    $this->actingAs($userA, 'sanctum')
        ->getJson("/api/folders/{$folder->id}")
        ->assertStatus(403);
});

test('G73c: user A cannot read user B\'s snippet', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $project = Project::factory()->for($userB)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $this->actingAs($userA, 'sanctum')
        ->getJson("/api/snippets/{$snippet->id}")
        ->assertStatus(403);
});

test('G73d: GET /api/snippets returns only user A\'s snippets', function () {
    [$userA, $userB] = User::factory()->count(2)->create();

    $projA = Project::factory()->for($userA)->create();
    $projB = Project::factory()->for($userB)->create();
    Snippet::factory()->for($projA)->create(['title' => 'snippet-a']);
    Snippet::factory()->for($projB)->create(['title' => 'snippet-b']);

    $data = $this->actingAs($userA, 'sanctum')
        ->getJson('/api/snippets')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('snippet-a');
    expect($titles)->not->toContain('snippet-b');
});

test('G73e: GET /api/tags returns only user A\'s tags', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    Tag::factory()->for($userA)->create(['name' => 'tag-a']);
    Tag::factory()->for($userB)->create(['name' => 'tag-b']);

    $data = $this->actingAs($userA, 'sanctum')
        ->getJson('/api/tags')
        ->assertStatus(200)
        ->json();

    $names = collect($data['data'] ?? $data)->pluck('name');
    expect($names)->toContain('tag-a');
    expect($names)->not->toContain('tag-b');
});

test('G73f: GET /api/trash does not expose other users\' trashed items', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $project = Project::factory()->for($userB)->create(['title' => 'b-project']);
    $project->delete();

    $data = $this->actingAs($userA, 'sanctum')
        ->getJson('/api/trash')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->not->toContain('b-project');
});

test('G73g: autocomplete does not leak other users\' data', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $projB = Project::factory()->for($userB)->create(['title' => 'b-proj']);
    Snippet::factory()->for($projB)->create(['title' => 'b-snippet']);
    Tag::factory()->for($userB)->create(['name' => 'b-tag']);

    $response = $this->actingAs($userA, 'sanctum')
        ->getJson('/api/autocomplete?q=b-')
        ->assertStatus(200)
        ->json();

    expect(collect($response['snippets'] ?? [])->pluck('title'))->not->toContain('b-snippet');
    expect(collect($response['tags'] ?? [])->pluck('name'))->not->toContain('b-tag');
    expect(collect($response['projects'] ?? [])->pluck('title'))->not->toContain('b-proj');
});

// ─── G74: Cross-project folder/snippet/tag mismatch tests ────────────────────

test('G74a: cannot create snippet with folder_id from a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/snippets', [
            'project_id' => $projectA->id,
            'folder_id'  => $folder->id,
            'title'      => 'cross',
            'content'    => 'x',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['folder_id']);
});

test('G74b: cannot create folder with parent_id from a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $parent   = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/folders', [
            'project_id' => $projectA->id,
            'parent_id'  => $parent->id,
            'title'      => 'child',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('G74c: cannot move folder to parent in a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($projectA)->create();
    $parent   = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$folder->id}", ['parent_id' => $parent->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('G74d: cannot move snippet to folder in a different project on update', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $snippet  = Snippet::factory()->for($projectA)->create();
    $folder   = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/snippets/{$snippet->id}", ['folder_id' => $folder->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['folder_id']);
});

test('G74e: cannot attach tag from a different user to a snippet', function () {
    [$userA, $userB] = User::factory()->count(2)->create();
    $project = Project::factory()->for($userA)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($userB)->create();

    $this->actingAs($userA, 'sanctum')
        ->postJson("/api/snippets/{$snippet->id}/tags/{$tag->id}")
        ->assertStatus(403);
});

// ─── G75: Defensive ID validation sweep ──────────────────────────────────────

test('G75a: non-existent project ID returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/projects/99999')
        ->assertStatus(404);
});

test('G75b: non-existent folder ID returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/folders/99999')
        ->assertStatus(404);
});

test('G75c: non-existent snippet ID returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets/99999')
        ->assertStatus(404);
});

test('G75d: non-existent tag in tag_ids fails validation with 422', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/snippets', [
            'project_id' => $project->id,
            'title'      => 'x',
            'content'    => 'x',
            'tag_ids'    => [99999],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids.0']);
});

test('G75e: string IDs in URL path that are not numeric return 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/projects/not-a-number')
        ->assertStatus(404);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets/not-a-number')
        ->assertStatus(404);
});

test('G75f: folder_id 0 is treated as invalid', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/snippets', [
            'project_id' => $project->id,
            'folder_id'  => 0,
            'title'      => 'x',
            'content'    => 'x',
        ])
        ->assertStatus(422);
});
