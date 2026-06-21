<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListSnippetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'language'  => ['nullable', 'string', 'max:50'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
