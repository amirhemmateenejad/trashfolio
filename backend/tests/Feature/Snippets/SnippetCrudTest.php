<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\Tag;
use App\Models\User;

// D1 – Create Snippet
test('D37: user can create a snippet at project root', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'title'      => 'Hello Snippet',
        'content'    => '<?php echo "hello";',
        'language'   => 'php',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['title' => 'Hello Snippet', 'language' => 'php']);

    $this->assertDatabaseHas('snippets', ['project_id' => $project->id, 'title' => 'Hello Snippet']);
});

test('D38: user can create a snippet inside a folder', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'folder_id'  => $folder->id,
        'title'      => 'In Folder',
        'content'    => 'code',
    ])->assertStatus(201)->assertJsonFragment(['folder_id' => $folder->id]);
});

test('D39: title and content are required', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
    ])->assertStatus(422)->assertJsonValidationErrors(['title', 'content']);
});

test('D38b: folder_id must belong to the same project', function () {
    $user     = User::factory()->create();
    $project  = Project::factory()->for($user)->create();
    $project2 = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($project2)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'folder_id'  => $folder->id,
        'title'      => 'Bad',
        'content'    => 'code',
    ])->assertStatus(422);
});

test('D40: can attach existing tags by tag_ids on create', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'title'      => 'Tagged',
        'content'    => 'x',
        'tag_ids'    => [$tag->id],
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('snippet_tag', [
        'snippet_id' => $response->json('id'),
        'tag_id'     => $tag->id,
    ]);
});

test('D40b: attaching a tag belonging to another user is forbidden', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($user)->create();
    $tag     = Tag::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'title'      => 'Bad',
        'content'    => 'x',
        'tag_ids'    => [$tag->id],
    ])->assertStatus(403);
});

test('D41: tag_names creates new tags for user and attaches them', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/snippets', [
        'project_id' => $project->id,
        'title'      => 'Named Tags',
        'content'    => 'code',
        'tag_names'  => ['laravel', 'php'],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'slug' => 'laravel']);
    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'slug' => 'php']);
    expect($response->json('tags'))->toHaveCount(2);
});

// D2 – Update Snippet
test('D42: owner can update basic snippet fields', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->putJson("/api/snippets/{$snippet->id}", [
        'title'   => 'Updated',
        'content' => 'new code',
    ])->assertStatus(200)->assertJsonFragment(['title' => 'Updated']);
});

test('D44: syncing tag_ids replaces old tags', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tagA    = Tag::factory()->for($user)->create();
    $tagB    = Tag::factory()->for($user)->create();

    $snippet->tags()->attach($tagA->id);

    $this->actingAs($user, 'sanctum')->putJson("/api/snippets/{$snippet->id}", [
        'title'   => $snippet->title,
        'content' => $snippet->content,
        'tag_ids' => [$tagB->id],
    ])->assertStatus(200);

    $this->assertDatabaseMissing('snippet_tag', ['snippet_id' => $snippet->id, 'tag_id' => $tagA->id]);
    $this->assertDatabaseHas('snippet_tag', ['snippet_id' => $snippet->id, 'tag_id' => $tagB->id]);
});

test('D47: user cannot update snippet owned by another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->putJson("/api/snippets/{$snippet->id}", [
        'title'   => 'Hacked',
        'content' => 'x',
    ])->assertStatus(403);
});

// D3 – Delete
test('D48: owner can delete snippet and pivot entries are removed', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create();
    $snippet->tags()->attach($tag->id);

    $this->actingAs($user, 'sanctum')->deleteJson("/api/snippets/{$snippet->id}")
        ->assertStatus(200);

    $this->assertSoftDeleted('snippets', ['id' => $snippet->id]);
    $this->assertDatabaseMissing('snippet_tag', ['snippet_id' => $snippet->id]);
});

test('D49: user cannot delete snippet owned by another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/snippets/{$snippet->id}")
        ->assertStatus(403);
});

// D4 – List & View
test('D50: listing snippets returns only own snippets', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project      = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($other)->create();

    Snippet::factory()->for($project)->create(['title' => 'Mine']);
    Snippet::factory()->for($otherProject)->create(['title' => 'Not Mine']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/snippets');

    $response->assertStatus(200)
        ->assertJsonFragment(['title' => 'Mine'])
        ->assertJsonMissing(['title' => 'Not Mine']);
});

test('D53: snippet response includes eager-loaded tags', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create(['name' => 'mytag']);
    $snippet->tags()->attach($tag->id);

    $this->actingAs($user, 'sanctum')->getJson("/api/snippets/{$snippet->id}")
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'mytag']);
});

test('D54: user cannot access snippet of another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->getJson("/api/snippets/{$snippet->id}")
        ->assertStatus(403);
});
