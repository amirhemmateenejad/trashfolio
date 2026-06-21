<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\User;

// ─── GET /api/snippets?folder_id= (D51) ──────────────────────────────────────

test('D51a: can filter snippets by folder_id', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    $inFolder  = Snippet::factory()->for($project)->create(['title' => 'In Folder', 'folder_id' => $folder->id]);
    $atRoot    = Snippet::factory()->for($project)->create(['title' => 'At Root', 'folder_id' => null]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/snippets?folder_id={$folder->id}")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('In Folder');
    expect($titles)->not->toContain('At Root');
});

test('D51b: folder_id filter returns only snippets in that folder', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folderA = Folder::factory()->for($project)->create();
    $folderB = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['title' => 'Snippet A', 'folder_id' => $folderA->id]);
    Snippet::factory()->for($project)->create(['title' => 'Snippet B', 'folder_id' => $folderB->id]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/snippets?folder_id={$folderA->id}")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('Snippet A');
    expect($titles)->not->toContain('Snippet B');
});

// ─── GET /api/snippets?language= (D52) ───────────────────────────────────────

test('D52a: can filter snippets by language', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'PHP Snippet', 'language' => 'php']);
    Snippet::factory()->for($project)->create(['title' => 'JS Snippet', 'language' => 'javascript']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets?language=php')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('PHP Snippet');
    expect($titles)->not->toContain('JS Snippet');
});

test('D52b: language filter is case-sensitive', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'PHP Upper', 'language' => 'PHP']);
    Snippet::factory()->for($project)->create(['title' => 'PHP Lower', 'language' => 'php']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/snippets?language=php')
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('PHP Lower');
    expect($titles)->not->toContain('PHP Upper');
});

test('D52c: can combine folder_id and language filters', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['title' => 'Match', 'folder_id' => $folder->id, 'language' => 'php']);
    Snippet::factory()->for($project)->create(['title' => 'Wrong Lang', 'folder_id' => $folder->id, 'language' => 'js']);
    Snippet::factory()->for($project)->create(['title' => 'Wrong Folder', 'folder_id' => null, 'language' => 'php']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/snippets?folder_id={$folder->id}&language=php")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('Match');
    expect($titles)->not->toContain('Wrong Lang');
    expect($titles)->not->toContain('Wrong Folder');
});

// ─── GET /api/projects/{project}/snippets (D50) ──────────────────────────────

test('D50a: project-scoped snippet listing returns only that project\'s snippets', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();

    Snippet::factory()->for($projectA)->create(['title' => 'Snippet A']);
    Snippet::factory()->for($projectB)->create(['title' => 'Snippet B']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$projectA->id}/snippets")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('Snippet A');
    expect($titles)->not->toContain('Snippet B');
});

test('D50b: project-scoped snippet listing requires ownership', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/snippets")
        ->assertStatus(403);
});

test('D50c: project-scoped snippet listing supports folder_id filter', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['title' => 'In Folder', 'folder_id' => $folder->id]);
    Snippet::factory()->for($project)->create(['title' => 'At Root', 'folder_id' => null]);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/snippets?folder_id={$folder->id}")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('In Folder');
    expect($titles)->not->toContain('At Root');
});

test('D50d: project-scoped snippet listing supports language filter', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'PHP', 'language' => 'php']);
    Snippet::factory()->for($project)->create(['title' => 'JS', 'language' => 'javascript']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/snippets?language=php")
        ->assertStatus(200)
        ->json('data');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('PHP');
    expect($titles)->not->toContain('JS');
});

test('D50e: project-scoped snippet listing is paginated', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    for ($i = 1; $i <= 5; $i++) {
        Snippet::factory()->for($project)->create(['title' => "Snippet {$i}"]);
    }

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/snippets?per_page=2")
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page']]);

    expect($response->json('meta.per_page'))->toBe(2);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(5);
    expect($response->json('data'))->toHaveCount(2);
});
