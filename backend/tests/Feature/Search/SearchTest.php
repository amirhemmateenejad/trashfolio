<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;

// F1 – Full-text search
test('F67: authenticated user can search snippets by title', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'unique-term-xyz hello', 'content' => 'a']);
    Snippet::factory()->for($project)->create(['title' => 'something else',        'content' => 'b']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/search?q=unique-term-xyz');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['title'])->toContain('unique-term-xyz');
});

test('F67b: authenticated user can search snippets by content', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'alpha', 'content' => 'needle-in-content']);
    Snippet::factory()->for($project)->create(['title' => 'beta',  'content' => 'nothing here']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/search?q=needle-in-content');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(collect($data)->pluck('title'))->toContain('alpha');
});

test('F68: search results do not include snippets of other users', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $myProject    = Project::factory()->for($user)->create();
    $theirProject = Project::factory()->for($other)->create();

    Snippet::factory()->for($myProject)->create(['title' => 'shared-keyword mine',  'content' => 'x']);
    Snippet::factory()->for($theirProject)->create(['title' => 'shared-keyword theirs', 'content' => 'x']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/search?q=shared-keyword');

    $response->assertStatus(200);
    $data = $response->json('data');

    $titles = collect($data)->pluck('title')->toArray();
    expect($titles)->toContain('shared-keyword mine');
    expect($titles)->not->toContain('shared-keyword theirs');
});

test('F69: search results are paginated', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    for ($i = 1; $i <= 5; $i++) {
        Snippet::factory()->for($project)->create(['title' => "paged-result {$i}", 'content' => 'x']);
    }

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/search?q=paged-result&per_page=2');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'total', 'per_page', 'current_page']);

    expect($response->json('per_page'))->toBe(2);
    expect($response->json('total'))->toBeGreaterThanOrEqual(5);
    expect($response->json('data'))->toHaveCount(2);
});

test('F70: search can be filtered by project_id', function () {
    $user     = User::factory()->create();
    $project1 = Project::factory()->for($user)->create();
    $project2 = Project::factory()->for($user)->create();

    Snippet::factory()->for($project1)->create(['title' => 'filter-project-test', 'content' => 'x']);
    Snippet::factory()->for($project2)->create(['title' => 'filter-project-test', 'content' => 'x']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/search?q=filter-project-test&project_id={$project1->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['project_id'])->toBe($project1->id);
});

test('F70b: search can be filtered by folder_id', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    Snippet::factory()->for($project)->create(['folder_id' => $folder->id, 'title' => 'in-folder', 'content' => 'x']);
    Snippet::factory()->for($project)->create(['title' => 'in-folder', 'content' => 'x']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/search?q=in-folder&folder_id={$folder->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['folder_id'])->toBe($folder->id);
});

test('F72: search can be filtered by language', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Snippet::factory()->for($project)->create(['title' => 'lang-test', 'content' => 'x', 'language' => 'php']);
    Snippet::factory()->for($project)->create(['title' => 'lang-test', 'content' => 'x', 'language' => 'javascript']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/search?q=lang-test&language=php');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['language'])->toBe('php');
});

test('F71: search can be filtered by tag_ids', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($user)->create(['name' => 'laravel', 'slug' => 'laravel']);

    $tagged   = Snippet::factory()->for($project)->create(['title' => 'tag-filter-test', 'content' => 'x']);
    $untagged = Snippet::factory()->for($project)->create(['title' => 'tag-filter-test', 'content' => 'x']);

    $tagged->tags()->attach($tag->id);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/search?q=tag-filter-test&tag_ids[]={$tag->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($tagged->id);
});

test('F71b: tag_ids filter only considers current user tags', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project    = Project::factory()->for($user)->create();
    $otherTag   = Tag::factory()->for($other)->create(['name' => 'other-tag', 'slug' => 'other-tag']);
    $snippet    = Snippet::factory()->for($project)->create(['title' => 'cross-tag-test', 'content' => 'x']);
    $snippet->tags()->attach($otherTag->id);

    // Even though we pass the other user's tag ID, it won't match (tag not owned by us)
    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/search?q=cross-tag-test&tag_ids[]={$otherTag->id}");

    $response->assertStatus(200);
    // Should return 0 results because the tag is not ours
    expect($response->json('data'))->toHaveCount(0);
});

test('F: search requires authentication', function () {
    $this->getJson('/api/search?q=test')
        ->assertStatus(401);
});

test('F: q parameter is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->getJson('/api/search')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['q']);
});

test('F: search response includes tags on each snippet', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($user)->create(['name' => 'included-tag']);
    $snippet = Snippet::factory()->for($project)->create(['title' => 'with-tag', 'content' => 'x']);
    $snippet->tags()->attach($tag->id);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/search?q=with-tag');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->not->toBeEmpty();
    expect($data[0])->toHaveKey('tags');
    $tagNames = collect($data[0]['tags'])->pluck('name')->toArray();
    expect($tagNames)->toContain('included-tag');
});
