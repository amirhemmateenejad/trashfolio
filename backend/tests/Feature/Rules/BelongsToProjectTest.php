<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\User;
use App\Rules\BelongsToProject;

function runBelongsToProject(?int $projectId, mixed $value): bool
{
    $passed = true;
    (new BelongsToProject($projectId))->validate('folder_id', $value, function () use (&$passed) {
        $passed = false;
    });
    return $passed;
}

test('BelongsToProject passes when folder belongs to given project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    expect(runBelongsToProject($project->id, $folder->id))->toBeTrue();
});

test('BelongsToProject fails when folder belongs to different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($projectB)->create();

    expect(runBelongsToProject($projectA->id, $folder->id))->toBeFalse();
});

test('BelongsToProject fails when folder does not exist', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    expect(runBelongsToProject($project->id, 99999))->toBeFalse();
});

test('BelongsToProject passes without validating when projectId is null', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    // When project_id is missing/null, rule defers to other validators
    expect(runBelongsToProject(null, $folder->id))->toBeTrue();
});
