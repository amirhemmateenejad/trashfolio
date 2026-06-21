<?php

namespace App\Rules;

use App\Models\Folder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BelongsToProject implements ValidationRule
{
    public function __construct(private ?int $projectId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->projectId) {
            return; // project_id failed its own validation; don't double-error
        }

        $folder = Folder::find($value);

        if (!$folder || $folder->project_id !== $this->projectId) {
            $fail('The :attribute does not belong to the specified project.');
        }
    }
}
