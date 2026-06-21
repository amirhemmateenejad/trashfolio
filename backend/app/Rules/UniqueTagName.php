<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueTagName implements ValidationRule
{
    /**
     * @param User $user      The user whose tags are checked
     * @param int|null $ignoreId  Tag ID to exclude (for update operations)
     */
    public function __construct(
        private User $user,
        private ?int $ignoreId = null,
    ) {}

    /**
     * Validate that the tag name slug is unique for the user.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = Str::slug($value);

        $exists = $this->user->tags()
            ->where('slug', $slug)
            ->when($this->ignoreId, fn($q) => $q->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('A tag with this name already exists.');
        }
    }
}
