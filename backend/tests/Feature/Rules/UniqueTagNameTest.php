<?php

use App\Models\Tag;
use App\Models\User;
use App\Rules\UniqueTagName;

function runUniqueTagName(User $user, string $value, ?int $ignoreId = null): bool
{
    $passed = true;
    (new UniqueTagName($user, $ignoreId))->validate('name', $value, function () use (&$passed) {
        $passed = false;
    });
    return $passed;
}

test('UniqueTagName passes when no existing tag with that slug', function () {
    $user = User::factory()->create();

    expect(runUniqueTagName($user, 'My Tag'))->toBeTrue();
});

test('UniqueTagName fails when slug already exists for user', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'My Tag', 'slug' => 'my-tag']);

    expect(runUniqueTagName($user, 'My Tag'))->toBeFalse();
    expect(runUniqueTagName($user, 'my-tag'))->toBeFalse(); // same slug
});

test('UniqueTagName passes for different user with same slug', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Tag::factory()->for($userA)->create(['name' => 'bug', 'slug' => 'bug']);

    expect(runUniqueTagName($userB, 'bug'))->toBeTrue();
});

test('UniqueTagName ignores own tag when ignoreId is provided', function () {
    $user = User::factory()->create();
    $tag  = Tag::factory()->for($user)->create(['name' => 'bug', 'slug' => 'bug']);

    // Should pass when ignoring the tag's own ID (update scenario)
    expect(runUniqueTagName($user, 'bug', $tag->id))->toBeTrue();
});

test('UniqueTagName fails for different tag with same slug when ignoreId is provided', function () {
    $user = User::factory()->create();
    $tagA = Tag::factory()->for($user)->create(['name' => 'bug', 'slug' => 'bug']);
    $tagB = Tag::factory()->for($user)->create(['name' => 'other', 'slug' => 'other']);

    // tagB trying to rename to 'bug' while ignoring tagB's own id
    expect(runUniqueTagName($user, 'bug', $tagB->id))->toBeFalse();
});
