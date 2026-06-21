<?php

namespace App\Rules;

use App\Models\Folder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BelongsToProject implements ValidationRule
{
    /**
     * @param int|null $projectId  The project the folder must belong to
     */
    public function __construct(private ?int $projectId) {}

    /**
     * Validate that the folder belongs to the given project.
     *
     * @param string $attribute
     * @param mixed $value  Folder ID
     * @param Closure $fail
     * @return void
     */
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
