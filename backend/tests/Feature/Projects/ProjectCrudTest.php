<?php

use App\Models\Project;
use App\Models\User;

// B1 – Create Project
test('B18: authenticated user can create a project with valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/projects', [
        'title'       => 'My Project',
        'description' => 'A description',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['title' => 'My Project']);

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'title'   => 'My Project',
    ]);
});

test('B19: project creation fails when name is empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')->postJson('/api/projects', ['title' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('B20: unauthenticated user cannot create a project', function () {
    $this->postJson('/api/projects', ['title' => 'Hacker'])
        ->assertStatus(401);
});

// B2 – List & View Projects
test('B21: list projects returns only own projects', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Project::factory()->for($user)->create(['title' => 'Mine']);
    Project::factory()->for($other)->create(['title' => 'Not Mine']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/projects');

    $response->assertStatus(200)
        ->assertJsonFragment(['title' => 'Mine'])
        ->assertJsonMissing(['title' => 'Not Mine']);
});

test('B22: user cannot see another user\'s project', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->getJson("/api/projects/{$project->id}")
        ->assertStatus(403);
});

test('B23: owner can view own project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->getJson("/api/projects/{$project->id}")
        ->assertStatus(200)
        ->assertJsonFragment(['id' => $project->id]);
});

// B3 – Update & Delete
test('B24: owner can update project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'Old']);

    $this->actingAs($user, 'sanctum')->putJson("/api/projects/{$project->id}", [
        'title' => 'New Title',
    ])->assertStatus(200)->assertJsonFragment(['title' => 'New Title']);
});

test('B25: user cannot update project not owned by them', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->putJson("/api/projects/{$project->id}", [
        'title' => 'Hacked',
    ])->assertStatus(403);
});

test('B26: owner can delete project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/projects/{$project->id}")
        ->assertStatus(200);

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

test('B27: user cannot delete project not owned by them', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();

    $this->actingAs($user, 'sanctum')->deleteJson("/api/projects/{$project->id}")
        ->assertStatus(403);
});
