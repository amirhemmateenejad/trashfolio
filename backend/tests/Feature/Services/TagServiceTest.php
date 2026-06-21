<?php

use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Services\TagService;

test('TagService resolveIds returns given tag IDs', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tagA    = Tag::factory()->for($user)->create();
    $tagB    = Tag::factory()->for($user)->create();

    $ids = (new TagService())->resolveIds($user, [$tagA->id, $tagB->id]);

    expect($ids)->toContain($tagA->id)->toContain($tagB->id);
});

test('TagService resolveIds creates new tags from tag_names', function () {
    $user = User::factory()->create();

    $ids = (new TagService())->resolveIds($user, [], ['Laravel', 'PHP']);

    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'slug' => 'laravel']);
    $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'slug' => 'php']);
    expect($ids)->toHaveCount(2);
});

test('TagService resolveIds deduplicates IDs', function () {
    $user = User::factory()->create();
    $tag  = Tag::factory()->for($user)->create(['slug' => 'foo', 'name' => 'Foo']);

    // Provide same tag via ID and via name
    $ids = (new TagService())->resolveIds($user, [$tag->id], ['Foo']);

    expect($ids)->toHaveCount(1);
});

test('TagService resolveIds reuses existing tag for tag_names', function () {
    $user = User::factory()->create();
    $tag  = Tag::factory()->for($user)->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $ids = (new TagService())->resolveIds($user, [], ['Laravel']);

    $this->assertDatabaseCount('tags', 1);
    expect($ids)->toContain($tag->id);
});

test('TagService resolveNamesForUser returns only caller-owned tag names', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    $tagA = Tag::factory()->for($user)->create(['name' => 'mine']);
    $tagB = Tag::factory()->for($other)->create(['name' => 'theirs']);

    $names = (new TagService())->resolveNamesForUser($user, [$tagA->id, $tagB->id]);

    expect($names)->toContain('mine');
    expect($names)->not->toContain('theirs');
});

test('TagService resolveNamesForUser returns empty array for empty input', function () {
    $user = User::factory()->create();

    expect((new TagService())->resolveNamesForUser($user, []))->toBe([]);
});
