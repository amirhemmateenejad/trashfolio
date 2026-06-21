<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\User;

// C1 – Create
test('C28: authenticated user can create a folder in their project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/folders', [
        'project_id' => $project->id,
        'title'      => 'My Folder',
    ])->assertStatus(201)->assertJsonFragment(['title' => 'My Folder']);
});

test('C29: folder title is required', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/folders', [
        'project_id' => $project->id,
        'title'      => '',
    ])->assertStatus(422)->assertJsonValidationErrors(['title']);
});

test('C30: user cannot create folder in another user\'s project', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/folders', [
        'project_id' => $project->id,
        'title'      => 'Hacked',
    ])->assertStatus(403);
});

test('C29b: parent_id must belong to same project', function () {
    $user     = User::factory()->create();
    $project  = Project::factory()->for($user)->create();
    $project2 = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($project2)->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/folders', [
        'project_id' => $project->id,
        'parent_id'  => $folder->id,
        'title'      => 'Child',
    ])->assertStatus(403);
});

// C3 – Update & Delete
test('C33: owner can rename folder', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create(['title' => 'Old']);

    $this->actingAs($user, 'sanctum')->putJson("/api/folders/{$folder->id}", [
        'title' => 'Renamed',
    ])->assertStatus(200)->assertJsonFragment(['title' => 'Renamed']);
});

test('C35: deleting a folder soft-deletes it', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/folders/{$folder->id}")
        ->assertStatus(200);

    $this->assertSoftDeleted('folders', ['id' => $folder->id]);
});

test('C36: user cannot update or delete folder of another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $folder  = Folder::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')->putJson("/api/folders/{$folder->id}", ['title' => 'x'])
        ->assertStatus(403);

    $this->actingAs($user, 'sanctum')->deleteJson("/api/folders/{$folder->id}")
        ->assertStatus(403);
});
