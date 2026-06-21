<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\Snippet;
use App\Models\User;

// ─── GET /api/trash ──────────────────────────────────────────────────────────

test('trash index requires authentication', function () {
    $this->getJson('/api/trash')->assertStatus(401);
});

test('trash index returns soft-deleted projects belonging to the user', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'Deleted Project']);
    $project->delete();

    $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->assertJsonFragment(['type' => 'project', 'title' => 'Deleted Project']);
});

test('trash index returns soft-deleted folders belonging to the user', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create(['title' => 'Deleted Folder']);
    $folder->delete();

    $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->assertJsonFragment(['type' => 'folder', 'title' => 'Deleted Folder']);
});

test('trash index returns soft-deleted snippets belonging to the user', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create(['title' => 'Deleted Snippet']);
    $snippet->delete();

    $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->assertJsonFragment(['type' => 'snippet', 'title' => 'Deleted Snippet']);
});

test('trash index does not expose other users deleted items', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create(['title' => 'Other User Project']);
    $project->delete();

    $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->assertJsonMissing(['title' => 'Other User Project']);
});

test('trash index includes type, id, title, deleted_at in each item', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['title' => 'Check Fields']);
    $project->delete();

    $data = $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->json('data');

    expect($data[0])->toHaveKeys(['type', 'id', 'title', 'deleted_at']);
});

test('trash index is paginated', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    for ($i = 1; $i <= 5; $i++) {
        $s = Snippet::factory()->for($project)->create(['title' => "Snippet {$i}"]);
        $s->delete();
    }

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/trash?per_page=2');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'total', 'per_page', 'current_page']);

    expect($response->json('per_page'))->toBe(2);
    expect($response->json('total'))->toBeGreaterThanOrEqual(5);
    expect($response->json('data'))->toHaveCount(2);
});

test('trash index includes folders whose parent project is also trashed', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create(['title' => 'Orphaned Folder']);

    // Soft-delete project first (cascades to folder)
    $project->delete();

    // Both should appear in trash
    $data = $this->actingAs($user, 'sanctum')->getJson('/api/trash')
        ->assertStatus(200)
        ->json('data');

    $types = collect($data)->pluck('type')->toArray();
    expect($types)->toContain('project');
    expect($types)->toContain('folder');
});

// ─── POST /api/trash/{type}/{id}/restore ─────────────────────────────────────

test('restore requires authentication', function () {
    $this->postJson('/api/trash/project/1/restore')->assertStatus(401);
});

test('owner can restore a soft-deleted project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $project->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/project/{$project->id}/restore")
        ->assertStatus(200)
        ->assertJsonFragment(['message' => 'restored']);

    $this->assertNotSoftDeleted('projects', ['id' => $project->id]);
});

test('owner can restore a soft-deleted folder', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();
    $folder->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/folder/{$folder->id}/restore")
        ->assertStatus(200);

    $this->assertNotSoftDeleted('folders', ['id' => $folder->id]);
});

test('owner can restore a soft-deleted snippet', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $snippet->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/snippet/{$snippet->id}/restore")
        ->assertStatus(200);

    $this->assertNotSoftDeleted('snippets', ['id' => $snippet->id]);
});

test('user cannot restore another user\'s trashed project', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $project->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/project/{$project->id}/restore")
        ->assertStatus(403);
});

test('user cannot restore another user\'s trashed folder', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $folder  = Folder::factory()->for($project)->create();
    $folder->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/folder/{$folder->id}/restore")
        ->assertStatus(403);
});

test('user cannot restore another user\'s trashed snippet', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $snippet->delete();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/snippet/{$snippet->id}/restore")
        ->assertStatus(403);
});

test('restore returns 404 for invalid type', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/trash/invalidtype/1/restore')
        ->assertStatus(404);
});

test('restore returns 404 for non-existent item', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/trash/project/99999/restore')
        ->assertStatus(404);
});

test('restore returns 404 for item that is not soft-deleted', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    // Project is not deleted — onlyTrashed() should return nothing
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/trash/project/{$project->id}/restore")
        ->assertStatus(404);
});

// ─── DELETE /api/trash/{type}/{id} ───────────────────────────────────────────

test('permanent delete requires authentication', function () {
    $this->deleteJson('/api/trash/project/1')->assertStatus(401);
});

test('owner can permanently delete a trashed project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $project->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/project/{$project->id}")
        ->assertStatus(200)
        ->assertJsonFragment(['message' => 'permanently deleted']);

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('owner can permanently delete a trashed folder', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();
    $folder->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/folder/{$folder->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
});

test('owner can permanently delete a trashed snippet', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $snippet->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/snippet/{$snippet->id}")
        ->assertStatus(200);

    $this->assertDatabaseMissing('snippets', ['id' => $snippet->id]);
});

test('user cannot permanently delete another user\'s trashed project', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $project->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/project/{$project->id}")
        ->assertStatus(403);
});

test('user cannot permanently delete another user\'s trashed folder', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $folder  = Folder::factory()->for($project)->create();
    $folder->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/folder/{$folder->id}")
        ->assertStatus(403);
});

test('user cannot permanently delete another user\'s trashed snippet', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $project = Project::factory()->for($other)->create();
    $snippet = Snippet::factory()->for($project)->create();
    $snippet->delete();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/snippet/{$snippet->id}")
        ->assertStatus(403);
});

test('permanent delete returns 404 for non-trashed item', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/trash/project/{$project->id}")
        ->assertStatus(404);
});

// ─── DELETE /api/trash (empty all) ───────────────────────────────────────────

test('empty trash permanently deletes all user trash', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $snippet = Snippet::factory()->for($project)->create();

    $project->delete();
    // snippet is cascade-deleted with project, so re-delete separately for this test
    // Actually soft-cascade means snippet is also soft-deleted when project is
    // So they're both in trash now

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/trash')
        ->assertStatus(200)
        ->assertJsonFragment(['message' => 'trash emptied']);

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('snippets', ['id' => $snippet->id]);
});

test('empty trash does not affect other users items', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $otherProject = Project::factory()->for($other)->create();
    $otherProject->delete();

    $this->actingAs($user, 'sanctum')->deleteJson('/api/trash')->assertStatus(200);

    $this->assertSoftDeleted('projects', ['id' => $otherProject->id]);
});
