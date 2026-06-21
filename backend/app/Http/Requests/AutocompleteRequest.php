<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutocompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'     => ['required', 'string', 'min:1', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'in:snippet,tag,project'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
