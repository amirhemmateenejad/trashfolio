<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\User;

// ─── C31–C32: Loop/cycle detection in parent_id ───────────────────────────────

test('C31: cannot set a folder as its own parent', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$folder->id}", ['parent_id' => $folder->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('C32: cannot create a cycle (child cannot become parent of its ancestor)', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $parent = Folder::factory()->for($project)->create(['title' => 'Parent']);
    $child  = Folder::factory()->for($project)->create(['title' => 'Child', 'parent_id' => $parent->id]);

    // Trying to set parent's parent to child would create: parent -> child -> parent
    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$parent->id}", ['parent_id' => $child->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('C32b: cannot create deep cycle (A -> B -> C -> A)', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $a = Folder::factory()->for($project)->create(['title' => 'A']);
    $b = Folder::factory()->for($project)->create(['title' => 'B', 'parent_id' => $a->id]);
    $c = Folder::factory()->for($project)->create(['title' => 'C', 'parent_id' => $b->id]);

    // Setting A's parent to C would create A -> B -> C -> A
    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$a->id}", ['parent_id' => $c->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

// ─── C34: Move folder with same-project validation ───────────────────────────

test('C34: can move folder to a different parent in the same project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $oldParent = Folder::factory()->for($project)->create(['title' => 'Old Parent']);
    $newParent = Folder::factory()->for($project)->create(['title' => 'New Parent']);
    $child     = Folder::factory()->for($project)->create(['title' => 'Child', 'parent_id' => $oldParent->id]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$child->id}", ['parent_id' => $newParent->id])
        ->assertStatus(200);

    $this->assertDatabaseHas('folders', ['id' => $child->id, 'parent_id' => $newParent->id]);
});

test('C34b: cannot move folder to parent in a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();

    $folder        = Folder::factory()->for($projectA)->create();
    $foreignParent = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$folder->id}", ['parent_id' => $foreignParent->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('C34c: can remove parent (move to root) by setting parent_id to null', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $parent  = Folder::factory()->for($project)->create();
    $child   = Folder::factory()->for($project)->create(['parent_id' => $parent->id]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/folders/{$child->id}", ['parent_id' => null])
        ->assertStatus(200);

    $this->assertDatabaseHas('folders', ['id' => $child->id, 'parent_id' => null]);
});

// ─── Create: cross-project parent validation ─────────────────────────────────

test('C31c: cannot create folder with parent from a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();

    $foreignParent = Folder::factory()->for($projectB)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/folders', [
            'project_id' => $projectA->id,
            'parent_id'  => $foreignParent->id,
            'title'      => 'Bad Folder',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});
