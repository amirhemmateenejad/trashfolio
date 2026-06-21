<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'          => ['required', 'string', 'max:200'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'folder_id'  => ['nullable', 'integer', 'exists:folders,id'],
            'tag_ids'    => ['nullable', 'array'],
            'tag_ids.*'  => ['integer', 'exists:tags,id'],
            'language'   => ['nullable', 'string', 'max:50'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
