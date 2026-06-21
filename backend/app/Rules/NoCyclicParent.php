<?php

namespace App\Rules;

use App\Models\Folder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoCyclicParent implements ValidationRule
{
    public function __construct(private Folder $folder) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $newParentId = (int) $value;

        if ($newParentId === $this->folder->id) {
            $fail('A folder cannot be its own parent.');
            return;
        }

        $newParent = Folder::find($newParentId);

        if (!$newParent) {
            return; // exists rule will catch missing IDs
        }

        if ($newParent->project_id !== $this->folder->project_id) {
            $fail('Parent folder must belong to the same project.');
            return;
        }

        if ($this->folder->isAncestorOf($newParentId)) {
            $fail('Moving this folder here would create a cycle.');
        }
    }
}
