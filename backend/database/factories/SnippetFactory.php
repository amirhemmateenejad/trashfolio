<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Snippet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Snippet>
 */
class SnippetFactory extends Factory
{
    protected $model = Snippet::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'folder_id'  => null,
            'title'      => fake()->sentence(4),
            'content'    => fake()->paragraphs(2, true),
            'language'   => fake()->randomElement(['php', 'javascript', 'python', 'go', null]),
        ];
    }
}
