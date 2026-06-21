<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;

// ─── Auth ─────────────────────────────────────────────────────────────────────

test('autocomplete requires authentication', function () {
    $this->getJson('/api/autocomplete?q=test')->assertStatus(401);
});

test('autocomplete requires q parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

// ─── Snippet suggestions ───────────────────────────────────────────────────────

test('autocomplete returns matching snippets by title', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'PHP helpers']);
    Snippet::factory()->for($project)->create(['title' => 'Python script']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=PHP')
        ->assertStatus(200)
        ->json('snippets');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('PHP helpers');
    expect($titles)->not->toContain('Python script');
});

test('autocomplete snippet results include id, title, language, project_id', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'my-snippet', 'language' => 'php']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=my-snippet')
        ->assertStatus(200)
        ->json('snippets');

    expect($data[0])->toHaveKeys(['id', 'title', 'language', 'project_id']);
});

test('autocomplete does not return snippets from other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $otherProject = Project::factory()->for($other)->create();
    Snippet::factory()->for($otherProject)->create(['title' => 'secret-snippet']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=secret-snippet')
        ->assertStatus(200)
        ->json('snippets');

    expect(collect($data)->pluck('title'))->not->toContain('secret-snippet');
});

// ─── Tag suggestions ───────────────────────────────────────────────────────────

test('autocomplete returns matching tags by name', function () {
    $user = User::factory()->create();

    Tag::factory()->for($user)->create(['name' => 'laravel']);
    Tag::factory()->for($user)->create(['name' => 'linux']);
    Tag::factory()->for($user)->create(['name' => 'python']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=la')
        ->assertStatus(200)
        ->json('tags');

    $names = collect($data)->pluck('name');
    expect($names)->toContain('laravel');
    expect($names)->not->toContain('python');
});

test('autocomplete does not return tags from other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Tag::factory()->for($other)->create(['name' => 'private-tag']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=private-tag')
        ->assertStatus(200)
        ->json('tags');

    expect(collect($data)->pluck('name'))->not->toContain('private-tag');
});

// ─── Project suggestions ──────────────────────────────────────────────────────

test('autocomplete returns matching projects by title', function () {
    $user = User::factory()->create();

    Project::factory()->for($user)->create(['title' => 'Laravel API']);
    Project::factory()->for($user)->create(['title' => 'Vue Frontend']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=Laravel')
        ->assertStatus(200)
        ->json('projects');

    $titles = collect($data)->pluck('title');
    expect($titles)->toContain('Laravel API');
    expect($titles)->not->toContain('Vue Frontend');
});

test('autocomplete does not return projects from other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Project::factory()->for($other)->create(['title' => 'other-project']);

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=other-project')
        ->assertStatus(200)
        ->json('projects');

    expect(collect($data)->pluck('title'))->not->toContain('other-project');
});

// ─── Type filtering ────────────────────────────────────────────────────────────

test('autocomplete can be limited to specific types', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'match']);

    Snippet::factory()->for($project)->create(['title' => 'match']);
    Tag::factory()->for($user)->create(['name' => 'match', 'slug' => 'match']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=match&types[]=tag')
        ->assertStatus(200)
        ->json();

    expect($response)->toHaveKey('tags');
    expect($response)->not->toHaveKey('snippets');
    expect($response)->not->toHaveKey('projects');
});

test('autocomplete returns all three types by default', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=x')
        ->assertStatus(200)
        ->json();

    expect($response)->toHaveKeys(['snippets', 'tags', 'projects']);
});

// ─── Limit ────────────────────────────────────────────────────────────────────

test('autocomplete respects the limit parameter', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    for ($i = 1; $i <= 10; $i++) {
        Snippet::factory()->for($project)->create(['title' => "result {$i}"]);
    }

    $data = $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=result&limit=3')
        ->assertStatus(200)
        ->json('snippets');

    expect(count($data))->toBeLessThanOrEqual(3);
});

test('autocomplete validates types must be valid', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/autocomplete?q=x&types[]=invalid')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['types.0']);
});
