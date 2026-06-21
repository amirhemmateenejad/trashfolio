<?php

use App\Models\Snippet;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;

// E1 – Create & List
test('E55: authenticated user can create a tag with name and color', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/tags', [
        'name'  => 'Bug Fix',
        'color' => '#ff0000',
    ])->assertStatus(201)->assertJsonFragment(['name' => 'Bug Fix', 'slug' => 'bug-fix']);

    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'slug' => 'bug-fix']);
});

test('E56: duplicate tag name for same user is rejected', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'Bug', 'slug' => 'bug']);

    $this->actingAs($user, 'sanctum')->postJson('/api/tags', ['name' => 'Bug'])
        ->assertStatus(422);
});

test('E57: same tag name is allowed for different users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Tag::factory()->for($userA)->create(['name' => 'bug', 'slug' => 'bug']);

    $this->actingAs($userB, 'sanctum')->postJson('/api/tags', ['name' => 'bug'])
        ->assertStatus(201);
});

test('E58: list tags returns only current user tags', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Tag::factory()->for($user)->create(['name' => 'mine']);
    Tag::factory()->for($other)->create(['name' => 'theirs']);

    $this->actingAs($user, 'sanctum')->getJson('/api/tags')
        ->assertStatus(200)
        ->assertJsonFragment(['name' => 'mine'])
        ->assertJsonMissing(['name' => 'theirs']);
});

// E2 – Update & Delete
test('E59: owner can update tag name and color', function () {
    $user = User::factory()->create();
    $tag  = Tag::factory()->for($user)->create(['name' => 'old', 'slug' => 'old']);

    $this->actingAs($user, 'sanctum')->putJson("/api/tags/{$tag->id}", [
        'name'  => 'New Name',
        'color' => '#00ff00',
    ])->assertStatus(200)->assertJsonFragment(['name' => 'New Name', 'slug' => 'new-name']);
});

test('E60: user cannot update tag owned by another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $tag = Tag::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->putJson("/api/tags/{$tag->id}", ['name' => 'x'])
        ->assertStatus(403);
});

test('E61: deleting a tag removes pivot records', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create();
    $snippet->tags()->attach($tag->id);

    $this->actingAs($user, 'sanctum')->deleteJson("/api/tags/{$tag->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    $this->assertDatabaseMissing('snippet_tag', ['tag_id' => $tag->id]);
});

test('E62: user cannot delete tag owned by another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $tag = Tag::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/tags/{$tag->id}")
        ->assertStatus(403);
});

// E3 – Attach/Detach
test('E63: user can attach their own tag to their own snippet', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson("/api/snippets/{$snippet->id}/tags/{$tag->id}")
        ->assertStatus(200);

    $this->assertDatabaseHas('snippet_tag', ['snippet_id' => $snippet->id, 'tag_id' => $tag->id]);
});

test('E64: user can detach tag from their snippet', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create();
    $snippet->tags()->attach($tag->id);

    $this->actingAs($user, 'sanctum')->deleteJson("/api/snippets/{$snippet->id}/tags/{$tag->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('snippet_tag', ['snippet_id' => $snippet->id, 'tag_id' => $tag->id]);
});

test('E65: user cannot attach another user\'s tag to their snippet', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->postJson("/api/snippets/{$snippet->id}/tags/{$tag->id}")
        ->assertStatus(403);
});

test('E66: user cannot attach tag to another user\'s snippet', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $tag     = Tag::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson("/api/snippets/{$snippet->id}/tags/{$tag->id}")
        ->assertStatus(403);
});
