<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HexColor implements ValidationRule
{
    /**
     * Validate that the value is a 6-digit hex color code (e.g. #ff0000).
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            $fail('The :attribute must be a valid hex color (e.g. #ff0000).');
        }
    }
}
