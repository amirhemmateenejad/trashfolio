<?php

use App\Models\Folder;
use App\Models\Project;
use App\Models\User;
use App\Rules\NoCyclicParent;

function runNoCyclicParent(Folder $folder, mixed $value): bool
{
    $passed = true;
    (new NoCyclicParent($folder))->validate('parent_id', $value, function () use (&$passed) {
        $passed = false;
    });
    return $passed;
}

test('NoCyclicParent passes when setting valid parent in same project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $parent  = Folder::factory()->for($project)->create();
    $child   = Folder::factory()->for($project)->create();

    expect(runNoCyclicParent($child, $parent->id))->toBeTrue();
});

test('NoCyclicParent fails when folder is set as its own parent', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $folder  = Folder::factory()->for($project)->create();

    expect(runNoCyclicParent($folder, $folder->id))->toBeFalse();
});

test('NoCyclicParent fails when parent is a descendant', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $parent  = Folder::factory()->for($project)->create();
    $child   = Folder::factory()->for($project)->create(['parent_id' => $parent->id]);

    // Setting parent's parent to its child creates a cycle
    expect(runNoCyclicParent($parent, $child->id))->toBeFalse();
});

test('NoCyclicParent fails for deep cycle (A->B->C, setting A parent to C)', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $a       = Folder::factory()->for($project)->create();
    $b       = Folder::factory()->for($project)->create(['parent_id' => $a->id]);
    $c       = Folder::factory()->for($project)->create(['parent_id' => $b->id]);

    expect(runNoCyclicParent($a, $c->id))->toBeFalse();
});

test('NoCyclicParent fails when new parent is in a different project', function () {
    $user     = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();
    $folder   = Folder::factory()->for($projectA)->create();
    $other    = Folder::factory()->for($projectB)->create();

    expect(runNoCyclicParent($folder, $other->id))->toBeFalse();
});
